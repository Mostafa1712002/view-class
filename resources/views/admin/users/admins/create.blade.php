@extends('layouts.app')

@section('title', __('users.add_admin'))
@section('body_class', 'theme-light')

@section('content')
<div class="content-header ad-header">
    <h2>@lang('users.add_admin')</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.admins.index') }}">@lang('users.admins')</a></li>
        <li class="breadcrumb-item active">@lang('users.add_admin')</li>
    </ol>
</div>

<div class="content-body">
    <div class="ad-form-wrap">
        <form action="{{ route('admin.users.admins.store') }}" method="POST" autocomplete="off" enctype="multipart/form-data">
            @include('admin.users.admins._form')
        </form>
    </div>
</div>

@push('styles')
<style>
    .ad-header { margin-bottom: 1.25rem; }
    .ad-header h2 { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-bottom: .15rem; letter-spacing: -.2px; }
    .ad-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }
    .ad-header .breadcrumb-item + .breadcrumb-item::before { color: #cbd5e1; }
    .ad-form-wrap { max-width: 960px; margin: 0 auto; }

    .ad-pill {
        display: inline-flex; align-items: center;
        padding: .2rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 600;
        line-height: 1.3; border: 1px solid transparent;
    }
    .ad-pill.role-muted { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }

    .btn-gold {
        background: linear-gradient(135deg, var(--gold-300), var(--gold-500));
        border: 1px solid var(--gold-400); color: #fff;
        font-weight: 600; padding: .6rem 1.25rem; border-radius: 10px;
        box-shadow: 0 1px 2px rgba(207,160,70,.18);
        transition: all .15s ease;
        display: inline-flex; align-items: center; gap: .45rem;
    }
    .btn-gold:hover {
        background: linear-gradient(135deg, var(--gold-400), var(--gold-500));
        color: #fff; transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(207,160,70,.22);
    }
    .btn-ghost {
        background: #fff; border: 1px solid #e2e8f0; color: #334155;
        font-weight: 600; padding: .55rem 1.1rem; border-radius: 10px;
        display: inline-flex; align-items: center; gap: .35rem;
        transition: all .15s ease;
    }
    .btn-ghost:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
</style>
@endpush
@endsection
