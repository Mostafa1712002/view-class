<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\File;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        $query = File::with(['uploader', 'subject', 'classRoom', 'academicYear']);

        if (!$user->isSuperAdmin()) {
            $query->where('school_id', $user->school_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('original_name', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        $files = $query->latest()->paginate(20)->withQueryString();

        $subjects = Subject::when(!$user->isSuperAdmin(), fn($q) => $q->where('school_id', $user->school_id))
            ->orderBy('name')
            ->get();

        $classes = ClassRoom::when(!$user->isSuperAdmin(), function ($q) use ($user) {
            $q->whereHas('section', fn($sq) => $sq->where('school_id', $user->school_id));
        })->with('section')->orderBy('name')->get();

        return view('admin.files.index', compact('files', 'subjects', 'classes'));
    }

    public function create(): View
    {
        $user = auth()->user();

        $subjects = Subject::when(!$user->isSuperAdmin(), fn($q) => $q->where('school_id', $user->school_id))
            ->orderBy('name')
            ->get();

        $classes = ClassRoom::when(!$user->isSuperAdmin(), function ($q) use ($user) {
            $q->whereHas('section', fn($sq) => $sq->where('school_id', $user->school_id));
        })->with('section')->orderBy('name')->get();

        $academicYears = AcademicYear::orderByDesc('is_current')->orderByDesc('start_date')->get();

        return view('admin.files.create', compact('subjects', 'classes', 'academicYears'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB
            'name' => 'required|string|max:255',
            'type' => 'required|in:material,assignment,resource,other',
            'subject_id' => 'nullable|exists:subjects,id',
            'class_id' => 'nullable|exists:classes,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
        ]);

        $uploadedFile = $request->file('file');
        $path = $uploadedFile->store('files/' . date('Y/m'), 'public');

        File::create([
            'school_id' => auth()->user()->school_id,
            'uploaded_by' => auth()->id(),
            'name' => $request->name,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'path' => $path,
            'disk' => 'public',
            'mime_type' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
            'type' => $request->type,
            'subject_id' => $request->subject_id,
            'class_id' => $request->class_id,
            'academic_year_id' => $request->academic_year_id,
            'description' => $request->description,
            'is_public' => $request->boolean('is_public'),
        ]);

        return redirect()->route('admin.files.index')
            ->with('success', 'تم رفع الملف بنجاح');
    }

    public function show(File $file): View
    {
        $this->authorize($file);

        $file->load(['uploader', 'subject', 'classRoom', 'academicYear']);

        return view('admin.files.show', compact('file'));
    }

    public function edit(File $file): View
    {
        $this->authorize($file);

        $user = auth()->user();

        $subjects = Subject::when(!$user->isSuperAdmin(), fn($q) => $q->where('school_id', $user->school_id))
            ->orderBy('name')
            ->get();

        $classes = ClassRoom::when(!$user->isSuperAdmin(), function ($q) use ($user) {
            $q->whereHas('section', fn($sq) => $sq->where('school_id', $user->school_id));
        })->with('section')->orderBy('name')->get();

        $academicYears = AcademicYear::orderByDesc('is_current')->orderByDesc('start_date')->get();

        return view('admin.files.edit', compact('file', 'subjects', 'classes', 'academicYears'));
    }

    public function update(Request $request, File $file): RedirectResponse
    {
        $this->authorize($file);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:material,assignment,resource,other',
            'subject_id' => 'nullable|exists:subjects,id',
            'class_id' => 'nullable|exists:classes,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
        ]);

        $file->update([
            'name' => $request->name,
            'type' => $request->type,
            'subject_id' => $request->subject_id,
            'class_id' => $request->class_id,
            'academic_year_id' => $request->academic_year_id,
            'description' => $request->description,
            'is_public' => $request->boolean('is_public'),
        ]);

        return redirect()->route('admin.files.index')
            ->with('success', 'تم تحديث الملف بنجاح');
    }

    public function destroy(File $file): RedirectResponse
    {
        $this->authorize($file);

        Storage::disk($file->disk)->delete($file->path);
        $file->delete();

        return redirect()->route('admin.files.index')
            ->with('success', 'تم حذف الملف بنجاح');
    }

    public function download(File $file): StreamedResponse
    {
        $this->authorize($file);

        $file->incrementDownloads();

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    private function authorize(File $file): void
    {
        $user = auth()->user();

        if (!$user->isSuperAdmin() && $file->school_id !== $user->school_id) {
            abort(403);
        }
    }
}
