<?php

namespace App\Modules\Policies\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Policy;
use App\Models\PolicyAcknowledgement;
use App\Models\User;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PolicyController extends Controller
{
    use HasSchoolScope;

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $q = $request->string('q')->toString();

        $policies = Policy::query()
            ->when($schoolId, fn ($w) => $w->where(fn ($x) => $x->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->when($q, fn ($w) => $w->where('title', 'like', "%{$q}%"))
            ->withCount([
                'acknowledgements as beneficiaries_count',
                'acknowledgements as read_count' => fn ($x) => $x->whereNotNull('read_at'),
            ])
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.policies.index', ['policies' => $policies, 'q' => $q]);
    }

    public function create(): View
    {
        return view('admin.policies.create', ['policy' => new Policy, 'roles' => Policy::ROLES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePolicy($request);
        $schoolId = $this->activeSchoolId();

        $policy = new Policy([
            'school_id' => $schoolId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'target_roles' => $data['target_roles'],
            'is_active' => (bool) ($data['is_active'] ?? false),
            'external_url' => $data['external_url'] ?? null,
            'created_by' => auth()->id(),
        ]);
        if ($request->hasFile('file')) {
            $policy->file_path = $request->file('file')->store('policies', 'public');
        }
        $policy->save();

        $this->notifyTargets($policy);

        return redirect()->route('admin.policies.index')
            ->with('status', __('policies.flash.created'));
    }

    public function edit(int $id): View
    {
        $policy = $this->findScoped($id);

        return view('admin.policies.edit', ['policy' => $policy, 'roles' => Policy::ROLES]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $policy = $this->findScoped($id);
        $data = $this->validatePolicy($request);

        $policy->fill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'target_roles' => $data['target_roles'],
            'is_active' => (bool) ($data['is_active'] ?? false),
            'external_url' => $data['external_url'] ?? null,
        ]);
        if ($request->hasFile('file')) {
            if ($policy->file_path) {
                Storage::disk('public')->delete($policy->file_path);
            }
            $policy->file_path = $request->file('file')->store('policies', 'public');
        }
        $policy->save();

        // New audience members get notified + an acknowledgement row (idempotent).
        $this->notifyTargets($policy);

        return redirect()->route('admin.policies.index')
            ->with('status', __('policies.flash.updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $policy = $this->findScoped($id);
        $policy->delete(); // soft delete — disappears from users immediately

        return redirect()->route('admin.policies.index')
            ->with('status', __('policies.flash.deleted'));
    }

    /** Who has / hasn't acknowledged a policy (card #105). */
    public function acknowledgements(int $id): View
    {
        $policy = $this->findScoped($id);
        $rows = $policy->acknowledgements()->with('user')->orderByDesc('read_at')->get();

        return view('admin.policies.acknowledgements', ['policy' => $policy, 'rows' => $rows]);
    }

    private function findScoped(int $id): Policy
    {
        $schoolId = $this->activeSchoolId();

        return Policy::query()
            ->when($schoolId, fn ($w) => $w->where(fn ($x) => $x->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->whereKey($id)
            ->firstOrFail();
    }

    private function validatePolicy(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_roles' => ['required', 'array', 'min:1'],
            'target_roles.*' => ['in:'.implode(',', Policy::ROLES)],
            'is_active' => ['nullable', 'boolean'],
            'external_url' => ['nullable', 'url', 'max:1024'],
            'file' => ['nullable', 'file', 'max:20480'],
        ]);
    }

    /**
     * Create an acknowledgement row + notification for every targeted user who
     * does not already have one (so editing a policy never double-notifies).
     */
    private function notifyTargets(Policy $policy): void
    {
        $targets = User::query()
            ->when($policy->school_id, fn ($w) => $w->where('school_id', $policy->school_id))
            ->whereHas('roles', fn ($r) => $r->whereIn('slug', $policy->target_roles))
            ->get(['id']);

        $existing = $policy->acknowledgements()->pluck('user_id')->all();
        $existing = array_fill_keys($existing, true);

        foreach ($targets as $user) {
            if (isset($existing[$user->id])) {
                continue; // already notified for this policy
            }

            PolicyAcknowledgement::create([
                'policy_id' => $policy->id,
                'user_id' => $user->id,
                'read_at' => null,
            ]);

            Notification::create([
                'user_id' => $user->id,
                'type' => 'announcement',
                'title' => __('policies.notify.title'),
                'body' => $policy->title,
                'icon' => 'la la-gavel',
                'color' => 'info',
                'action_url' => route('policies.my.show', $policy->id),
                'action_text' => __('policies.notify.action'),
                'data' => ['policy_id' => $policy->id],
            ]);
        }
    }
}
