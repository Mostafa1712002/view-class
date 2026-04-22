<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Actions\GetContentStatsAction;
use App\Modules\Dashboard\Actions\GetDashboardStatsAction;
use App\Modules\Dashboard\Actions\GetInteractionRatesAction;
use App\Modules\Dashboard\Actions\GetMostActiveAction;
use App\Modules\Dashboard\Actions\GetWeeklyActivityAction;
use App\Modules\Dashboard\Repositories\Contracts\DashboardStatsRepository;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function stats(Request $request, GetDashboardStatsAction $action): JsonResponse
    {
        return ApiResponse::ok($action->execute($request->user()?->school_id));
    }

    public function interactionRates(Request $request, GetInteractionRatesAction $action): JsonResponse
    {
        return ApiResponse::ok($action->execute($request->user()?->school_id));
    }

    public function contentStats(Request $request, GetContentStatsAction $action): JsonResponse
    {
        return ApiResponse::ok($action->execute($request->user()?->school_id));
    }

    public function variousStats(Request $request, DashboardStatsRepository $repo): JsonResponse
    {
        return ApiResponse::ok($repo->variousStats($request->user()?->school_id));
    }

    public function weeklyAbsence(Request $request, DashboardStatsRepository $repo): JsonResponse
    {
        return ApiResponse::ok($repo->weeklyAbsenceRate($request->user()?->school_id));
    }

    public function mostActive(Request $request, GetMostActiveAction $action): JsonResponse
    {
        $user = $request->user();
        return ApiResponse::ok($action->execute(
            $user?->school_id,
            optional($user?->school)->educational_company_id,
        ));
    }

    public function weeklyActivity(Request $request, GetWeeklyActivityAction $action): JsonResponse
    {
        return ApiResponse::ok($action->execute($request->user()?->school_id));
    }
}
