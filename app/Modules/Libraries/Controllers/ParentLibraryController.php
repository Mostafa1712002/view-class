<?php

namespace App\Modules\Libraries\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Library;
use App\Models\LibraryAudience;
use App\Models\LibraryItem;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ParentLibraryController extends Controller
{
    use HasSchoolScope;

    public function index(Request $request): View
    {
        $user     = auth()->user();
        $schoolId = $this->activeSchoolId();

        // --- Public library items (school-scoped, same as admin public view) ---
        $publicFilters = $request->only(['title', 'content_type', 'sort']);

        $publicQuery = LibraryItem::query()
            ->where('is_public', true)
            ->where(function ($w) use ($schoolId) {
                $w->whereNull('school_id');
                if ($schoolId) {
                    $w->orWhere('school_id', $schoolId);
                }
            });

        if (! empty($publicFilters['title'])) {
            $publicQuery->where('title', 'like', '%' . $publicFilters['title'] . '%');
        }
        if (! empty($publicFilters['content_type'])) {
            $publicQuery->where('content_type', $publicFilters['content_type']);
        }

        $publicQuery->withAvg('ratings as ratings_avg', 'rating')->withCount('ratings');

        if (($publicFilters['sort'] ?? '') === 'top_rated') {
            $publicQuery->orderByDesc('ratings_avg')->orderByDesc('id');
        } elseif (($publicFilters['sort'] ?? '') === 'oldest') {
            $publicQuery->orderBy('id');
        } else {
            $publicQuery->orderByDesc('id');
        }

        $publicItems = $publicQuery->paginate(12)->withQueryString();

        // --- Private libraries accessible to the parent's children ---
        // A private library is accessible when:
        //   1. The library's school_id matches the parent's school (school-level audience), OR
        //   2. The library has a 'school' audience entry for the parent's school_id, OR
        //   3. The library has a 'user' audience entry for one of the parent's children.
        $childIds = $user->children()->pluck('users.id')->all();

        $privateLibraries = collect();
        if ($schoolId) {
            $privateLibraries = Library::query()
                ->where('type', 'private')
                ->where('is_active', true)
                ->where('school_id', $schoolId)
                ->where(function ($q) use ($schoolId, $childIds) {
                    // Accessible when there is a school-level audience for this school,
                    // OR when one of the parent's children is explicitly listed.
                    $q->whereHas('audiences', function ($a) use ($schoolId) {
                        $a->where('audience_type', 'school')
                          ->where('audience_id', $schoolId);
                    });
                    if (! empty($childIds)) {
                        $q->orWhereHas('audiences', function ($a) use ($childIds) {
                            $a->where('audience_type', 'user')
                              ->whereIn('audience_id', $childIds);
                        });
                    }
                })
                ->withCount('items')
                ->orderByDesc('id')
                ->get();
        }

        $types = LibraryItem::TYPES;

        return view('libraries.my.index', compact(
            'publicItems',
            'publicFilters',
            'privateLibraries',
            'types',
        ));
    }
}
