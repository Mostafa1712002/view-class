{{-- @include('special-programs.partials.program-tabs', ['program' => $program, 'active' => 'enrollments']) --}}
@php
    $tabDefs = [
        'enrollments' => ['manage.special-programs.enrollments.index', 'special_programs.view'],
        'groups' => ['manage.special-programs.groups.index', 'special_programs.manage'],
        'teachers' => ['manage.special-programs.teachers.index', 'special_programs.manage'],
        'exams' => ['manage.special-programs.exams.index', 'special_programs.exams'],
        'bank' => ['manage.special-programs.bank.index', 'special_programs.questions'],
        'reports' => ['manage.special-programs.reports.index', 'special_programs.reports'],
        'settings' => ['manage.special-programs.settings.index', 'special_programs.settings'],
    ];
@endphp
<ul class="nav nav-tabs mb-3">
    @foreach($tabDefs as $key => [$routeName, $perm])
        @if(Route::has($routeName) && auth()->user()->canDo($perm))
            <li class="nav-item"><a class="nav-link {{ ($active ?? '') === $key ? 'active' : '' }}" href="{{ route($routeName, $program) }}">{{ __('special_programs/staff.tabs.'.$key) }}</a></li>
        @endif
    @endforeach
</ul>
