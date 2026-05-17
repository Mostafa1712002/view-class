@extends('layouts.app')

@section('title', __('users.parent_show_title').' — '.$parent->name)
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@push('styles')
<style>
    .ps-header { margin-bottom: 1.25rem; }
    .ps-header h2 {
        font-size: 1.5rem; font-weight: 700; color: #0f172a;
        margin-bottom: .15rem; letter-spacing: -.2px;
    }
    .ps-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }
    .ps-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
        margin-bottom: 1rem;
    }
    .ps-card .head {
        padding: 1rem 1.1rem; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between; gap: .75rem;
    }
    .ps-card .head h5 {
        margin: 0; font-size: 1rem; font-weight: 700; color: #0f172a;
        display: inline-flex; align-items: center; gap: .55rem;
    }
    .ps-card .head h5 i { color: var(--gold-400); }
    .ps-card .body { padding: 1.1rem; }

    .ps-profile { display: flex; align-items: center; gap: 1rem; }
    .ps-avatar {
        width: 64px; height: 64px; border-radius: 50%;
        background: linear-gradient(135deg, #fde68a, #fcd34d);
        color: #92400e; font-weight: 800; font-size: 1.4rem;
        display: inline-flex; align-items: center; justify-content: center;
        border: 1px solid #fde68a;
    }
    .ps-profile h3 { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin: 0; }
    .ps-profile .un { color: #64748b; font-size: .9rem; margin-top: .1rem; }

    .ps-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: .8rem 1.25rem; }
    .ps-grid .item { display: flex; flex-direction: column; gap: .15rem; }
    .ps-grid .lbl { font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; color: #64748b; font-weight: 600; }
    .ps-grid .val { color: #0f172a; font-size: .95rem; font-weight: 500; }
    .ps-grid .val.muted { color: #94a3b8; font-style: italic; font-weight: 400; }

    .ps-pill { display: inline-flex; align-items: center; gap: .3rem;
        padding: .2rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 600; line-height: 1.3;
        background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }

    .ps-table { margin: 0; width: 100%; }
    .ps-table thead th {
        background: #f8fafc !important; color: #475569 !important;
        font-weight: 600; font-size: .78rem; text-transform: uppercase; letter-spacing: .5px;
        border-bottom: 1px solid #e5e7eb; padding: .75rem 1rem;
    }
    .ps-table tbody td { padding: .8rem 1rem; vertical-align: middle; color: #0f172a; border-top: 1px solid #f1f5f9; }
    .ps-table tbody tr:first-child td { border-top: 0; }
    .ps-table tbody tr:hover { background: #fafbfc; }

    .ps-empty { padding: 2rem 1rem; text-align: center; color: #94a3b8; }
    .ps-empty i { font-size: 2.25rem; opacity: .55; display: block; margin-bottom: .35rem; color: #cbd5e1; }

    .btn-gold {
        background: linear-gradient(135deg, var(--gold-300), var(--gold-500));
        border: 1px solid var(--gold-400); color: #fff;
        font-weight: 600; padding: .5rem 1rem; border-radius: 10px;
        box-shadow: 0 1px 2px rgba(207,160,70,.18);
        display: inline-flex; align-items: center; gap: .45rem; text-decoration: none;
    }
    .btn-gold:hover { color: #fff; transform: translateY(-1px); }
    .btn-ghost {
        background: #fff; border: 1px solid #e2e8f0; color: #475569;
        font-weight: 500; padding: .5rem 1rem; border-radius: 10px;
        display: inline-flex; align-items: center; gap: .45rem; text-decoration: none;
    }
    .btn-ghost:hover { border-color: var(--gold-300); color: var(--gold-500); }

    @media (max-width: 575.98px) {
        .ps-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="content-header ps-header">
    <h2>@lang('users.parent_show_title')</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.parents.index') }}">@lang('users.parents')</a></li>
        <li class="breadcrumb-item active">{{ $parent->name }}</li>
    </ol>
</div>

<div class="content-body">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.users.parents.edit', $parent->id) }}" class="btn-gold">
            <i class="la la-edit"></i> @lang('users.edit')
        </a>
        <a href="{{ route('admin.users.parents.students', $parent->id) }}" class="btn-ghost">
            <i class="la la-user-graduate"></i> @lang('users.parent_link_children')
        </a>
        <a href="{{ route('admin.users.parents.index') }}" class="btn-ghost">
            <i class="la la-arrow-{{ $isRtl ? 'right' : 'left' }}"></i> @lang('users.parent_back')
        </a>
    </div>

    <div class="row">
        <div class="col-lg-7 col-12">
            <div class="ps-card">
                <div class="head">
                    <h5><i class="la la-user"></i> @lang('users.parent_basic_info')</h5>
                </div>
                <div class="body">
                    @php
                        $initials = collect(preg_split('/\s+/u', trim($parent->name)))
                            ->filter()->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('');
                    @endphp
                    <div class="ps-profile mb-3">
                        <div class="ps-avatar">{{ $initials ?: '?' }}</div>
                        <div>
                            <h3>{{ $parent->name }}</h3>
                            <div class="un">{{ '@'.$parent->username }}</div>
                        </div>
                    </div>
                    <div class="ps-grid">
                        <div class="item">
                            <span class="lbl">@lang('users.national_id')</span>
                            <span class="val {{ $parent->national_id ? '' : 'muted' }}">{{ $parent->national_id ?? '—' }}</span>
                        </div>
                        <div class="item">
                            <span class="lbl">@lang('users.email')</span>
                            <span class="val {{ $parent->email ? '' : 'muted' }}">{{ $parent->email ?? '—' }}</span>
                        </div>
                        <div class="item">
                            <span class="lbl">@lang('users.phone')</span>
                            <span class="val {{ $parent->phone ? '' : 'muted' }}">{{ $parent->phone ?? '—' }}</span>
                        </div>
                        <div class="item">
                            <span class="lbl">@lang('users.gender')</span>
                            <span class="val {{ $parent->gender ? '' : 'muted' }}">
                                {{ $parent->gender ? __('users.gender_'.$parent->gender) : '—' }}
                            </span>
                        </div>
                        <div class="item">
                            <span class="lbl">@lang('users.last_activity')</span>
                            <span class="val {{ $parent->last_login_at ? '' : 'muted' }}">
                                {{ $parent->last_login_at ? $parent->last_login_at->diffForHumans() : '—' }}
                            </span>
                        </div>
                        <div class="item">
                            <span class="lbl">@lang('users.status')</span>
                            <span class="val"><span class="ps-pill">@lang('users.admin_status_active')</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 col-12">
            <div class="ps-card">
                <div class="head">
                    <h5><i class="la la-user-graduate"></i> @lang('users.parent_currently_linked')</h5>
                    <span class="ps-pill">{{ $children->count() }}</span>
                </div>
                <div class="body p-0">
                    @if($children->isEmpty())
                        <div class="ps-empty">
                            <i class="la la-users"></i>
                            <div>@lang('users.parent_no_children')</div>
                            <a href="{{ route('admin.users.parents.students', $parent->id) }}" class="btn-gold mt-2">
                                <i class="la la-link"></i> @lang('users.parent_link_children')
                            </a>
                        </div>
                    @else
                        <table class="ps-table">
                            <thead>
                                <tr>
                                    <th>@lang('users.name')</th>
                                    <th>@lang('users.class')</th>
                                    <th>@lang('users.parent_relationship')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($children as $c)
                                <tr>
                                    <td>
                                        <strong>{{ $c->name }}</strong>
                                        @if($c->national_id)
                                            <div class="text-muted small">{{ $c->national_id }}</div>
                                        @endif
                                    </td>
                                    <td>{{ optional($c->classRoom)->name ?? '—' }}</td>
                                    <td>{{ $c->pivot->relationship ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
