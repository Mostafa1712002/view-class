@extends('layouts.app')
@section('title', __('users.import_excel_title'))
@section('body_class', 'theme-light')

@push('styles')
<style>
    .px-card {
        background:#fff; border:1px solid #e5e7eb; border-radius:14px;
        box-shadow:0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
        margin-bottom:1rem;
    }
    .px-card .head { padding:.9rem 1.1rem; border-bottom:1px solid #f1f5f9; }
    .px-card .head h5 { margin:0; font-size:1rem; font-weight:700; color:#0f172a; display:inline-flex; align-items:center; gap:.55rem; }
    .px-card .head h5 i { color: var(--gold-400); }
    .px-card .body { padding:1.1rem; }
    .px-help { color:#64748b; font-size:.88rem; margin-bottom:.9rem; }
    .px-row { display:flex; flex-wrap:wrap; align-items:center; gap:.6rem; }
    .px-row .form-control { flex:1 1 240px; background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:.5rem .7rem; }
    .btn-gold {
        background:linear-gradient(135deg, var(--gold-300), var(--gold-500));
        border:1px solid var(--gold-400); color:#fff; font-weight:600;
        padding:.5rem 1rem; border-radius:10px; display:inline-flex; align-items:center; gap:.45rem; text-decoration:none;
    }
    .btn-gold:hover { color:#fff; transform:translateY(-1px); }
    .btn-ghost {
        background:#fff; border:1px solid #e2e8f0; color:#475569; font-weight:500;
        padding:.5rem 1rem; border-radius:10px; display:inline-flex; align-items:center; gap:.45rem; text-decoration:none;
    }
    .btn-ghost:hover { border-color: var(--gold-300); color: var(--gold-500); }
    .px-alert { background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; border-radius:10px; padding:.65rem .85rem; margin-bottom:1rem; }
</style>
@endpush

@section('content')
<div class="content-header" style="margin-bottom:1.25rem;">
    <h2 style="font-size:1.5rem;font-weight:700;color:#0f172a;">@lang('users.import_excel_title')</h2>
    <ol class="breadcrumb" style="padding:0;margin:0;background:transparent;font-size:.85rem;">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.parents.index') }}">@lang('users.parents')</a></li>
        <li class="breadcrumb-item active">@lang('users.import_excel')</li>
    </ol>
</div>

<div class="content-body">
    @if(session('status'))
        <div class="px-alert"><i class="la la-check-circle"></i> {{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="px-alert" style="background:#fef2f2;border-color:#fecaca;color:#991b1b;">
            <ul style="margin:0;padding-inline-start:1.1rem;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- 1. Import new parents --}}
    <div class="px-card">
        <div class="head"><h5><i class="la la-file-excel"></i> @lang('users.import_excel')</h5></div>
        <div class="body">
            <p class="px-help">@lang('users.import_excel_help')</p>
            <div class="px-row" style="margin-bottom:.9rem;">
                <a href="{{ route('admin.users.parents.import.template') }}" class="btn-ghost"><i class="la la-download"></i> @lang('users.download_template')</a>
            </div>
            <form action="{{ route('admin.users.parents.import.run') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="px-row">
                    <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.xls,.txt" required />
                    <button type="submit" class="btn-gold"><i class="la la-upload"></i> @lang('users.upload_file')</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 2. Edit existing parents via Excel --}}
    <div class="px-card">
        <div class="head"><h5><i class="la la-file-upload"></i> @lang('users.edit_excel')</h5></div>
        <div class="body">
            <p class="px-help">@lang('users.edit_excel_help')</p>
            <div class="px-row" style="margin-bottom:.9rem;">
                <a href="{{ route('admin.users.parents.export') }}" class="btn-ghost"><i class="la la-file-export"></i> @lang('users.export_current')</a>
            </div>
            <form action="{{ route('admin.users.parents.import.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="px-row">
                    <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.xls,.txt" required />
                    <button type="submit" class="btn-gold"><i class="la la-save"></i> @lang('users.upload_file')</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 3. Link parents to students by national-id numbers --}}
    <div class="px-card">
        <div class="head"><h5><i class="la la-link"></i> @lang('users.update_by_student_numbers')</h5></div>
        <div class="body">
            <p class="px-help">@lang('users.update_by_student_help')</p>
            <div class="px-row" style="margin-bottom:.9rem;">
                <a href="{{ route('admin.users.parents.import.template') }}" class="btn-ghost"><i class="la la-download"></i> @lang('users.download_template')</a>
            </div>
            <form action="{{ route('admin.users.parents.link.numbers') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="px-row">
                    <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.xls,.txt" required />
                    <button type="submit" class="btn-gold"><i class="la la-link"></i> @lang('users.upload_file')</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
