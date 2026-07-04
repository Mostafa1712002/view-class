@extends('layouts.app')

@section('title', __('appointments.btn_book_new'))
@section('body_class', 'theme-light')

@push('styles')
<style>
    .bk-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:1.5rem; box-shadow:0 1px 2px rgba(15,23,42,.04); }
    .bk-card .card-section-title { font-size:.8rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.6px; margin-bottom:.75rem; }
    .form-label { font-weight:600; font-size:.9rem; color:#0f172a; }
    .form-control, .custom-select { border-radius:10px; border:1px solid #e2e8f0; }
    .form-control:focus, .custom-select:focus { border-color:var(--gold-300); box-shadow:0 0 0 .2rem rgba(207,160,70,.16); }
    .btn-gold { background:linear-gradient(135deg,var(--gold-300),var(--gold-500)); border:1px solid var(--gold-400); color:#fff; font-weight:600; padding:.6rem 1.4rem; border-radius:10px; display:inline-flex; align-items:center; gap:.45rem; transition:transform .15s,box-shadow .2s; }
    .btn-gold:hover { color:#fff; transform:translateY(-1px); box-shadow:0 6px 16px rgba(207,160,70,.22); }
    #people-section, #subject-section { transition:all .2s; }
    .spinner-sm { width:1rem; height:1rem; }
</style>
@endpush

@section('content')
<div class="app-content content">
<div class="content-overlay"></div>
<div class="content-wrapper">

    <div class="content-header row">
        <div class="content-header-left col-12 mb-2">
            <h3 class="content-header-title">@lang('appointments.btn_book_new')</h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') ?? '#' }}">@lang('appointments.breadcrumb_home')</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('my.appointments.index') }}">@lang('appointments.my_bookings_title')</a></li>
                        <li class="breadcrumb-item active">@lang('appointments.btn_book_new')</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 col-12">
            <div class="bk-card">
                <form method="POST" action="{{ route('my.appointments.store') }}" enctype="multipart/form-data">
                    @csrf

                    {{-- Parent: child selection --}}
                    @if($asParent)
                    <div class="mb-1">
                        <div class="card-section-title">@lang('appointments.section_child')</div>
                        <div class="form-group">
                            <label class="form-label" for="student_id">@lang('appointments.field_child') <span class="text-danger">*</span></label>
                            <select id="student_id" name="student_id" class="form-control custom-select @error('student_id') is-invalid @enderror" required>
                                <option value="">— @lang('appointments.placeholder_select_child') —</option>
                                @foreach($children as $child)
                                <option value="{{ $child->id }}" data-school="{{ $child->school_id }}" {{ (old('student_id') ?? ($selectedChildId ?? '')) == $child->id ? 'selected' : '' }}>
                                    {{ $child->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('student_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    @endif

                    {{-- Bookable Role --}}
                    <div class="mb-1">
                        <div class="card-section-title">@lang('appointments.section_role')</div>
                        <div class="form-group">
                            <label class="form-label" for="bookable_role_id">@lang('appointments.field_bookable_role') <span class="text-danger">*</span></label>
                            <select id="bookable_role_id" name="bookable_role_id" class="form-control custom-select @error('bookable_role_id') is-invalid @enderror" required>
                                <option value="">— @lang('appointments.placeholder_select_role') —</option>
                                @foreach($bookableRoles as $role)
                                <option value="{{ $role->id }}" {{ old('bookable_role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->label }}
                                </option>
                                @endforeach
                            </select>
                            @error('bookable_role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Subject selection (for subject_teacher type) --}}
                    <div id="subject-section" class="mb-1" style="display:none;">
                        <div class="form-group">
                            <label class="form-label" for="subject_id">@lang('appointments.field_subject') <span class="text-danger">*</span></label>
                            <select id="subject_id" name="subject_id" class="form-control custom-select @error('subject_id') is-invalid @enderror">
                                <option value="">— @lang('appointments.placeholder_select_subject') —</option>
                            </select>
                            @error('subject_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Target Person --}}
                    <div id="people-section" class="mb-1" style="display:none;">
                        <div class="form-group">
                            <label class="form-label" for="target_user_id">@lang('appointments.field_target_person') <span class="text-danger">*</span></label>
                            <select id="target_user_id" name="target_user_id" class="form-control custom-select @error('target_user_id') is-invalid @enderror" required>
                                <option value="">— @lang('appointments.placeholder_select_person') —</option>
                            </select>
                            @error('target_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Appointment Details --}}
                    <div class="mb-1">
                        <div class="card-section-title">@lang('appointments.section_details')</div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="appointment_date">@lang('appointments.field_appointment_date') <span class="text-danger">*</span></label>
                                    <input type="date" id="appointment_date" name="appointment_date" class="form-control @error('appointment_date') is-invalid @enderror"
                                        value="{{ old('appointment_date') }}" min="{{ date('Y-m-d') }}" required>
                                    @error('appointment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="appointment_time">@lang('appointments.field_appointment_time') <span class="text-danger">*</span></label>
                                    <input type="time" id="appointment_time" name="appointment_time" class="form-control @error('appointment_time') is-invalid @enderror"
                                        value="{{ old('appointment_time') }}" required>
                                    @error('appointment_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="contact_method">@lang('appointments.field_contact_method') <span class="text-danger">*</span></label>
                            <select id="contact_method" name="contact_method" class="form-control custom-select @error('contact_method') is-invalid @enderror" required>
                                <option value="in_person" {{ old('contact_method') === 'in_person' ? 'selected' : '' }}>@lang('appointments.mode_in_person')</option>
                                <option value="call"      {{ old('contact_method') === 'call'      ? 'selected' : '' }}>@lang('appointments.mode_call')</option>
                                <option value="virtual"   {{ old('contact_method') === 'virtual'   ? 'selected' : '' }}>@lang('appointments.mode_virtual')</option>
                            </select>
                            @error('contact_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="reason">@lang('appointments.field_reason') <span class="text-danger">*</span></label>
                            <textarea id="reason" name="reason" rows="3" class="form-control @error('reason') is-invalid @enderror" required>{{ old('reason') }}</textarea>
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="notes">@lang('appointments.field_notes')</label>
                            <textarea id="notes" name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="attachment">@lang('appointments.field_attachment')</label>
                            <div class="custom-file">
                                <input type="file" id="attachment" name="attachment" class="custom-file-input @error('attachment') is-invalid @enderror"
                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                <label class="custom-file-label" for="attachment">@lang('appointments.placeholder_choose_file')</label>
                                @error('attachment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-1">
                        <button type="submit" class="btn-gold">
                            <i class="la la-check"></i> @lang('appointments.btn_book_submit')
                        </button>
                        <a href="{{ route('my.appointments.index') }}" class="btn btn-outline-secondary">
                            @lang('appointments.btn_cancel')
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var roleSelect    = document.getElementById('bookable_role_id');
    var subjectSec    = document.getElementById('subject-section');
    var peopleSec     = document.getElementById('people-section');
    var subjectSelect = document.getElementById('subject_id');
    var personSelect  = document.getElementById('target_user_id');
    @if($asParent)
    var childSelect   = document.getElementById('student_id');
    @endif

    var currentTargetType = null;

    function clearOptions(sel, placeholder) {
        while (sel.options.length > 0) sel.remove(0);
        var opt = document.createElement('option');
        opt.value = '';
        opt.text  = placeholder;
        sel.add(opt);
    }

    function populateOptions(sel, items, nameKey) {
        nameKey = nameKey || 'name';
        items.forEach(function (item) {
            var opt = document.createElement('option');
            opt.value = item.id;
            opt.text  = item[nameKey];
            sel.add(opt);
        });
    }

    function fetchPeople(roleId, subjectId, studentId) {
        if (!roleId) { subjectSec.style.display='none'; peopleSec.style.display='none'; return; }

        var url = '{{ route("my.appointments.people") }}?bookable_role_id=' + roleId;
        if (subjectId) url += '&subject_id=' + subjectId;
        if (studentId) url += '&student_id=' + studentId;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                currentTargetType = data.target_type;

                if (data.target_type === 'subject_teacher' && !subjectId) {
                    // Show subject select first
                    clearOptions(subjectSelect, '— {{ __("appointments.placeholder_select_subject") }} —');
                    populateOptions(subjectSelect, data.subjects || []);
                    subjectSec.style.display = 'block';
                    peopleSec.style.display  = 'none';
                } else {
                    subjectSec.style.display = (data.target_type === 'subject_teacher') ? 'block' : 'none';
                    clearOptions(personSelect, '— {{ __("appointments.placeholder_select_person") }} —');
                    populateOptions(personSelect, data.people || []);
                    peopleSec.style.display = (data.people && data.people.length > 0) ? 'block' : 'none';

                    // Auto-select if single person
                    if (data.people && data.people.length === 1) {
                        personSelect.value = data.people[0].id;
                    }
                }
            })
            .catch(function () {
                peopleSec.style.display  = 'none';
                subjectSec.style.display = 'none';
            });
    }

    function getStudentId() {
        @if($asParent)
        return childSelect ? childSelect.value : '';
        @else
        return '{{ auth()->id() }}';
        @endif
    }

    @if($asParent)
    // When parent changes child, reload bookable roles via page param or AJAX
    if (childSelect) {
        childSelect.addEventListener('change', function () {
            var schoolId = this.options[this.selectedIndex]?.dataset?.school || '';
            if (schoolId) {
                window.location.href = '{{ route("my.appointments.create") }}?child=' + this.value;
            }
            // Reset cascaded selects
            clearOptions(subjectSelect, '— {{ __("appointments.placeholder_select_subject") }} —');
            clearOptions(personSelect,  '— {{ __("appointments.placeholder_select_person") }} —');
            subjectSec.style.display = 'none';
            peopleSec.style.display  = 'none';
        });
    }
    @endif

    if (roleSelect) {
        roleSelect.addEventListener('change', function () {
            clearOptions(subjectSelect, '— {{ __("appointments.placeholder_select_subject") }} —');
            clearOptions(personSelect,  '— {{ __("appointments.placeholder_select_person") }} —');
            subjectSec.style.display = 'none';
            peopleSec.style.display  = 'none';
            fetchPeople(this.value, '', getStudentId());
        });
    }

    if (subjectSelect) {
        subjectSelect.addEventListener('change', function () {
            fetchPeople(roleSelect.value, this.value, getStudentId());
        });
    }

    // On load: if old value persisted after validation error
    if (roleSelect && roleSelect.value) {
        fetchPeople(roleSelect.value, subjectSelect ? subjectSelect.value : '', getStudentId());
    }
})();
</script>
@endpush
