@csrf
<div class="row">
    <div class="form-group col-md-6"><label>@lang('users.name') *</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $admin->name ?? '') }}" required /></div>
    <div class="form-group col-md-6"><label>@lang('users.username') *</label>
        <input type="text" name="username" class="form-control" value="{{ old('username', $admin->username ?? '') }}" required /></div>
    <div class="form-group col-md-6"><label>@lang('users.email')</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $admin->email ?? '') }}" /></div>
    <div class="form-group col-md-6"><label>@lang('users.national_id')</label>
        <input type="text" name="national_id" class="form-control" value="{{ old('national_id', $admin->national_id ?? '') }}" /></div>
    <div class="form-group col-md-6"><label>@lang('users.job_title')</label>
        @php $current = old('job_title_id', $admin->job_title_id ?? ($selectedJobTitleId ?? null)); @endphp
        <select name="job_title_id" class="form-control">
            <option value="">@lang('users.select_job_title')</option>
            @foreach($jobTitles as $jt)
                <option value="{{ $jt->id }}" @selected($current == $jt->id)>{{ $jt->localized_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-3"><label>@lang('users.gender')</label>
        @php $g = old('gender', $admin->gender ?? ''); @endphp
        <select name="gender" class="form-control">
            <option value="">—</option>
            <option value="male" @selected($g === 'male')>@lang('users.gender_male')</option>
            <option value="female" @selected($g === 'female')>@lang('users.gender_female')</option>
        </select>
    </div>
    <div class="form-group col-md-3"><label>@lang('users.phone')</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $admin->phone ?? '') }}" /></div>
    <div class="form-group col-md-6"><label>@lang('users.password')</label>
        <input type="password" name="password" class="form-control" autocomplete="new-password" /></div>
</div>
<div class="d-flex gap-1 mt-3">
    <button class="btn btn-primary"><i class="la la-save"></i> @lang('users.save')</button>
    <a href="{{ route('admin.users.admins.index') }}" class="btn btn-outline-secondary">@lang('users.cancel')</a>
</div>
@if($errors->any())<div class="alert alert-danger mt-3"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
