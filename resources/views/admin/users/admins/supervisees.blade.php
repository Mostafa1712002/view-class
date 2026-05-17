@extends('layouts.app')

@section('title', $admin->name)
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header ad-header">
    <h2>{{ $admin->name }} — @lang('users.'.($type === 'student' ? 'students' : 'teachers'))</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.admins.index') }}">@lang('users.admins')</a></li>
        <li class="breadcrumb-item active">@lang('users.admin_supervisees')</li>
    </ol>
</div>

<div class="content-body">
    @if(session('status'))
        <div class="ad-form-alert mb-3" style="max-width:960px;margin:0 auto 1rem;">
            <i class="la la-check-circle"></i><span>{{ session('status') }}</span>
        </div>
    @endif
    <div class="sup-wrap">
        <form action="{{ route('admin.users.admins.supervisees.sync', $admin->id) }}" method="POST">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}" />
            <div class="ad-form-section">
                <div class="ad-section-title">
                    <i class="la la-users"></i> @lang('users.admin_supervisees')
                    <span class="sup-pill">{{ count($assigned) }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table sup-table">
                        <thead>
                            <tr>
                                <th style="width:42px;">
                                    <input type="checkbox" id="sup-toggle-all" />
                                </th>
                                <th>@lang('users.name')</th>
                                <th>@lang('users.username')</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($candidates as $c)
                            <tr>
                                <td><input class="sup-cbx" type="checkbox" name="ids[]" value="{{ $c->id }}" @checked(in_array($c->id, $assigned))></td>
                                <td>{{ $c->name }}</td>
                                <td><span class="ad-secondary">{{ $c->username }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3"><div class="ad-empty"><i class="la la-inbox"></i><div class="lbl">@lang('users.no_results')</div></div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="ad-form-footer">
                    <button class="btn-gold" type="submit"><i class="la la-save"></i> @lang('users.save')</button>
                    <a href="{{ route('admin.users.admins.index') }}" class="btn-ghost">@lang('users.cancel')</a>
                </div>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
    .ad-header { margin-bottom: 1.25rem; }
    .ad-header h2 { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-bottom: .15rem; letter-spacing: -.2px; }
    .ad-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }
    .ad-header .breadcrumb-item + .breadcrumb-item::before { color: #cbd5e1; }
    .sup-wrap { max-width: 960px; margin: 0 auto; }

    .ad-form-section { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.1rem 1.15rem; box-shadow: 0 1px 2px rgba(15,23,42,.03); }
    .ad-section-title {
        display: flex; align-items: center; gap: .55rem;
        font-size: .92rem; font-weight: 700; color: #0f172a;
        margin-bottom: 1rem; padding-bottom: .65rem; border-bottom: 1px solid #f1f5f9;
    }
    .ad-section-title i { color: var(--gold-400); font-size: 1.15rem; }
    .sup-pill {
        margin-{{ $isRtl ? 'right' : 'left' }}: auto;
        background: #fffbeb; color: #92400e; border: 1px solid #fde68a;
        font-size: .78rem; font-weight: 700; padding: .15rem .55rem; border-radius: 999px;
    }
    .sup-table thead th { background: #f8fafc !important; color: #475569 !important; font-weight: 600; font-size: .78rem; text-transform: uppercase; letter-spacing: .5px; padding: .65rem .85rem; }
    .sup-table tbody td { padding: .65rem .85rem; vertical-align: middle; color: #0f172a; }
    .sup-table tbody tr { transition: background .15s ease; }
    .sup-table tbody tr:hover { background: #fafbfc; }
    .sup-table tbody tr + tr td { border-top: 1px solid #f1f5f9; }
    .ad-secondary { color: #64748b; font-size: .85rem; }
    .ad-empty { padding: 2.5rem 1rem; text-align: center; color: #94a3b8; }
    .ad-empty i { font-size: 2.25rem; opacity: .55; display: block; margin-bottom: .35rem; color: #cbd5e1; }
    .ad-empty .lbl { font-size: .92rem; color: #64748b; }
    .ad-form-footer { display: flex; gap: .55rem; align-items: center; padding-top: 1rem; }
    .ad-form-alert { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: 10px; padding: .65rem .85rem; display: flex; align-items: center; gap: .55rem; font-size: .9rem; }
    .ad-form-alert i { color: #10b981; font-size: 1.1rem; }

    .btn-gold {
        background: linear-gradient(135deg, var(--gold-300), var(--gold-500));
        border: 1px solid var(--gold-400); color: #fff;
        font-weight: 600; padding: .55rem 1.1rem; border-radius: 10px;
        display: inline-flex; align-items: center; gap: .45rem;
        transition: all .15s ease;
    }
    .btn-gold:hover { background: linear-gradient(135deg, var(--gold-400), var(--gold-500)); color:#fff; transform: translateY(-1px); }
    .btn-ghost { background: #fff; border: 1px solid #e2e8f0; color: #334155; font-weight: 600; padding: .55rem 1.1rem; border-radius: 10px; transition: all .15s ease; }
    .btn-ghost:hover { background: #f8fafc; color: #0f172a; }
</style>
@endpush

@push('scripts')
<script>
(function(){
    var master = document.getElementById('sup-toggle-all');
    if (!master) return;
    var boxes = document.querySelectorAll('.sup-cbx');
    master.addEventListener('change', function(){
        boxes.forEach(function(b){ b.checked = master.checked; });
    });
})();
</script>
@endpush
@endsection
