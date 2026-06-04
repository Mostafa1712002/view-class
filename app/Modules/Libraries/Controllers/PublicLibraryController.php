<?php

namespace App\Modules\Libraries\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LibraryItem;
use App\Models\Subject;
use App\Models\User;
use App\Modules\Libraries\Repositories\Contracts\LibraryItemRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PublicLibraryController extends Controller
{
    use HasSchoolScope;

    public function __construct(private LibraryItemRepository $items) {}

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $filters = $request->only(['title', 'content_type', 'subject_id', 'teacher_id', 'tag', 'sort']);
        $items = $this->items->paginatePublic($schoolId, $filters);

        $subjects = Subject::query()
            ->where(function ($q) use ($schoolId) {
                $q->whereNull('school_id');
                if ($schoolId) {
                    $q->orWhere('school_id', $schoolId);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $teachers = User::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereHas('roles', fn ($q) => $q->where('name', 'teacher'))
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name']);

        $types = LibraryItem::TYPES;

        return view('admin.libraries.public.index', compact('items', 'filters', 'subjects', 'teachers', 'types'));
    }

    public function create(): View
    {
        $item = new LibraryItem;

        return view('admin.libraries.public.create', $this->formData($item));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateItem($request);
        $data['is_public'] = $request->boolean('is_public', true);
        $data['allow_comments'] = $request->boolean('allow_comments', true);
        $data['library_id'] = null;
        $data['school_id'] = $this->activeSchoolId();
        $data['created_by'] = auth()->id();
        $data = $this->handleUploads($request, $data);

        $this->items->create($data);

        return redirect()->route('admin.libraries.public.index')
            ->with('success', __('libraries.flash.item_created'));
    }

    public function edit(int $id): View
    {
        $item = $this->items->findScoped($id, $this->activeSchoolId());
        abort_if(! $item, 404);

        return view('admin.libraries.public.edit', $this->formData($item));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $item = $this->items->findScoped($id, $this->activeSchoolId());
        abort_if(! $item, 404);

        $data = $this->validateItem($request);
        $data['is_public'] = $request->boolean('is_public', true);
        $data['allow_comments'] = $request->boolean('allow_comments', true);
        $data = $this->handleUploads($request, $data, $item);

        $this->items->update($item, $data);

        return redirect()->route('admin.libraries.public.index')
            ->with('success', __('libraries.flash.item_updated'));
    }

    /** Item detail page with ratings + comments (card #97). */
    public function show(int $id): View
    {
        $item = $this->items->findScoped($id, $this->activeSchoolId());
        abort_if(! $item, 404);

        $item->load(['subject', 'teacher', 'comments.user']);
        $userRating = \App\Models\LibraryItemRating::where('library_item_id', $item->id)
            ->where('user_id', auth()->id())
            ->value('rating');

        // Like / dislike / understood reactions (card #118).
        $reactionCounts = \App\Models\LibraryItemReaction::where('library_item_id', $item->id)
            ->selectRaw('type, COUNT(*) as c')->groupBy('type')->pluck('c', 'type');
        $myReactions = \App\Models\LibraryItemReaction::where('library_item_id', $item->id)
            ->where('user_id', auth()->id())->pluck('type')->all();

        return view('admin.libraries.public.show', [
            'item' => $item,
            'avg' => $item->averageRating(),
            'count' => $item->ratingsCount(),
            'userRating' => $userRating,
            'likeCount' => (int) ($reactionCounts['like'] ?? 0),
            'dislikeCount' => (int) ($reactionCounts['dislike'] ?? 0),
            'understoodCount' => (int) ($reactionCounts['understood'] ?? 0),
            'myReaction' => in_array('like', $myReactions, true) ? 'like' : (in_array('dislike', $myReactions, true) ? 'dislike' : null),
            'iUnderstood' => in_array('understood', $myReactions, true),
        ]);
    }

    /**
     * Toggle a like/dislike/understood reaction (card #118).
     * Like and dislike are mutually exclusive (switching moves the reaction);
     * clicking the active one again removes it. "Understood" is an independent toggle.
     */
    public function react(Request $request, int $id): RedirectResponse
    {
        $item = $this->items->findScoped($id, $this->activeSchoolId());
        abort_if(! $item, 404);

        $data = $request->validate([
            'type' => ['required', \Illuminate\Validation\Rule::in(\App\Models\LibraryItemReaction::TYPES)],
        ]);
        $type = $data['type'];
        $userId = auth()->id();

        if ($type === 'understood') {
            $existing = \App\Models\LibraryItemReaction::where('library_item_id', $item->id)
                ->where('user_id', $userId)->where('type', 'understood')->first();
            $existing ? $existing->delete() : \App\Models\LibraryItemReaction::create([
                'library_item_id' => $item->id, 'user_id' => $userId, 'type' => 'understood',
            ]);

            return back()->with('success', __('libraries.flash.reacted'));
        }

        // like / dislike — mutually exclusive
        $current = \App\Models\LibraryItemReaction::where('library_item_id', $item->id)
            ->where('user_id', $userId)->whereIn('type', ['like', 'dislike'])->first();

        if ($current && $current->type === $type) {
            $current->delete();                 // clicking the active reaction again removes it
        } elseif ($current) {
            $current->update(['type' => $type]); // switch like <-> dislike
        } else {
            \App\Models\LibraryItemReaction::create([
                'library_item_id' => $item->id, 'user_id' => $userId, 'type' => $type,
            ]);
        }

        return back()->with('success', __('libraries.flash.reacted'));
    }

    /** Rate an item 1-5; one rating per user, editable (card #97). */
    public function rate(Request $request, int $id): RedirectResponse
    {
        $item = $this->items->findScoped($id, $this->activeSchoolId());
        abort_if(! $item, 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
        ]);

        \App\Models\LibraryItemRating::updateOrCreate(
            ['library_item_id' => $item->id, 'user_id' => auth()->id()],
            ['rating' => $data['rating']],
        );

        return back()->with('success', __('libraries.flash.rated'));
    }

    /** Post a comment (only when the item allows comments) (card #97). */
    public function storeComment(Request $request, int $id): RedirectResponse
    {
        $item = $this->items->findScoped($id, $this->activeSchoolId());
        abort_if(! $item, 404);
        abort_unless($item->allow_comments, 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        \App\Models\LibraryItemComment::create([
            'library_item_id' => $item->id,
            'user_id' => auth()->id(),
            'body' => $data['body'],
        ]);

        return back()->with('success', __('libraries.flash.commented'));
    }

    /** Delete a comment — its owner or any staff admin (card #97). */
    public function destroyComment(int $id, int $commentId): RedirectResponse
    {
        $item = $this->items->findScoped($id, $this->activeSchoolId());
        abort_if(! $item, 404);

        $comment = \App\Models\LibraryItemComment::where('library_item_id', $item->id)
            ->whereKey($commentId)
            ->firstOrFail();

        $user = auth()->user();
        abort_unless(
            $comment->user_id === $user->id || $user->isSuperAdmin() || $user->isSchoolAdmin(),
            403,
        );

        $comment->delete();

        return back()->with('success', __('libraries.flash.comment_deleted'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $item = $this->items->findScoped($id, $this->activeSchoolId());
        abort_if(! $item, 404);

        $this->items->delete($item);

        return redirect()->route('admin.libraries.public.index')
            ->with('success', __('libraries.flash.item_deleted'));
    }

    private function validateItem(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'content_type' => ['required', 'in:'.implode(',', LibraryItem::TYPES)],
            'external_url' => ['nullable', 'url', 'max:1024'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'tags' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'file' => ['nullable', 'file', 'max:51200'], // 50MB
            'thumbnail' => ['nullable', 'image', 'max:5120'],
        ]);
    }

    private function handleUploads(Request $request, array $data, ?LibraryItem $existing = null): array
    {
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('libraries/items', 'public');
            $data['file_path'] = $path;
            if ($existing && $existing->file_path) {
                Storage::disk('public')->delete($existing->file_path);
            }
        }
        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('libraries/thumbs', 'public');
            $data['thumbnail_path'] = $path;
            if ($existing && $existing->thumbnail_path) {
                Storage::disk('public')->delete($existing->thumbnail_path);
            }
        }
        unset($data['file'], $data['thumbnail']);

        return $data;
    }

    private function formData(LibraryItem $item): array
    {
        $schoolId = $this->activeSchoolId();

        $subjects = Subject::query()
            ->where(function ($q) use ($schoolId) {
                $q->whereNull('school_id');
                if ($schoolId) {
                    $q->orWhere('school_id', $schoolId);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $teachers = User::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereHas('roles', fn ($q) => $q->where('name', 'teacher'))
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name']);

        $types = LibraryItem::TYPES;

        return compact('item', 'subjects', 'teachers', 'types');
    }
}
