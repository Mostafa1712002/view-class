@csrf
@php $isRtl = app()->getLocale() === 'ar'; @endphp

@push('styles')
<style>
    .pf-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
        margin-bottom: 1rem;
    }
    .pf-card .head {
        padding: .9rem 1.1rem; border-bottom: 1px solid #f1f5f9;
    }
    .pf-card .head h5 {
        margin: 0; font-size: 1rem; font-weight: 700; color: #0f172a;
        display: inline-flex; align-items: center; gap: .55rem;
    }
    .pf-card .head h5 i { color: var(--gold-400); }
    .pf-card .body { padding: 1.1rem; }

    .pf-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 1rem; }
    .pf-grid.cols-3 { grid-template-columns: repeat(3, minmax(0,1fr)); }
    @media (max-width: 767.98px) {
        .pf-grid, .pf-grid.cols-3 { grid-template-columns: 1fr; }
    }
    .pf-field label {
        display: flex; align-items: center; gap: .35rem;
        font-weight: 600; font-size: .82rem; color: #334155; margin-bottom: .35rem;
    }
    .pf-field label .req { color: #dc2626; font-weight: 700; }
    .pf-field label .hint { color: #94a3b8; font-weight: 400; font-size: .75rem; margin-{{ $isRtl ? 'right' : 'left' }}: auto; }
    .pf-field .form-control, .pf-field select.form-control {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: .55rem .75rem; font-size: .92rem; color: #0f172a; width: 100%;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .pf-field .form-control:focus, .pf-field select.form-control:focus {
        border-color: var(--gold-300); box-shadow: 0 0 0 .2rem rgba(207,160,70,.16); outline: none;
    }

    .pf-actions { display: flex; gap: .5rem; align-items: center; padding-top: .5rem; }
    .btn-gold {
        background: linear-gradient(135deg, var(--gold-300), var(--gold-500));
        border: 1px solid var(--gold-400); color: #fff;
        font-weight: 600; padding: .55rem 1.1rem; border-radius: 10px;
        box-shadow: 0 1px 2px rgba(207,160,70,.18);
        display: inline-flex; align-items: center; gap: .45rem;
        transition: transform .15s ease, box-shadow .2s ease;
    }
    .btn-gold:hover { color: #fff; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(207,160,70,.22); }
    .btn-ghost {
        background: #fff; border: 1px solid #e2e8f0; color: #475569;
        font-weight: 500; padding: .55rem 1.1rem; border-radius: 10px;
        display: inline-flex; align-items: center; gap: .45rem; text-decoration: none;
    }
    .btn-ghost:hover { border-color: var(--gold-300); color: var(--gold-500); }

    .pf-alert {
        background: #fef2f2; border: 1px solid #fecaca; color: #991b1b;
        border-radius: 10px; padding: .65rem .85rem; font-size: .9rem; margin-top: 1rem;
    }
    .pf-alert ul { margin: 0; padding-{{ $isRtl ? 'right' : 'left' }}: 1.1rem; }
</style>
@endpush

<div class="pf-card">
    <div class="head"><h5><i class="la la-user"></i> @lang('users.parent_personal_info')</h5></div>
    <div class="body">
        <div class="pf-grid cols-3">
            <div class="pf-field">
                <label>@lang('users.name') <span class="req">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $parent->name ?? '') }}" required />
            </div>
            <div class="pf-field">
                <label>@lang('users.national_id')</label>
                <input type="text" name="national_id" class="form-control" value="{{ old('national_id', $parent->national_id ?? '') }}" />
            </div>
            <div class="pf-field">
                <label>@lang('users.first_name')</label>
                <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $parent->first_name ?? '') }}" />
            </div>
            <div class="pf-field">
                <label>@lang('users.father_name')</label>
                <input type="text" name="father_name" class="form-control" value="{{ old('father_name', $parent->father_name ?? '') }}" />
            </div>
            <div class="pf-field">
                <label>@lang('users.grandfather_name')</label>
                <input type="text" name="grandfather_name" class="form-control" value="{{ old('grandfather_name', $parent->grandfather_name ?? '') }}" />
            </div>
            <div class="pf-field">
                <label>@lang('users.family_name')</label>
                <input type="text" name="family_name" class="form-control" value="{{ old('family_name', $parent->family_name ?? '') }}" />
            </div>
            <div class="pf-field">
                <label>@lang('users.name_en')</label>
                <input type="text" name="name_en" class="form-control" value="{{ old('name_en', $parent->name_en ?? '') }}" dir="ltr" />
            </div>
            <div class="pf-field">
                <label>@lang('users.gender')</label>
                @php $g = old('gender', $parent->gender ?? ''); @endphp
                <select name="gender" class="form-control">
                    <option value="">—</option>
                    <option value="male" @selected($g === 'male')>@lang('users.gender_male')</option>
                    <option value="female" @selected($g === 'female')>@lang('users.gender_female')</option>
                </select>
            </div>
            <div class="pf-field">
                <label>@lang('users.date_of_birth')</label>
                @php $dob = old('date_of_birth', isset($parent) && $parent->date_of_birth ? $parent->date_of_birth->format('Y-m-d') : ''); @endphp
                <input type="date" name="date_of_birth" class="form-control" value="{{ $dob }}" />
            </div>
            <div class="pf-field">
                <label>@lang('users.birth_place')</label>
                <input type="text" name="birth_place" class="form-control" value="{{ old('birth_place', $parent->birth_place ?? '') }}" />
            </div>
            <div class="pf-field">
                <label>@lang('users.nationality')</label>
                <input type="text" name="nationality" class="form-control" value="{{ old('nationality', $parent->nationality ?? '') }}" />
            </div>
        </div>
    </div>
