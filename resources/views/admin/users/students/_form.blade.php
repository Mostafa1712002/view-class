@csrf

@php
    $val = function ($key, $modelKey = null) use ($student, $profile) {
        $modelKey = $modelKey ?? $key;
        if (in_array($key, ['name','username','email','national_id','gender','date_of_birth','phone','address','section_id','class_room_id','name_en'])) {
            return old($key, $student->{$modelKey} ?? '');
        }
        return old($key, $profile->{$modelKey} ?? '');
    };
@endphp

<div class="student-form">
    @php
        $sections_data = [
            [
                'icon' => 'la la-id-card', 'title' => __('users.student_basic_info'),
                'fields' => [
                    ['name' => 'name', 'label' => __('users.student_full_name'), 'required' => true, 'col' => 'col-md-6'],
                    ['name' => 'username', 'label' => __('users.username'), 'required' => true, 'col' => 'col-md-6'],
                    ['name' => 'password', 'label' => __('users.password'), 'type' => 'password', 'col' => 'col-md-6', 'hint' => __('users.password_hint')],
                    ['name' => 'national_id', 'label' => __('users.national_id'), 'col' => 'col-md-6'],
                    ['name' => 'first_name', 'label' => __('users.student_first_name'), 'col' => 'col-md-3'],
                    ['name' => 'father_name', 'label' => __('users.student_father_name'), 'col' => 'col-md-3'],
                    ['name' => 'grandfather_name', 'label' => __('users.student_grandfather_name'), 'col' => 'col-md-3'],
                    ['name' => 'last_name', 'label' => __('users.student_last_name'), 'col' => 'col-md-3'],
                ],
            ],
            [
                'icon' => 'la la-globe', 'title' => __('users.student_name_en_section'),
                'fields' => [
                    ['name' => 'first_name_en', 'label' => __('users.student_first_name_en'), 'col' => 'col-md-3'],
                    ['name' => 'father_name_en', 'label' => __('users.student_father_name_en'), 'col' => 'col-md-3'],
                    ['name' => 'grandfather_name_en', 'label' => __('users.student_grandfather_name_en'), 'col' => 'col-md-3'],
                    ['name' => 'last_name_en', 'label' => __('users.student_last_name_en'), 'col' => 'col-md-3'],
                ],
            ],
            [
                'icon' => 'la la-info-circle', 'title' => __('users.student_extra_info'),
                'fields' => [
                    ['name' => 'email', 'label' => __('users.email'), 'type' => 'email', 'col' => 'col-md-6'],
                    ['name' => 'fingerprint_id', 'label' => __('users.student_fingerprint'), 'col' => 'col-md-3'],
                    ['name' => 'seat_number', 'label' => __('users.student_seat_number'), 'col' => 'col-md-3'],
                    ['name' => 'passport_number', 'label' => __('users.student_passport'), 'col' => 'col-md-3'],
                    ['name' => 'nationality', 'label' => __('users.student_nationality'), 'type' => 'select', 'col' => 'col-md-3',
                        'options' => ['' => __('users.select_nationality')] + array_combine(config('countries_ar'), config('countries_ar'))],
                    ['name' => 'academic_id', 'label' => __('users.student_academic_id'), 'col' => 'col-md-3'],
                    ['name' => 'gender', 'label' => __('users.gender'), 'type' => 'select', 'col' => 'col-md-3',
                        'options' => ['' => '—', 'male' => __('users.gender_male'), 'female' => __('users.gender_female')]],
                    ['name' => 'date_of_birth', 'label' => __('users.date_of_birth'), 'type' => 'date', 'col' => 'col-md-3'],
                    ['name' => 'birth_place', 'label' => __('users.student_birth_place'), 'col' => 'col-md-3'],
                    ['name' => 'admission_year', 'label' => __('users.student_admission_year'), 'type' => 'number', 'col' => 'col-md-3'],
                ],
            ],
            [
                'icon' => 'la la-graduation-cap', 'title' => __('users.student_school_info'),
                'fields' => [
                    ['name' => 'section_id', 'label' => __('users.grade_level'), 'type' => 'select', 'col' => 'col-md-3',
                        'attrs' => 'id="student-section-select"',
                        'options' => ['' => __('users.select_section')] + ($sections->pluck('name', 'id')->toArray())],
                    ['name' => 'class_room_id', 'label' => __('users.class'), 'type' => 'select', 'col' => 'col-md-3',
                        'attrs' => 'id="student-class-select"',
                        'options' => ['' => __('users.select_class')] + ($classes->pluck('name', 'id')->toArray())],
                    ['name' => 'previous_school', 'label' => __('users.student_previous_school'), 'col' => 'col-md-3'],
                    ['name' => 'enrollment_date', 'label' => __('users.student_enrollment_date'), 'type' => 'date', 'col' => 'col-md-3'],
                ],
            ],
            [
                'icon' => 'la la-users', 'title' => __('users.student_family_info'),
                'fields' => [
                    ['name' => 'father_national_id', 'label' => __('users.student_father_national_id'), 'col' => 'col-md-4'],
                    ['name' => 'mother_national_id', 'label' => __('users.student_mother_national_id'), 'col' => 'col-md-4'],
                    ['name' => 'mother_full_name', 'label' => __('users.student_mother_name'), 'col' => 'col-md-4'],
                ],
            ],
            [
                'icon' => 'la la-phone', 'title' => __('users.student_contact_info'),
                'fields' => [
                    ['name' => 'phone', 'label' => __('users.phone'), 'col' => 'col-md-4'],
                    ['name' => 'home_phone', 'label' => __('users.student_home_phone'), 'col' => 'col-md-4'],
                    ['name' => 'address', 'label' => __('users.student_address'), 'col' => 'col-md-12', 'type' => 'textarea'],
                ],
            ],
        ];
    @endphp

    @foreach($sections_data as $sec)
        <div class="form-section card mb-3">
            <div class="card-header d-flex align-items-center gap-1">
                <span class="section-icon"><i class="{{ $sec['icon'] }}"></i></span>
                <h5 class="m-0">{{ $sec['title'] }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($sec['fields'] as $f)
                        @php
                            $type = $f['type'] ?? 'text';
                            $required = !empty($f['required']);
                            $value = $val($f['name']);
                        @endphp
                        <div class="form-group {{ $f['col'] }}">
                            <label class="form-label">
                                {{ $f['label'] }}@if($required) <span class="text-danger">*</span>@endif
                            </label>
                            @if($type === 'select')
                                <select name="{{ $f['name'] }}" class="form-control" {{ $required ? 'required' : '' }} {!! $f['attrs'] ?? '' !!}>
                                    @foreach($f['options'] as $optVal => $optLabel)
                                        @if($f['name'] === 'class_room_id' && $optVal !== '')
                                            @php $optSection = optional($classes->firstWhere('id', $optVal))->section_id; @endphp
                                            <option value="{{ $optVal }}" data-section="{{ $optSection }}" @selected((string)$value === (string)$optVal)>{{ $optLabel }}</option>
                                        @else
                                            <option value="{{ $optVal }}" @selected((string)$value === (string)$optVal)>{{ $optLabel }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            @elseif($type === 'textarea')
                                <textarea name="{{ $f['name'] }}" class="form-control" rows="2">{{ $value }}</textarea>
                            @elseif($type === 'date')
                                <input type="date" name="{{ $f['name'] }}" class="form-control"
                                       value="{{ $value instanceof \Carbon\Carbon ? $value->format('Y-m-d') : (is_object($value) && method_exists($value,'format') ? $value->format('Y-m-d') : $value) }}" />
                            @elseif($type === 'password')
                                <input type="password" name="{{ $f['name'] }}" class="form-control" autocomplete="new-password" />
                                @isset($f['hint'])<small class="form-text text-muted">{{ $f['hint'] }}</small>@endisset
                            @else
                                <input type="{{ $type }}" name="{{ $f['name'] }}" class="form-control" value="{{ $value }}" {{ $required ? 'required' : '' }} />
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

    <div class="form-section card mb-3">
        <div class="card-header d-flex align-items-center gap-1">
            <span class="section-icon"><i class="la la-image"></i></span>
            <h5 class="m-0">@lang('users.student_photo_info')</h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-2">
                    @php $avatar = $student->avatar ?? $student->profile_picture ?? null; @endphp
                    <div class="student-avatar-large">
                        @if($avatar)
                            <img src="{{ asset('storage/'.$avatar) }}" alt="" />
                        @else
                            <i class="la la-user"></i>
                        @endif
                    </div>
                </div>
                <div class="col-md-10">
                    <label class="form-label">@lang('users.student_profile_picture')</label>
                    <input type="file" name="profile_picture" class="form-control" disabled />
                    <small class="form-text text-muted">@lang('users.student_coming_soon')</small>
                </div>
            </div>
        </div>
    </div>

    <p class="text-muted small"><i class="la la-info-circle"></i> @lang('users.student_required_hint')</p>

    <div class="d-flex flex-wrap gap-1 mt-3 sticky-actions">
        <button type="submit" class="btn add-student-btn"><i class="la la-save"></i> @lang('users.save')</button>
        <a href="{{ route('admin.users.students.index') }}" class="btn btn-soft"><i class="la la-arrow-left"></i> @lang('users.cancel')</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger mt-3">
            <strong>@lang('users.no_results') :</strong>
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
</div>

<script>
(function () {
    var sectionSel = document.getElementById('student-section-select');
    var classSel = document.getElementById('student-class-select');
    if (!sectionSel || !classSel) return;

    var allOptions = Array.prototype.slice.call(classSel.options).map(function (o) {
        return { value: o.value, label: o.textContent, section: o.getAttribute('data-section') || '' };
    });
    var placeholder = allOptions.length && allOptions[0].value === '' ? allOptions[0] : null;

    function rebuild() {
        var sid = sectionSel.value;
        var current = classSel.value;
        while (classSel.firstChild) { classSel.removeChild(classSel.firstChild); }
        if (placeholder) {
            classSel.appendChild(new Option(placeholder.label, ''));
        }
        var keepCurrent = false;
        allOptions.forEach(function (o) {
            if (o.value === '') return;
            if (!sid || o.section === sid) {
                var opt = new Option(o.label, o.value);
                opt.setAttribute('data-section', o.section);
                if (o.value === current) { opt.selected = true; keepCurrent = true; }
                classSel.appendChild(opt);
            }
        });
        if (!keepCurrent && placeholder) { classSel.value = ''; }
    }

    sectionSel.addEventListener('change', rebuild);
    // On load, narrow the class list to the pre-selected section (edit page).
    rebuild();
})();
</script>
