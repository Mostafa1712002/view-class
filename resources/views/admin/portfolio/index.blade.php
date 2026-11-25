@extends('layouts.admin')

@section('title', 'إدارة ملف أنجاز')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">إدارة ملف أنجاز</h1>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'teacher' ? 'active' : '' }}" href="{{ route('admin.portfolio.index', ['tab' => 'teacher']) }}">ملف أنجاز معلم</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'student' ? 'active' : '' }}" href="{{ route('admin.portfolio.index', ['tab' => 'student']) }}">ملف أنجاز طالب</a>
        </li>
    </ul>

    <form method="GET" class="card card-body mb-3">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="form-row align-items-end">
            <div class="col-md-3 mb-2"><label for="pf_q">الاسم</label>
                <input type="text" class="form-control" id="pf_q" name="q" value="{{ request('q') }}" placeholder="بحث بالاسم"></div>
            @if($tab === 'teacher')
            <div class="col-md-3 mb-2"><label for="pf_subject">المادة</label>
                <select class="form-control" id="pf_subject" name="subject_id">
                    <option value="">كل المواد</option>
                    @foreach($subjects as $s)<option value="{{ $s->id }}" @selected(request('subject_id') == $s->id)>{{ $s->name }}</option>@endforeach
                </select></div>
            @else
            <div class="col-md-3 mb-2"><label for="pf_section">الصف</label>
                <select class="form-control" id="pf_section" name="section_id">
                    <option value="">كل الصفوف</option>
                    @foreach($sections as $s)<option value="{{ $s->id }}" @selected(request('section_id') == $s->id)>{{ $s->name }}</option>@endforeach
                </select></div>
            <div class="col-md-3 mb-2"><label for="pf_class">الفصل</label>
                <select class="form-control" id="pf_class" name="class_id">
                    <option value="">كل الفصول</option>
                    @foreach($classes as $c)<option data-section="{{ $c->section_id }}" value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->name }}{{ $c->division ? ' - '.$c->division : '' }}</option>@endforeach
                </select></div>
            @endif
            <div class="col-md-3 mb-2">
                <button class="btn btn-primary">بحث</button>
                <a href="{{ route('admin.portfolio.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary">إعادة ضبط</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            @if($users->isEmpty())
                <p class="text-muted text-center py-4 mb-0">لا يوجد {{ $tab === 'teacher' ? 'معلمون' : 'طلاب' }}</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                @if($tab === 'teacher')<th>المادة</th>@else<th>الصف</th><th>الفصل</th>@endif
                                <th>البريد الإلكتروني</th>
                                <th>الهاتف</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $u)
                            <tr>
                                <td>{{ $u->name }}</td>
                                @if($tab === 'teacher')
                                <td>{{ $u->subjects->pluck('name')->unique()->join('، ') ?: '-' }}</td>
                                @else
                                @php
                                    $cls = $u->classRoom ?? $u->enrolledClasses->first();
                                    $grade = $cls?->section ?? $u->section;
                                @endphp
                                <td>{{ $grade?->name ?? '-' }}</td>
                                <td>{{ $cls ? $cls->name.($cls->division ? ' - '.$cls->division : '') : '-' }}</td>
                                @endif
                                <td>{{ $u->email }}</td>
                                <td>{{ $u->phone ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.portfolio.show', $u) }}" class="btn btn-sm btn-primary">عرض الملف</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $users->links() }}</div>
            @endif
        </div>
    </div>
</div>

@if($tab === 'student')
@push('scripts')
<script>
(function () {
    const grade = document.getElementById('pf_section'), klass = document.getElementById('pf_class');
    if (!grade || !klass) return;
    const narrow = () => Array.from(klass.options).forEach(o => {
        const hide = !!o.value && !!grade.value && o.dataset.section !== grade.value;
        o.hidden = hide; o.disabled = hide;
        if (hide && o.selected) klass.value = '';
    });
    grade.addEventListener('change', narrow);
    narrow();
})();
</script>
@endpush
@endif
@endsection
