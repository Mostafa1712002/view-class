@csrf
@php
    $tp = $teacher->teacherProfile ?? null;
@endphp

<div class="card mb-3 border-0 shadow-sm">
    <div class="card-header bg-white"><strong>@lang('users.teacher_form_basic')</strong></div>
    <div class="card-body">

        {{-- Arabic name parts --}}
        <h6 class="text-muted mb-2"><i class="la la-language"></i> @lang('users.teacher_form_name_ar')</h6>
        <div class="row">
            <div class="form-group col-md-3">
                <label>@lang('users.first_name_ar') <span class="text-danger">*</span></label>
                <input type="text" name="first_name_ar" class="form-control"
                       value="{{ old('first_name_ar', $tp->first_name_ar ?? '') }}" required />
            </div>
            <div class="form-group col-md-3">
                <label>@lang('users.father_name_ar')</label>
                <input type="text" name="father_name_ar" class="form-control"
                       value="{{ old('father_name_ar', $tp->father_name_ar ?? '') }}" />
            </div>
            <div class="form-group col-md-3">
                <label>@lang('users.grandfather_name_ar')</label>
                <input type="text" name="grandfather_name_ar" class="form-control"
                       value="{{ old('grandfather_name_ar', $tp->grandfather_name_ar ?? '') }}" />
            </div>
            <div class="form-group col-md-3">
                <label>@lang('users.family_name_ar') <span class="text-danger">*</span></label>
                <input type="text" name="family_name_ar" class="form-control"
                       value="{{ old('family_name_ar', $tp->family_name_ar ?? '') }}" required />
            </div>
        </div>

        <hr class="my-3">

        {{-- English name parts --}}
        <h6 class="text-muted mb-2"><i class="la la-language"></i> @lang('users.teacher_form_name_en')</h6>
        <div class="row">
            <div class="form-group col-md-3">
                <label>@lang('users.first_name_en')</label>
                <input type="text" name="first_name_en" class="form-control" dir="ltr"
                       value="{{ old('first_name_en', $tp->first_name_en ?? '') }}" />
            </div>
            <div class="form-group col-md-3">
                <label>@lang('users.father_name_en')</label>
                <input type="text" name="father_name_en" class="form-control" dir="ltr"
                       value="{{ old('father_name_en', $tp->father_name_en ?? '') }}" />
            </div>
            <div class="form-group col-md-3">
                <label>@lang('users.grandfather_name_en')</label>
                <input type="text" name="grandfather_name_en" class="form-control" dir="ltr"
                       value="{{ old('grandfather_name_en', $tp->grandfather_name_en ?? '') }}" />
            </div>
            <div class="form-group col-md-3">
                <label>@lang('users.family_name_en')</label>
                <input type="text" name="family_name_en" class="form-control" dir="ltr"
                       value="{{ old('family_name_en', $tp->family_name_en ?? '') }}" />
            </div>
        </div>

        <hr class="my-3">

        {{-- Identity & work --}}
        <h6 class="text-muted mb-2"><i class="la la-id-card"></i> @lang('users.teacher_form_identity')</h6>
        <div class="row">
            @if(($schools ?? collect())->isNotEmpty())
            <div class="form-group col-md-4">
                <label>@lang('users.school') <span class="text-danger">*</span></label>
                @php $selSchool = old('school_id', $teacher->school_id ?? ''); @endphp
                <select name="school_id" class="form-control" required>
                    <option value="">@lang('users.select_school')</option>
                    @foreach($schools as $s)
                        <option value="{{ $s->id }}" @selected((string)$selSchool === (string)$s->id)>{{ $s->name_ar ?: $s->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="form-group col-md-4">
                <label>@lang('users.passport_number')</label>
                <input type="text" name="passport_number" class="form-control"
                       value="{{ old('passport_number', $tp->passport_number ?? '') }}" />
            </div>
            <div class="form-group col-md-4">
                <label>@lang('users.employee_id')</label>
                <input type="text" name="employee_id" class="form-control"
                       value="{{ old('employee_id', $teacher->employee_id ?? '') }}" />
            </div>
            <div class="form-group col-md-4">
                <label>@lang('users.national_id') <span class="text-danger">*</span></label>
                <input type="text" name="national_id" class="form-control"
                       value="{{ old('national_id', $teacher->national_id ?? '') }}" required />
            </div>
            <div class="form-group col-md-6">
                <label>@lang('users.username') <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control" dir="ltr"
                       value="{{ old('username', $teacher->username ?? '') }}" required />
            </div>
            <div class="form-group col-md-6">
                <label>@lang('users.password') @if(empty($teacher))<span class="text-danger">*</span>@endif</label>
                <input type="password" name="password" class="form-control" autocomplete="new-password"
                       @if(empty($teacher)) required @endif />
                <small class="text-muted">@lang('users.password_hint')</small>
            </div>
        </div>

        <hr class="my-3">

        {{-- Personal & professional --}}
        <h6 class="text-muted mb-2"><i class="la la-briefcase"></i> @lang('users.teacher_form_personal')</h6>
        <div class="row">
            <div class="form-group col-md-4">
                <label>@lang('users.specialization')</label>
                <input type="text" name="specialization" class="form-control"
                       value="{{ old('specialization', $teacher->specialization ?? '') }}" />
            </div>
            <div class="form-group col-md-4">
                <label>@lang('users.qualification')</label>
                <input type="text" name="qualification" class="form-control"
                       value="{{ old('qualification', $teacher->qualification ?? '') }}" />
            </div>
            <div class="form-group col-md-4">
                <label>@lang('users.gender')</label>
                @php $g = old('gender', $teacher->gender ?? ''); @endphp
                <select name="gender" class="form-control">
                    <option value="">—</option>
                    <option value="male" @selected($g === 'male')>@lang('users.gender_male')</option>
                    <option value="female" @selected($g === 'female')>@lang('users.gender_female')</option>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label>@lang('users.date_of_birth')</label>
                <input type="date" name="date_of_birth" class="form-control"
                       value="{{ old('date_of_birth', optional($teacher->date_of_birth ?? null)->format('Y-m-d')) }}" />
            </div>
            <div class="form-group col-md-4">
                <label>@lang('users.birth_place')</label>
                <input type="text" name="birth_place" class="form-control"
                       value="{{ old('birth_place', $tp->birth_place ?? '') }}" />
            </div>
            <div class="form-group col-md-4">
                <label>@lang('users.hire_date')</label>
                <input type="date" name="hire_date" class="form-control"
                       value="{{ old('hire_date', optional($teacher->hire_date ?? null)->format('Y-m-d')) }}" />
            </div>
        </div>

        <hr class="my-3">

        {{-- Contact --}}
        <h6 class="text-muted mb-2"><i class="la la-envelope"></i> @lang('users.teacher_form_contact')</h6>
        <div class="row">
            <div class="form-group col-md-6">
                <label>@lang('users.teacher_address')</label>
                <input type="text" name="address" class="form-control"
                       value="{{ old('address', $teacher->address ?? '') }}" />
            </div>
            <div class="form-group col-md-3">
                <label>@lang('users.phone_secondary')</label>
                <input type="text" name="phone_secondary" class="form-control" dir="ltr"
                       value="{{ old('phone_secondary', $tp->phone_secondary ?? '') }}" />
            </div>
            <div class="form-group col-md-3">
                <label>@lang('users.phone')</label>
                <input type="text" name="phone" class="form-control" dir="ltr"
                       value="{{ old('phone', $teacher->phone ?? '') }}" />
            </div>
            <div class="form-group col-md-6">
                <label>@lang('users.email')</label>
                <input type="email" name="email" class="form-control" dir="ltr"
                       value="{{ old('email', $teacher->email ?? '') }}" />
            </div>
        </div>

        <hr class="my-3">

        {{-- Extras --}}
        <h6 class="text-muted mb-2"><i class="la la-plus-circle"></i> @lang('users.teacher_form_extras')</h6>
        <div class="row">
            <div class="form-group col-md-6">
                <label>@lang('users.nationality')</label>
                <input type="text" name="nationality" class="form-control"
                       value="{{ old('nationality', $tp->nationality ?? '') }}" />
            </div>
            <div class="form-group col-md-6">
                <label>@lang('users.profile_photo')</label>
                <input type="file" name="profile_photo" class="form-control" accept="image/*" />
                @if(!empty($tp?->profile_photo))
                    <small class="text-muted d-block mt-1">
                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($tp->profile_photo) }}"
                           target="_blank">@lang('users.view')</a>
                    </small>
                @endif
            </div>
        </div>

    </div>
</div>

@isset($subjects)
<div class="card mb-3 border-0 shadow-sm">
    <div class="card-header bg-white">
        <strong><i class="la la-book"></i> @lang('users.teacher_subjects')</strong>
        <div class="text-muted small">@lang('users.teacher_subjects_hint')</div>
    </div>
    <div class="card-body">
        @php $selSubjectIds = collect(old('subject_ids', $selectedSubjectIds ?? []))->map(fn ($v) => (int) $v)->all(); @endphp
        @if($subjects->isEmpty())
            <div class="text-muted">@lang('users.teacher_subjects_empty')</div>
        @else
            <div class="row">
                @foreach($subjects as $sub)
                    <div class="form-group col-md-3">
                        <label class="d-flex align-items-center gap-1 m-0">
                            <input type="checkbox" name="subject_ids[]" value="{{ $sub->id }}"
                                   @checked(in_array($sub->id, $selSubjectIds))>
                            <span>{{ $sub->name }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endisset

@if(!empty($teacher?->id) && isset($sections))
<div class="card mb-3 border-0 shadow-sm">
    <div class="card-header bg-white">
        <strong><i class="la la-chalkboard-teacher"></i> @lang('users.teacher_assignment')</strong>
        <div class="text-muted small">@lang('users.teacher_assignment_hint')</div>
    </div>
    <div class="card-body">
        @php $hasAny = false; @endphp
        @foreach($sections as $sec)
            @php $secClasses = $classes->where('section_id', $sec->id); @endphp
            @if($secClasses->isNotEmpty())
                @php $hasAny = true; @endphp
                <div class="mb-3">
                    <h6 class="text-muted mb-2">{{ $sec->name }}</h6>
                    <div class="row">
                        @foreach($secClasses as $cl)
                            <div class="form-group col-md-3">
                                <label class="d-flex align-items-center gap-1 m-0">
                                    <input type="checkbox" name="assigned_class_ids[]" value="{{ $cl->id }}"
                                           @checked(in_array($cl->id, $assignedClassIds ?? []))>
                                    <span>{{ $cl->name }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
        @unless($hasAny)
            <div class="text-muted">@lang('users.teacher_assignment_empty')</div>
        @endunless
    </div>
</div>
@endif

<div class="d-flex gap-1 mt-3">
    <button class="btn btn-primary"><i class="la la-save"></i> @lang('users.save')</button>
    <a href="{{ route('admin.users.teachers.index') }}" class="btn btn-outline-secondary">@lang('users.cancel')</a>
</div>

@if($errors->any())
    <div class="alert alert-danger mt-3">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
