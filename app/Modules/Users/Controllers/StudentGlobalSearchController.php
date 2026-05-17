<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cross-school student search.
 *
 * System admin (super-admin) can find any student across ALL schools
 * without opening each school separately. Filters: username, email,
 * national id, phone, passport number. Passport lives on student_profiles
 * so we join that table to keep it in the same WHERE pass.
 *
 * Card: Trello #59 — بحث في كل المدارس
 */
class StudentGlobalSearchController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        abort_unless($user && $user->isSuperAdmin(), 403, __('school_search.super_admin_only'));

        $filters = [
            'username' => trim((string) $request->input('username')),
            'email' => trim((string) $request->input('email')),
            'national_id' => trim((string) $request->input('national_id')),
            'phone' => trim((string) $request->input('phone')),
            'passport' => trim((string) $request->input('passport')),
            'advanced' => $request->boolean('advanced'),
            'auto' => $request->boolean('auto'),
        ];

        $hasAny = (bool) array_filter([
            $filters['username'],
            $filters['email'],
            $filters['national_id'],
            $filters['phone'],
            $filters['passport'],
        ]);

        $students = null;
        if ($hasAny) {
            $students = $this->runSearch($filters);
        }

        return view('admin.users.students.global_search', [
            'filters' => $filters,
            'students' => $students,
            'hasAny' => $hasAny,
        ]);
    }

    /**
     * Build the cross-school query.
     *
     * - Joins student_profiles so passport_number is searchable.
     * - Each filled filter narrows the result set (AND semantics).
     * - NO school_id scope — super-admin only.
     */
    private function runSearch(array $filters)
    {
        $q = User::query()
            ->whereHas('roles', fn ($r) => $r->where('slug', 'student'))
            ->leftJoin('student_profiles', 'student_profiles.user_id', '=', 'users.id')
            ->with(['school', 'section', 'classRoom'])
            ->select('users.*');

        if ($filters['username'] !== '') {
            $q->where('users.username', 'like', '%'.$filters['username'].'%');
        }
        if ($filters['email'] !== '') {
            $q->where('users.email', 'like', '%'.$filters['email'].'%');
        }
        if ($filters['national_id'] !== '') {
            $q->where('users.national_id', 'like', '%'.$filters['national_id'].'%');
        }
        if ($filters['phone'] !== '') {
            $q->where('users.phone', 'like', '%'.$filters['phone'].'%');
        }
        if ($filters['passport'] !== '') {
            $q->where('student_profiles.passport_number', 'like', '%'.$filters['passport'].'%');
        }

        return $q->orderBy('users.name')
            ->paginate(25)
            ->withQueryString();
    }
}
