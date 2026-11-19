{{-- subject switcher: @include('special-programs.staff.bank._subjects', [program, subjects, subject, route]) --}}
<ul class="nav nav-pills mb-3">
    @foreach($subjects as $s)
        <li class="nav-item"><a class="nav-link {{ $s->id === $subject->id ? 'active' : '' }}" href="{{ route($route, [$program, 'subject' => $s->id]) }}">{{ $s->name_ar }}</a></li>
    @endforeach
</ul>
