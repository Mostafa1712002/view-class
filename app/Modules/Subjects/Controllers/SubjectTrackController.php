<?php

namespace App\Modules\Subjects\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SubjectTrack;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectTrackController extends Controller
{
    use HasSchoolScope;

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $search   = trim((string) $request->query('q', ''));

        $query = SubjectTrack::query()->forSchool($schoolId);

        if ($search !== '') {
            $needle = '%' . $search . '%';
            $query->where(function ($q) use ($needle) {
                $q->where('name', 'like', $needle)
                  ->orWhere('name_en', 'like', $needle);
            });
        }

        $tracks = $query->orderBy('sort_order')->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.subjects.tracks.index', compact('tracks', 'search'));
    }

    public function create(): View
    {
        $track = new SubjectTrack(['is_active' => true, 'sort_order' => 0]);
        return view('admin.subjects.tracks.create', compact('track'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTrack($request);
        $data['school_id'] = $this->activeSchoolId();

        SubjectTrack::create($data);

        return redirect()
            ->route('admin.subject-tracks.index')
            ->with('success', __('subject_tracks.flash.created'));
    }

    public function edit(int $id): View
    {
        $track = $this->findOrFail($id);
        return view('admin.subjects.tracks.edit', compact('track'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $track = $this->findOrFail($id);
        $data = $this->validateTrack($request);
        $track->fill($data)->save();

        return redirect()
            ->route('admin.subject-tracks.index')
            ->with('success', __('subject_tracks.flash.updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $track = $this->findOrFail($id);
        $track->delete();

        return redirect()
            ->route('admin.subject-tracks.index')
            ->with('success', __('subject_tracks.flash.deleted'));
    }

    private function findOrFail(int $id): SubjectTrack
    {
        $schoolId = $this->activeSchoolId();
        $query = SubjectTrack::query()->whereKey($id);

        if ($schoolId !== null) {
            $query->where(function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId)->orWhereNull('school_id');
            });
        }

        $track = $query->first();
        abort_if(! $track, 404);
        return $track;
    }

    private function validateTrack(Request $request): array
    {
        return $request->validate([
            'name'       => ['required', 'string', 'max:120'],
            'name_en'    => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'  => ['nullable', 'boolean'],
            'notes'      => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
