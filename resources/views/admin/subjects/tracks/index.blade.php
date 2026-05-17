@extends('layouts.app')

@section('title', __('subject_tracks.page_title'))
@section('body_class', 'theme-light')

@push('styles')
<style>
    .tr-header { margin-bottom: 1.25rem; }
    .tr-header h2 {
        font-size: 1.5rem; font-weight: 700; color: #0f172a;
        margin-bottom: .15rem; letter-spacing: -.2px;
    }
    .tr-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }
    .tr-header .breadcrumb-item + .breadcrumb-item::before { color: #cbd5e1; }
    .tr-help { color: #64748b; font-size: .9rem; max-width: 720px; line-height: 1.55; }

    .tr-toolbar {
        display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: .85rem 1rem; margin-bottom: 1rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
    }
    .tr-toolbar .search-field {
        flex: 1 1 280px; min-width: 0;
        display: flex; align-items: center; gap: .55rem;
        border: 1px solid #e2e8f0; border-radius: 10px; padding: .35rem .7rem;
        background: #fff;
    }
    .tr-toolbar .search-field i { color: var(--gold-400, #cfa046); }
    .tr-toolbar .search-field input {
        flex: 1; border: 0; outline: none; font-size: .93rem; background: transparent; color: #0f172a;
    }
    .tr-toolbar .btn-add {
        background: var(--gold-500, #b88735); color: #fff; border: 0;
        padding: .55rem 1.1rem; border-radius: 10px; font-weight: 600;
        display: inline-flex; align-items: center; gap: .4rem;
    }
    .tr-toolbar .btn-add:hover { background: var(--gold-600, #9a6f25); color: #fff; }

    .tr-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
    }
    .tr-table { width: 100%; margin: 0; border-collapse: separate; border-spacing: 0; }
    .tr-table thead th {
        background: #f8fafc; color: #475569;
        font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .3px;
        padding: .8rem 1rem; border-bottom: 1px solid #e5e7eb; white-space: nowrap;
    }
    .tr-table tbody td {
        padding: .85rem 1rem; border-bottom: 1px solid #f1f5f9;
        font-size: .94rem; color: #0f172a; vertical-align: middle;
    }
    .tr-table tbody tr:last-child td { border-bottom: 0; }
    .tr-table tbody tr:hover { background: #fafbfc; }
    .tr-name { font-weight: 600; color: #0f172a; }
    .tr-name small { color: #64748b; font-weight: 400; }

    .tr-badge { display: inline-block; padding: .2rem .65rem; border-radius: 999px; font-size: .78rem; font-weight: 600; }
    .tr-badge.on  { background: #dcfce7; color: #15803d; }
    .tr-badge.off { background: #fee2e2; color: #b91c1c; }

    .tr-actions { display: flex; gap: .35rem; }
    .tr-actions .btn-ico {
        width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #475569;
        font-size: 1rem; transition: background .15s ease, color .15s ease, border-color .15s ease;
    }
    .tr-actions .btn-ico:hover { background: #f8fafc; color: #0f172a; }
    .tr-actions .btn-ico.danger:hover { background: #fee2e2; color: #b91c1c; border-color: #fca5a5; }

    .tr-empty { padding: 2.5rem 1rem; text-align: center; color: #64748b; }
    .tr-empty i { font-size: 2.4rem; color: #cbd5e1; margin-bottom: .5rem; display: block; }
    .tr-empty h4 { color: #0f172a; font-weight: 700; font-size: 1.05rem; margin-bottom: .25rem; }
</style>
@endpush

@section('content')
<div class="content-header row tr-header">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title">@lang('subject_tracks.page_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.subjects.index') }}">@lang('sprint4.subjects.plural')</a></li>
                <li class="breadcrumb-item active">@lang('subject_tracks.page_title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <p class="tr-help">@lang('subject_tracks.help')</p>

    <form method="GET" action="{{ route('admin.subject-tracks.index') }}" class="tr-toolbar">
        <div class="search-field">
            <i class="la la-search"></i>
            <input type="text" name="q" value="{{ $search }}" placeholder="@lang('subject_tracks.search_placeholder')">
        </div>
        <button type="submit" class="btn btn-light" style="border:1px solid #e2e8f0; border-radius:10px; padding:.55rem 1rem;">@lang('common.search')</button>
        <a href="{{ route('admin.subject-tracks.create') }}" class="btn-add" style="margin-{{ app()->getLocale()==='ar' ? 'right' : 'left' }}: auto;">
            <i class="la la-plus"></i>
            @lang('subject_tracks.add')
        </a>
    </form>

    <div class="tr-card">
        @if($tracks->total() === 0)
            <div class="tr-empty">
                <i class="la la-folder-open"></i>
                <h4>@lang('subject_tracks.empty.title')</h4>
                <div>@lang('subject_tracks.empty.subtitle')</div>
            </div>
        @else
            <div class="table-responsive">
                <table class="tr-table">
                    <thead>
                        <tr>
                            <th>@lang('subject_tracks.columns.name')</th>
                            <th>@lang('subject_tracks.columns.sort_order')</th>
                            <th>@lang('subject_tracks.columns.is_active')</th>
                            <th style="width:140px; text-align:center;">@lang('subject_tracks.columns.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tracks as $track)
                            <tr>
                                <td>
                                    <div class="tr-name">{{ $track->name }}</div>
                                    @if($track->name_en)<small>{{ $track->name_en }}</small>@endif
                                </td>
                                <td>{{ $track->sort_order }}</td>
                                <td>
                                    @if($track->is_active)
                                        <span class="tr-badge on">@lang('subject_tracks.status.active')</span>
                                    @else
                                        <span class="tr-badge off">@lang('subject_tracks.status.inactive')</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="tr-actions" style="justify-content:center;">
                                        <a href="{{ route('admin.subject-tracks.edit', $track->id) }}"
                                           class="btn-ico" title="@lang('common.edit')">
                                            <i class="la la-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.subject-tracks.destroy', $track->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('{{ __('subject_tracks.confirm_delete') }}')"
                                              style="margin:0;">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-ico danger" title="@lang('common.delete')">
                                                <i class="la la-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:.85rem 1.1rem; background:#fafbfc; border-top:1px solid #f1f5f9;">
                {{ $tracks->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
