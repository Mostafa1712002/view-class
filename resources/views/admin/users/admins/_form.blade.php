{{-- Admins card 55 — form partial (light + gold) --}}
@csrf
@php
    $isRtl = app()->getLocale() === 'ar';
    $currentJobId = old('job_title_id', $admin->job_title_id ?? ($selectedJobTitleId ?? null));
    $currentJob = $jobTitles->firstWhere('id', $currentJobId);
@endphp

@if($errors->any())
    <div class="ad-form-alert err mb-3">
        <i class="la la-exclamation-triangle"></i>
        <div>
            @foreach($errors->all() as $e)
                <div>{{ $e }}</div>
            @endforeach
        </div>
    </div>
@endif

{{-- Section: Job Title preview --}}
@if($currentJob)
<div class="ad-job-banner mb-3">
    <div class="jb-ico"><i class="la la-id-badge"></i></div>
    <div class="jb-body">
        <div class="jb-label">@lang('users.job_title')</div>
        <div class="jb-title">{{ $currentJob->localized_name }}</div>
    </div>
    <span class="ad-pill role-muted">{{ $currentJob->slug }}</span>
</div>
@endif

{{-- Section 1: Basic info --}}
<div class="ad-form-section">
    <div class="ad-section-title">
        <i class="la la-user"></i> @lang('users.admin_basic_info')
    </div>
    <div class="row">
        <div class="form-group col-md-6 mb-3">
            <label class="ad-form-label">@lang('users.name') <span class="req">*</span></label>
            <input type="text" name="name" class="form-control"
                   value="{{ old('name', $admin->name ?? '') }}" required />
        </div>
        <div class="form-group col-md-6 mb-3">
            <label class="ad-form-label">@lang('users.national_id')</label>
            <input type="text" name="national_id" class="form-control"
                   value="{{ old('national_id', $admin->national_id ?? '') }}"
                   placeholder="10**********" />
        </div>
        <div class="form-group col-md-6 mb-3">
            <label class="ad-form-label">@lang('users.email')</label>
            <input type="email" name="email" class="form-control"
                   value="{{ old('email', $admin->email ?? '') }}" />
        </div>
        <div class="form-group col-md-6 mb-3">
            <label class="ad-form-label">@lang('users.phone')</label>
            <input type="text" name="phone" class="form-control"
                   value="{{ old('phone', $admin->phone ?? '') }}"
                   placeholder="05XXXXXXXX" />
        </div>
        <div class="form-group col-md-6 mb-3">
            <label class="ad-form-label">@lang('users.gender')</label>
            @php $g = old('gender', $admin->gender ?? ''); @endphp
            <select name="gender" class="form-control">
                <option value="">—</option>
                <option value="male" @selected($g === 'male')>@lang('users.gender_male')</option>
                <option value="female" @selected($g === 'female')>@lang('users.gender_female')</option>
            </select>
        </div>
    </div>
</div>

