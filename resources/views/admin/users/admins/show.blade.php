@extends('layouts.app')

@section('title', $admin->name)
@section('body_class', 'theme-light')

@section('content')
<div class="content-header ad-header">
    <h2>{{ $admin->name }}</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.admins.index') }}">@lang('users.admins')</a></li>
        <li class="breadcrumb-item active">{{ $admin->name }}</li>
    </ol>
</div>

<div class="content-body">
    <div class="ad-show-wrap">
        <div class="ad-show-head">
            <div class="ad-show-avatar">
                @php $photo = $admin->profile_picture ?? $admin->avatar ?? null; @endphp
                @if($photo)
                    <img src="{{ asset('storage/'.$photo) }}" alt="" />
                @else
                    <i class="la la-user-shield"></i>
                @endif
            </div>
            <div class="ad-show-meta">
                <div class="ad-show-name">{{ $admin->name }}</div>
                @if($admin->jobTitle)
                    <span class="ad-pill role">{{ $admin->jobTitle->localized_name }}</span>
                @endif
                @if($admin->is_active)
                    <span class="ad-pill active">@lang('users.admin_status_active')</span>
                @else
                    <span class="ad-pill inactive">@lang('users.admin_status_inactive')</span>
                @endif
            </div>
            <div class="ad-show-actions">
                <a href="{{ route('admin.users.admins.edit', $admin->id) }}" class="btn-gold">
                    <i class="la la-edit"></i> @lang('users.edit')
                </a>
                <a href="{{ route('admin.users.admins.index') }}" class="btn-ghost">
                    <i class="la la-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i> @lang('users.cancel')
                </a>
            </div>
        </div>

        @php
            $sections = [
                __('users.admin_basic_info') => [
                    __('users.name') => $admin->name,
                    __('users.username') => $admin->username,
                    __('users.national_id') => $admin->national_id,
                    __('users.job_title') => optional($admin->jobTitle)->localized_name,
                    __('users.admin_first_name') => $admin->first_name,
                    __('users.admin_father_name') => $admin->father_name,
                    __('users.admin_grandfather_name') => $admin->grandfather_name,
                    __('users.admin_family_name') => $admin->family_name,
                    __('users.admin_name_en') => $admin->name_en,
                    __('users.gender') => $admin->gender ? __('users.gender_'.$admin->gender) : null,
                    __('users.date_of_birth') => $admin->date_of_birth ? $admin->date_of_birth->format('Y-m-d') : null,
                    __('users.birth_place') => $admin->birth_place,
                    __('users.nationality') => $admin->nationality,
                ],
                __('users.admin_contact_info') => [
                    __('users.email') => $admin->email,
                    __('users.phone') => $admin->phone,
                    __('users.admin_phone_secondary') => $admin->phone_secondary,
                    __('users.admin_whatsapp') => $admin->whatsapp,
                    __('users.address') => $admin->address,
                ],
            ];
        @endphp

        @foreach($sections as $title => $fields)
            @php $hasAny = collect($fields)->filter(fn ($v) => !blank($v))->isNotEmpty(); @endphp
            <div class="ad-form-section">
                <div class="ad-section-title"><i class="la la-info-circle"></i> {{ $title }}</div>
                @if($hasAny)
                    <div class="ad-info-grid">
                        @foreach($fields as $label => $value)
                            @continue(blank($value))
                            <div class="ad-info-field">
                                <div class="lbl">{{ $label }}</div>
                                <div class="val">{{ $value }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted m-0">—</p>
                @endif
            </div>
        @endforeach
    </div>
</div>

@push('styles')
<style>
    .ad-header { margin-bottom: 1.25rem; }
    .ad-header h2 { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-bottom: .15rem; }
    .ad-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }
    .ad-show-wrap { max-width: 960px; margin: 0 auto; }

    .ad-show-head {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
        padding: 1.1rem 1.15rem; margin-bottom: 1rem;
        display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
    }
    .ad-show-avatar {
        width: 72px; height: 72px; border-radius: 14px; overflow: hidden;
        background: linear-gradient(135deg, #fef3c7, #fde68a); color: var(--gold-500);
        display: flex; align-items: center; justify-content: center; font-size: 2rem; flex-shrink: 0;
    }
    .ad-show-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .ad-show-meta { flex: 1; display: flex; flex-direction: column; gap: .4rem; }
    .ad-show-name { font-size: 1.2rem; font-weight: 700; color: #0f172a; }
    .ad-show-actions { display: flex; gap: .5rem; align-items: center; }

    .ad-pill {
        display: inline-flex; align-items: center; width: fit-content;
        padding: .2rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 600;
        line-height: 1.3; border: 1px solid transparent;
    }
    .ad-pill.role { background: #fffbeb; color: #92400e; border-color: #fde68a; }
    .ad-pill.active { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .ad-pill.inactive { background: #f3f4f6; color: #6b7280; border-color: #e5e7eb; }

    .ad-form-section {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
        padding: 1.1rem 1.15rem; margin-bottom: 1rem;
    }
    .ad-section-title {
        display: flex; align-items: center; gap: .55rem;
        font-size: .92rem; font-weight: 700; color: #0f172a;
        margin-bottom: 1rem; padding-bottom: .65rem; border-bottom: 1px solid #f1f5f9;
    }
    .ad-section-title i { color: var(--gold-400); font-size: 1.15rem; }

    .ad-info-grid { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: .85rem 1.25rem; }
    .ad-info-field .lbl { font-size: .72rem; color: #94a3b8; font-weight: 600; margin-bottom: .2rem; }
    .ad-info-field .val { font-size: .92rem; color: #0f172a; }

    .btn-gold {
        background: linear-gradient(135deg, var(--gold-300), var(--gold-500));
        border: 1px solid var(--gold-400); color: #fff; font-weight: 600;
        padding: .5rem 1.1rem; border-radius: 10px;
        display: inline-flex; align-items: center; gap: .4rem; text-decoration: none;
    }
    .btn-gold:hover { color: #fff; }
    .btn-ghost {
        background: #fff; border: 1px solid #e2e8f0; color: #334155; font-weight: 600;
        padding: .45rem 1rem; border-radius: 10px;
        display: inline-flex; align-items: center; gap: .35rem; text-decoration: none;
    }
    .btn-ghost:hover { background: #f8fafc; color: #0f172a; }

    @media (max-width: 575.98px) {
        .ad-info-grid { grid-template-columns: 1fr 1fr; }
    }
</style>
@endpush
@endsection
