<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Exam;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q', '');
        $type = $request->get('type', 'all');

        if (strlen($query) < 2) {
            return response()->json([
                'results' => [],
                'message' => 'يرجى إدخال كلمتين على الأقل للبحث',
            ]);
        }

        $user = Auth::user();
        $schoolId = $user->school_id;
        $results = [];

        if ($type === 'all' || $type === 'users') {
            $results['users'] = $this->searchUsers($query, $schoolId, $user);
        }

        if ($type === 'all' || $type === 'classes') {
            $results['classes'] = $this->searchClasses($query, $schoolId);
        }

        if ($type === 'all' || $type === 'subjects') {
            $results['subjects'] = $this->searchSubjects($query, $schoolId);
        }

        if ($type === 'all' || $type === 'exams') {
            $results['exams'] = $this->searchExams($query, $schoolId);
        }

        return response()->json([
            'results' => $results,
            'query' => $query,
        ]);
    }

    public function quick(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $user = Auth::user();
        $schoolId = $user->school_id;
        $results = [];

        $students = User::where('school_id', $schoolId)
            ->where('name', 'like', "%{$query}%")
            ->whereHas('roles', fn($q) => $q->where('slug', 'student'))
            ->take(5)
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'type' => 'طالب',
                'icon' => 'la-user-graduate',
                'url' => route('manage.users.show', $u),
            ]);

        $teachers = User::where('school_id', $schoolId)
            ->where('name', 'like', "%{$query}%")
            ->whereHas('roles', fn($q) => $q->where('slug', 'teacher'))
            ->take(3)
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'type' => 'معلم',
                'icon' => 'la-chalkboard-teacher',
                'url' => route('manage.users.show', $u),
            ]);

        $classes = ClassRoom::whereHas('section', fn($q) => $q->where('school_id', $schoolId))
            ->where('name', 'like', "%{$query}%")
            ->take(3)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'type' => 'فصل',
                'icon' => 'la-users',
                'url' => route('manage.classes.show', $c),
            ]);

        $subjects = Subject::where('school_id', $schoolId)
            ->where('name', 'like', "%{$query}%")
            ->take(3)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'type' => 'مادة',
                'icon' => 'la-book',
                'url' => route('manage.subjects.show', $s),
            ]);

        $results = $students->concat($teachers)->concat($classes)->concat($subjects);

        return response()->json(['results' => $results]);
    }

    private function searchUsers($query, $schoolId, $currentUser)
    {
        $usersQuery = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%");

        if (!$currentUser->isSuperAdmin()) {
            $usersQuery->where('school_id', $schoolId);
        }

        return $usersQuery->with('roles')
            ->take(20)
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role_name,
                'avatar' => $u->avatar ? asset('storage/' . $u->avatar) : null,
            ]);
    }

    private function searchClasses($query, $schoolId)
    {
        return ClassRoom::whereHas('section', fn($q) => $q->where('school_id', $schoolId))
            ->where('name', 'like', "%{$query}%")
            ->with(['section'])
            ->take(10)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'section' => $c->section->name ?? '',
                'students_count' => $c->students()->count(),
            ]);
    }

    private function searchSubjects($query, $schoolId)
    {
        return Subject::where('school_id', $schoolId)
            ->where('name', 'like', "%{$query}%")
            ->take(10)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'code' => $s->code ?? '',
            ]);
    }

    private function searchExams($query, $schoolId)
    {
        return Exam::where('school_id', $schoolId)
            ->where('title', 'like', "%{$query}%")
            ->with(['subject'])
            ->take(10)
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'subject' => $e->subject->name ?? '',
                'date' => $e->start_date->format('Y/m/d'),
                'status' => $e->status_label,
            ]);
    }
}