</div>

<div class="pf-card">
    <div class="head"><h5><i class="la la-phone"></i> @lang('users.parent_contact_info')</h5></div>
    <div class="body">
        <div class="pf-grid cols-3">
            <div class="pf-field">
                <label>@lang('users.phone')</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $parent->phone ?? '') }}" dir="ltr" />
            </div>
            <div class="pf-field">
                <label>@lang('users.mobile')</label>
                <input type="text" name="phone_secondary" class="form-control" value="{{ old('phone_secondary', $parent->phone_secondary ?? '') }}" dir="ltr" />
            </div>
            <div class="pf-field">
                <label>@lang('users.whatsapp')</label>
                <input type="text" name="whatsapp" class="form-control" value="{{ old('whatsapp', $parent->whatsapp ?? '') }}" dir="ltr" />
            </div>
            <div class="pf-field" style="grid-column: 1 / -1;">
                <label>@lang('users.address')</label>
                <input type="text" name="address" class="form-control" value="{{ old('address', $parent->address ?? '') }}" />
            </div>
        </div>
    </div>
</div>

<div class="pf-card">
    <div class="head"><h5><i class="la la-id-badge"></i> @lang('users.parent_account_info')</h5></div>
    <div class="body">
        <div class="pf-grid cols-3">
            <div class="pf-field">
                <label>@lang('users.username') <span class="req">*</span></label>
                <input type="text" name="username" class="form-control" value="{{ old('username', $parent->username ?? '') }}" required />
            </div>
            <div class="pf-field">
                <label>@lang('users.email')</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $parent->email ?? '') }}" dir="ltr" />
            </div>
            <div class="pf-field">
                <label>@lang('users.password')
                    <span class="hint">{{ isset($parent) ? ($isRtl ? 'اتركه فارغًا لعدم التغيير' : 'leave empty to keep') : '' }}</span>
                </label>
                <input type="password" name="password" class="form-control" autocomplete="new-password" />
            </div>
            <div class="pf-field" style="grid-column: 1 / -1;">
                <label>@lang('users.profile_picture')</label>
                @if(isset($parent) && $parent->profile_picture)
                    <div style="margin-bottom:.5rem;">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($parent->profile_picture) }}" alt="" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:1px solid #e5e7eb;">
                    </div>
                @endif
                <input type="file" name="profile_picture" class="form-control" accept="image/*" />
            </div>
        </div>
    </div>
</div>

<div class="pf-actions">
    <button type="submit" class="btn-gold"><i class="la la-save"></i> @lang('users.save')</button>
    <a href="{{ route('admin.users.parents.index') }}" class="btn-ghost">@lang('users.cancel')</a>
</div>

@if($errors->any())
    <div class="pf-alert">
        <ul>
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif
