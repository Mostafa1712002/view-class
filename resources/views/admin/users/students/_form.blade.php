@csrf
<div class="row">
    <div class="form-group col-md-6">
        <label class="form-label">@lang('users.name') *</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $student->name ?? '') }}" required />
    </div>
    <div class="form-group col-md-6">
        <label class="form-label">@lang('users.username') *</label>
        <input type="text" name="username" class="form-control" value="{{ old('username', $student->username ?? '') }}" required />
    </div>
    <div class="form-group col-md-6">
        <label class="form-label">@lang('users.email')</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $student->email ?? '') }}" />
    </div>
    <div class="form-group col-md-6">
        <label class="form-label">@lang('users.national_id')</label>
        <input type="text" name="national_id" class="form-control" value="{{ old('national_id', $student->national_id ?? '') }}" />
    </div>
    <div class="form-group col-md-4">
        <label class="form-label">@lang('users.gender')</label>
        @php $g = old('gender', $student->gender ?? ''); @endphp
        <select name="gender" class="form-control">
            <option value="">—</option>
            <option value="male" @selected($g === 'male')>@lang('users.gender_male')</option>
            <option value="female" @selected($g === 'female')>@lang('users.gender_female')</option>
        </select>
    </div>
    <div class="form-group col-md-4">
        <label class="form-label">@lang('users.date_of_birth')</label>
        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', optional($student->date_of_birth ?? null)->format('Y-m-d')) }}" />
    </div>
    <div class="form-group col-md-4">
        <label class="form-label">@lang('users.phone')</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $student->phone ?? '') }}" />
    </div>
    <div class="form-group col-md-6">
        <label class="form-label">@lang('users.grade_level')</label>
        <select name="section_id" class="form-control">
            <option value="">@lang('users.select_section')</option>
            @foreach($sections as $s)
                <option value="{{ $s->id }}" @selected(old('section_id', $student->section_id ?? '') == $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-6">
        <label class="form-label">@lang('users.class')</label>
        <select name="class_room_id" class="form-control">
            <option value="">@lang('users.select_class')</option>
            @foreach($classes as $c)
                <option value="{{ $c->id }}" @selected(old('class_room_id', $student->class_room_id ?? '') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-6">
        <label class="form-label">@lang('users.password')</label>
        <input type="password" name="password" class="form-control" autocomplete="new-password" />
        <small class="form-text text-muted">@lang('users.password_hint')</small>
    </div>
</div>

<div class="d-flex gap-1 mt-3">
    <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('users.save')</button>
    <a href="{{ route('admin.users.students.index') }}" class="btn btn-outline-secondary">@lang('users.cancel')</a>
</div>

@if($errors->any())
<div class="alert alert-danger mt-3">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
