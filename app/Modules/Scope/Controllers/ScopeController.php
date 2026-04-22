<?php

namespace App\Modules\Scope\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Scope\Actions\SetScopeAction;
use App\Modules\Scope\Repositories\Contracts\ScopeRepository;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ScopeController extends Controller
{
    public function options(Request $request, ScopeRepository $repo): JsonResponse
    {
        $user = $request->user();
        $companyId = $request->integer('company_id') ?: null;
        $schoolId = $request->integer('school_id') ?: null;

        return ApiResponse::ok([
            'companies' => $repo->companiesFor($user),
            'schools' => $repo->schoolsFor($user, $companyId),
            'academic_years' => $repo->yearsFor($user, $schoolId),
            'current' => session('scope', [
                'company_id' => optional($user->school)->educational_company_id,
                'school_id' => $user->school_id,
                'academic_year_id' => null,
            ]),
        ]);
    }

    public function set(Request $request, SetScopeAction $action): RedirectResponse
    {
        $action->execute($request->user(), $request->only(['company_id', 'school_id', 'academic_year_id']));
        return back();
    }
}
