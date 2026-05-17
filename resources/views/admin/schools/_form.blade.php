@php
    $isEdit = isset($school);
    $val = fn($k, $d=null) => old($k, $isEdit ? ($school->{$k} ?? $d) : $d);
@endphp
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name_ar" class="form-label">@lang('schools.name_ar') <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('name_ar') is-invalid @enderror" id="name_ar" name="name_ar" value="{{ $val('name_ar') }}" required>
        @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="name_en" class="form-label">@lang('schools.name_en') <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('name_en') is-invalid @enderror" id="name_en" name="name_en" value="{{ $val('name_en') }}" required>
        @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="branch_id" class="form-label">@lang('schools.branch')</label>
        <select class="form-control select2" id="branch_id" name="branch_id">
            <option value="">—</option>
            @foreach(($branches ?? []) as $b)
                <option value="{{ $b->id }}" @selected($val('branch_id') == $b->id)>{{ app()->getLocale() === 'en' ? ($b->name_en ?: $b->name_ar) : ($b->name_ar ?: $b->name_en) }}</option>
            @endforeach
        </select>
        @if(($branches ?? collect())->isEmpty())
            <small class="text-muted">
                @lang('schools.no_branches_yet')
                <a href="{{ route('admin.school-branches.create') }}">@lang('schools.add_branch_now')</a>
            </small>
        @endif
    </div>
    <div class="col-md-4 mb-3">
        <label for="sort_order" class="form-label">@lang('schools.sort_order')</label>
        <input type="number" min="0" class="form-control" id="sort_order" name="sort_order" value="{{ $val('sort_order') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="educational_track" class="form-label">@lang('schools.educational_track')</label>
        <select class="form-control select2" id="educational_track" name="educational_track">
            @foreach(['national','international','general','k12'] as $t)
                <option value="{{ $t }}" @selected($val('educational_track','national') === $t)>@lang('schools.track_' . $t)</option>
            @endforeach
        </select>
        <small class="text-muted">@lang('schools.track_predefined_hint')</small>
    </div>

    <div class="col-md-6 mb-3">
        <label for="stage" class="form-label">@lang('schools.stage') <span class="text-danger">*</span></label>
        <select class="form-control select2 @error('stage') is-invalid @enderror" id="stage" name="stage" required>
            <option value="">—</option>
            @foreach(['primary','intermediate','secondary'] as $s)
                <option value="{{ $s }}" @selected($val('stage') === $s)>@lang('schools.stage_' . $s)</option>
            @endforeach
        </select>
        @error('stage')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="city" class="form-label">@lang('schools.city') <span class="text-danger">*</span></label>
        <select class="form-control select2 @error('city') is-invalid @enderror" id="city" name="city" required>
            <option value="">—</option>
            @foreach(($cities ?? []) as $key => $labels)
                <option value="{{ $key }}" @selected($val('city') === $key)>{{ app()->getLocale() === 'en' ? $labels['en'] : $labels['ar'] }}</option>
            @endforeach
        </select>
        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="student_gender" class="form-label">@lang('schools.student_gender') <span class="text-danger">*</span></label>
        <select class="form-control select2 @error('student_gender') is-invalid @enderror" id="student_gender" name="student_gender" required>
            <option value="">—</option>
            <option value="boys"  @selected($val('student_gender') === 'boys')>@lang('schools.gender_boys')</option>
            <option value="girls" @selected($val('student_gender') === 'girls')>@lang('schools.gender_girls')</option>
            <option value="mixed" @selected($val('student_gender','mixed') === 'mixed')>@lang('schools.gender_mixed')</option>
        </select>
        @error('student_gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="timezone" class="form-label">@lang('schools.timezone') <span class="text-danger">*</span></label>
        <select class="form-control select2 @error('timezone') is-invalid @enderror" id="timezone" name="timezone" required>
            @foreach(($timezones ?? []) as $tz => $label)
                <option value="{{ $tz }}" @selected($val('timezone','Asia/Riyadh') === $tz)>{{ $label }}</option>
            @endforeach
        </select>
        @error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="default_language" class="form-label">@lang('schools.default_language') <span class="text-danger">*</span></label>
        <select class="form-control select2" id="default_language" name="default_language" required>
            <option value="ar" @selected($val('default_language','ar') === 'ar')>@lang('schools.lang_ar')</option>
            <option value="en" @selected($val('default_language','ar') === 'en')>@lang('schools.lang_en')</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label for="educational_company_id" class="form-label">@lang('schools.educational_company')</label>
        <select class="form-control select2" id="educational_company_id" name="educational_company_id">
            <option value="">—</option>
            @foreach($companies as $c)
                <option value="{{ $c->id }}" @selected($val('educational_company_id') == $c->id)>{{ $c->name_ar ?: $c->name_en }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label for="phone" class="form-label">@lang('common.phone')</label>
        <input type="text" class="form-control" id="phone" name="phone" value="{{ $val('phone') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label for="fax" class="form-label">@lang('schools.fax')</label>
        <input type="text" class="form-control" id="fax" name="fax" value="{{ $val('fax') }}">
    </div>

    <div class="col-md-6 mb-3">
        <label for="email" class="form-label">@lang('common.email')</label>
        <input type="email" class="form-control" id="email" name="email" value="{{ $val('email') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label for="website" class="form-label">@lang('schools.website')</label>
        <input type="url" class="form-control" id="website" name="website" value="{{ $val('website') }}">
    </div>

    <div class="col-12 mb-3">
        <label for="address" class="form-label">@lang('common.address')</label>
        <textarea class="form-control" id="address" name="address" rows="2">{{ $val('address') }}</textarea>
    </div>

    <div class="col-md-6 mb-3">
        <label for="logo" class="form-label">@lang('schools.logo')</label>
        @if($isEdit && $school->logo)
            <div class="mb-2"><img src="{{ asset('storage/'.$school->logo) }}" style="max-height:60px;"></div>
        @endif
        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
        <small class="text-muted">@lang('schools.logo_hint')</small>
    </div>

    <div class="col-12">
        <h5 class="mt-3 mb-2">@lang('schools.social_accounts')</h5>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Facebook</label>
        <input type="url" class="form-control" name="facebook" value="{{ $val('facebook') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Twitter</label>
        <input type="url" class="form-control" name="twitter" value="{{ $val('twitter') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Instagram</label>
        <input type="url" class="form-control" name="instagram" value="{{ $val('instagram') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">LinkedIn</label>
        <input type="url" class="form-control" name="linkedin" value="{{ $val('linkedin') }}">
    </div>

    @if($isEdit)
        <div class="col-md-6 mb-3">
            <div class="form-check mt-4">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" @checked($val('is_active', true))>
                <label class="form-check-label" for="is_active">@lang('schools.is_active')</label>
            </div>
        </div>
    @endif
</div>
