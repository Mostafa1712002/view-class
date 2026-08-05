{{-- Card #338: after fixing one publish-blocker, point the admin at the next one.
     Expects $form and $currentRoute (the route name of the page including this partial). --}}
@if ($form->isEditable())
    @php
        $nextIssue = collect(app(\App\Modules\Evaluation\Services\FormCompletenessChecker::class)->detailedProblems($form))
            ->first(fn ($p) => $p['route'] !== $currentRoute);
    @endphp
    @if ($nextIssue)
        <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2 py-2">
            <span><i class="la la-info-circle"></i> {{ $nextIssue['message'] }}</span>
            <a href="{{ route($nextIssue['route'], $nextIssue['params']) }}" class="btn btn-sm btn-outline-dark">
                @lang('evaluation.publish.next_issue') <i class="la la-arrow-left"></i>
            </a>
        </div>
    @endif
@endif
