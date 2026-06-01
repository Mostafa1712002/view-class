@extends('layouts.app')

@section('title', __('schools.migrate_title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            @lang('schools.migrate_title') — {{ app()->getLocale() === 'en' ? ($school->name_en ?: $school->name_ar) : ($school->name_ar ?: $school->name) }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.index') }}">@lang('schools.title')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.academic-years.index', $school) }}">@lang('schools.academic_years')</a></li>
                <li class="breadcrumb-item active">@lang('schools.migrate_title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card mb-3">
        <div class="card-body">
            <div class="form-group">
                <label class="form-label"><strong>@lang('schools.migrate_type')</strong></label>
                <select id="migrate-type" class="form-control" style="max-width:320px;">
                    <option value="classes">@lang('schools.migrate_type_classes')</option>
                    <option value="students">@lang('schools.migrate_type_students')</option>
                </select>
            </div>
            <p class="text-muted mb-1"><i class="la la-info-circle"></i> @lang('schools.migrate_timeslots_note')</p>
            <p class="text-muted mb-0"><i class="la la-info-circle"></i> @lang('schools.migrate_lessons_note')</p>
        </div>
    </div>

    {{-- Classes migration --}}
    <div class="card mb-3 migrate-block" data-type="classes">
        <div class="card-header"><h5 class="mb-0">@lang('schools.migrate_type_classes')</h5></div>
        <div class="card-body">
            <form action="{{ route('admin.schools.academic-years.migrate.classes', $school) }}" method="POST"
                  onsubmit="return confirm(@json(__('schools.migrate_confirm')))">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">@lang('schools.migrate_source_year')</label>
                        <select name="source_year_id" class="form-control" required>
                            @foreach($years as $y)
                                <option value="{{ $y->id }}">{{ $y->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">@lang('schools.migrate_destination_year')</label>
                        <select name="destination_year_id" class="form-control" required>
                            @foreach($years as $y)
                                <option value="{{ $y->id }}" @selected($y->is_current)>{{ $y->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100"><i class="la la-share"></i> @lang('schools.migrate_run')</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Students migration (grade promotion) --}}
    <div class="card mb-3 migrate-block" data-type="students" style="display:none;">
        <div class="card-header"><h5 class="mb-0">@lang('schools.migrate_type_students')</h5></div>
        <div class="card-body">
            <form action="{{ route('admin.schools.academic-years.migrate.students', $school) }}" method="POST"
                  onsubmit="return confirm(@json(__('schools.migrate_confirm')))">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">@lang('schools.migrate_source')</h6>
                        <div class="form-group">
                            <label class="form-label">@lang('schools.migrate_source_year')</label>
                            <select class="form-control year-pick" data-side="src" required>
                                <option value="">—</option>
                                @foreach($years as $y)<option value="{{ $y->id }}">{{ $y->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">@lang('schools.migrate_grade')</label>
                            <select class="form-control section-pick" data-side="src" required>
                                <option value="">—</option>
                                @foreach($sections as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">@lang('schools.migrate_class')</label>
                            <select name="source_class_id" class="form-control class-pick" data-side="src" required>
                                <option value="">@lang('schools.migrate_pick_class')</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">@lang('schools.migrate_destination')</h6>
                        <div class="form-group">
                            <label class="form-label">@lang('schools.migrate_destination_year')</label>
                            <select class="form-control year-pick" data-side="dst" required>
                                <option value="">—</option>
                                @foreach($years as $y)<option value="{{ $y->id }}" @selected($y->is_current)>{{ $y->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">@lang('schools.migrate_grade')</label>
                            <select class="form-control section-pick" data-side="dst" required>
                                <option value="">—</option>
                                @foreach($sections as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">@lang('schools.migrate_class')</label>
                            <select name="destination_class_id" class="form-control class-pick" data-side="dst" required>
                                <option value="">@lang('schools.migrate_pick_class')</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary"><i class="la la-share"></i> @lang('schools.migrate_run')</button>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var CLASSES = @json($classes);
    var T = { pick: @json(__('schools.migrate_pick_class')), none: @json(__('schools.migrate_no_classes')) };

    // Type toggle
    var typeSel = document.getElementById('migrate-type');
    function applyType() {
        document.querySelectorAll('.migrate-block').forEach(function (b) {
            b.style.display = (b.dataset.type === typeSel.value) ? '' : 'none';
        });
    }
    typeSel.addEventListener('change', applyType);
    applyType();

    // Dependent class dropdowns for the students form
    function classOptions(side) {
        var year = document.querySelector('.year-pick[data-side="' + side + '"]').value;
        var section = document.querySelector('.section-pick[data-side="' + side + '"]').value;
        var target = document.querySelector('.class-pick[data-side="' + side + '"]');
        target.replaceChildren();
        if (!year || !section) {
            var o = document.createElement('option'); o.value = ''; o.textContent = T.pick; target.appendChild(o);
            return;
        }
        var matches = CLASSES.filter(function (c) {
            return String(c.academic_year_id) === String(year) && String(c.section_id) === String(section);
        });
        if (!matches.length) {
            var n = document.createElement('option'); n.value = ''; n.textContent = T.none; target.appendChild(n);
            return;
        }
        var ph = document.createElement('option'); ph.value = ''; ph.textContent = T.pick; target.appendChild(ph);
        matches.forEach(function (c) {
            var o = document.createElement('option');
            o.value = c.id;
            o.textContent = c.name + ' (' + (c.students_count || 0) + ')';
            target.appendChild(o);
        });
    }
    ['src', 'dst'].forEach(function (side) {
        document.querySelector('.year-pick[data-side="' + side + '"]').addEventListener('change', function () { classOptions(side); });
        document.querySelector('.section-pick[data-side="' + side + '"]').addEventListener('change', function () { classOptions(side); });
    });
})();
</script>
@endsection