{{-- Section 2: Assignment --}}
<div class="ad-form-section">
    <div class="ad-section-title">
        <i class="la la-briefcase"></i> @lang('users.admin_assignment')
    </div>
    <div class="row">
        <div class="form-group col-md-12 mb-3">
            <label class="ad-form-label">
                @lang('users.job_title')
                <a href="{{ route('admin.users.job-titles.index') }}" class="ad-form-link" target="_blank">
                    <i class="la la-cogs"></i> @lang('users.admin_manage_job_titles')
                </a>
            </label>
            <select name="job_title_id" class="form-control">
                <option value="">@lang('users.select_job_title')</option>
                @foreach($jobTitles as $jt)
                    <option value="{{ $jt->id }}" @selected($currentJobId == $jt->id)>{{ $jt->localized_name }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

{{-- Section 3: Credentials --}}
<div class="ad-form-section">
    <div class="ad-section-title">
        <i class="la la-key"></i> @lang('users.admin_security')
    </div>
    <div class="row">
        <div class="form-group col-md-6 mb-3">
            <label class="ad-form-label">@lang('users.username') <span class="req">*</span></label>
            <input type="text" name="username" class="form-control"
                   value="{{ old('username', $admin->username ?? '') }}" required
                   autocomplete="off" />
        </div>
        <div class="form-group col-md-6 mb-3">
            <label class="ad-form-label">
                @lang('users.password')
                @isset($admin)
                    <span class="hint">{{ $isRtl ? 'اتركها فارغة للإبقاء على كلمة المرور الحالية' : 'Leave blank to keep current' }}</span>
                @endisset
            </label>
            <input type="password" name="password" class="form-control" autocomplete="new-password"
                   placeholder="••••••••" />
        </div>
    </div>
</div>

<div class="ad-form-footer">
    <button class="btn-gold" type="submit">
        <i class="la la-save"></i> @lang('users.save')
    </button>
    <a href="{{ route('admin.users.admins.index') }}" class="btn-ghost">
        <i class="la la-times"></i> @lang('users.cancel')
    </a>
</div>

@once
@push('styles')
<style>
    .ad-form-alert {
        background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;
        border-radius: 10px; padding: .65rem .85rem; display: flex; align-items: flex-start;
        gap: .55rem; font-size: .9rem;
    }
    .ad-form-alert.err { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
    .ad-form-alert i { font-size: 1.15rem; line-height: 1.3; color: inherit; flex-shrink: 0; }

    .ad-job-banner {
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
        border: 1px solid #fde68a; border-radius: 12px;
        padding: .85rem 1rem; display: flex; align-items: center; gap: .85rem;
    }
    .ad-job-banner .jb-ico {
        width: 42px; height: 42px; border-radius: 10px;
        background: #fff; color: var(--gold-500); font-size: 1.2rem;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 2px 4px rgba(207,160,70,.18);
        flex-shrink: 0;
    }
    .ad-job-banner .jb-body { flex: 1; }
    .ad-job-banner .jb-label { font-size: .72rem; color: #92400e; text-transform: uppercase; letter-spacing: .5px; font-weight: 700; }
    .ad-job-banner .jb-title { font-size: 1.05rem; font-weight: 700; color: #78350f; }

    .ad-form-section {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
        padding: 1.1rem 1.15rem; margin-bottom: 1rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.03);
    }
    .ad-section-title {
        display: flex; align-items: center; gap: .55rem;
        font-size: .92rem; font-weight: 700; color: #0f172a;
        margin-bottom: 1rem; padding-bottom: .65rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .ad-section-title i { color: var(--gold-400); font-size: 1.15rem; }

    .ad-form-label {
        font-weight: 600; font-size: .82rem; color: #334155;
        margin-bottom: .35rem; display: flex; align-items: center; gap: .35rem;
    }
    .ad-form-label .req { color: #dc2626; font-weight: 700; }
    .ad-form-label .hint { color: #94a3b8; font-weight: 400; font-size: .72rem; margin-{{ $isRtl ? 'right' : 'left' }}: auto; }
    .ad-form-link {
        margin-{{ $isRtl ? 'right' : 'left' }}: auto;
        font-size: .75rem; font-weight: 500;
        color: var(--gold-500); text-decoration: none;
        display: inline-flex; align-items: center; gap: .25rem;
    }
    .ad-form-link:hover { color: var(--gold-400); text-decoration: underline; }
    .ad-form-link i { font-size: .9rem; }

    .ad-form-section .form-control {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: .55rem .75rem; font-size: .92rem; color: #0f172a;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .ad-form-section .form-control:focus {
        border-color: var(--gold-300);
        box-shadow: 0 0 0 .2rem rgba(207,160,70,.16); outline: none;
    }

    .ad-form-footer {
        display: flex; gap: .55rem; align-items: center;
        padding-top: .25rem;
    }
</style>
@endpush
@endonce
