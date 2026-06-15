@extends('layouts.app')

@section('title', __('users.parent_link_children').' — '.$parent->name)
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $linkedIds = $linked->pluck('id')->all();
@endphp

@push('styles')
<style>
    .pl-header { margin-bottom: 1.25rem; }
    .pl-header h2 {
        font-size: 1.5rem; font-weight: 700; color: #0f172a;
        margin-bottom: .15rem; letter-spacing: -.2px;
    }
    .pl-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }
    .pl-header .breadcrumb-item + .breadcrumb-item::before { color: #cbd5e1; }

    .pl-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
        margin-bottom: 1rem;
    }
    .pl-card .head {
        padding: 1rem 1.1rem; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap;
    }
    .pl-card .head h5 {
        margin: 0; font-size: 1rem; font-weight: 700; color: #0f172a;
        display: inline-flex; align-items: center; gap: .55rem;
    }
    .pl-card .head h5 i { color: var(--gold-400); }
    .pl-card .body { padding: 1.1rem; }
    .pl-card .body.p-0 { padding: 0; }

    .pl-summary {
        display: flex; align-items: center; gap: .75rem;
        padding: .65rem .85rem; background: #fffbeb; border: 1px solid #fde68a;
        border-radius: 10px; margin-bottom: 1rem; font-size: .9rem; color: #78350f;
    }
    .pl-summary i { color: var(--gold-500); font-size: 1.1rem; }
    .pl-summary strong { color: #92400e; }

    .pl-search-row {
        display: flex; gap: .5rem; align-items: center; flex: 1 1 auto; max-width: 460px;
    }
    .pl-search-row input[type="search"] {
        flex: 1; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: .5rem .75rem; font-size: .9rem;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .pl-search-row input[type="search"]:focus {
        border-color: var(--gold-300); box-shadow: 0 0 0 .2rem rgba(207,160,70,.16); outline: none;
    }
    .pl-search-row .btn-go {
        background: #fff; border: 1px solid #e2e8f0; color: #475569;
        border-radius: 10px; padding: .5rem .75rem; transition: all .15s ease;
    }
    .pl-search-row .btn-go:hover { border-color: var(--gold-300); color: var(--gold-500); }

    .pl-quick {
        display: flex; gap: .35rem; flex-wrap: wrap;
    }
    .btn-ghost-sm {
        background: #fff; border: 1px solid #e2e8f0; color: #475569;
        font-size: .8rem; padding: .35rem .65rem; border-radius: 8px;
        display: inline-flex; align-items: center; gap: .3rem; transition: all .15s ease; cursor: pointer;
    }
    .btn-ghost-sm:hover { border-color: var(--gold-300); color: var(--gold-500); }

    .pl-table { margin: 0; width: 100%; }
    .pl-table thead th {
        background: #f8fafc !important; color: #475569 !important;
        font-weight: 600; font-size: .78rem; text-transform: uppercase; letter-spacing: .5px;
        border-bottom: 1px solid #e5e7eb; padding: .8rem 1rem; white-space: nowrap;
    }
    .pl-table tbody td { padding: .8rem 1rem; vertical-align: middle; color: #0f172a; border-top: 1px solid #f1f5f9; }
    .pl-table tbody tr:first-child td { border-top: 0; }
    .pl-table tbody tr { transition: background .15s ease; }
    .pl-table tbody tr:hover { background: #fafbfc; }
    .pl-table tbody tr.is-linked { background: #fffbeb; }
    .pl-table tbody tr.is-linked:hover { background: #fef3c7; }

    .pl-check {
        width: 18px; height: 18px; accent-color: var(--gold-500); cursor: pointer;
    }
    .pl-name-primary { font-weight: 600; color: #0f172a; }
    .pl-name-secondary { color: #64748b; font-size: .8rem; }
    .pl-id-chip {
        background: #f1f5f9; color: #475569; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .78rem; padding: .15rem .5rem; border-radius: 6px; border: 1px solid #e2e8f0;
    }
    .pl-pill { display: inline-flex; align-items: center; gap: .3rem;
        padding: .2rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 600; line-height: 1.3; }
    .pl-pill.linked { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
    .pl-pill.linked i { color: var(--gold-500); }

    .pl-actions-bar {
        position: sticky; bottom: 0;
        display: flex; justify-content: space-between; align-items: center;
        padding: 1rem 1.1rem; background: #fff; border-top: 1px solid #f1f5f9;
        gap: .5rem; flex-wrap: wrap;
        border-radius: 0 0 14px 14px;
    }

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

    .pl-alert {
        background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;
        border-radius: 10px; padding: .65rem .85rem; display: flex; align-items: center;
        gap: .55rem; font-size: .9rem; margin-bottom: 1rem;
    }
    .pl-alert i { color: #10b981; font-size: 1.1rem; }
    .pl-alert.err { background: #fef2f2; border-color: #fecaca; color: #991b1b; }

    .pl-empty { padding: 2.5rem 1rem; text-align: center; color: #94a3b8; }
    .pl-empty i { font-size: 2.25rem; opacity: .55; display: block; margin-bottom: .35rem; color: #cbd5e1; }

    .pl-counter {
        background: #fff7ed; color: #b45309; font-weight: 700;
        font-size: .85rem; padding: .25rem .65rem; border-radius: 999px;
        border: 1px solid #fed7aa; display: inline-flex; align-items: center; gap: .3rem;
    }

    /* Parent identity strip */
    .pl-parent-strip {
        background: linear-gradient(135deg, #fffbeb 0%, #fff 60%);
        border: 1px solid #fde68a; border-radius: 14px;
        padding: .9rem 1.1rem; margin-bottom: 1.25rem;
        display: flex; align-items: center; gap: .9rem; flex-wrap: wrap;
        box-shadow: 0 1px 2px rgba(207,160,70,.06), 0 4px 12px rgba(207,160,70,.04);
    }
    .pl-parent-strip .ps-avatar {
        width: 44px; height: 44px; border-radius: 50%;
        background: linear-gradient(135deg, #fde68a, #fcd34d);
        color: #92400e; font-weight: 700; font-size: 1rem;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; border: 1px solid #fde68a;
    }
    .pl-parent-strip .ps-name { font-weight: 700; color: #0f172a; font-size: 1rem; }
    .pl-parent-strip .ps-username { color: #64748b; font-size: .82rem; }
    .pl-parent-strip .ps-back {
        margin-inline-start: auto;
        background: #fff; border: 1px solid #e2e8f0; color: #475569;
        border-radius: 9px; padding: .38rem .8rem; font-size: .84rem; font-weight: 500;
        display: inline-flex; align-items: center; gap: .4rem; text-decoration: none;
        transition: all .15s ease;
    }
    .pl-parent-strip .ps-back:hover { border-color: var(--gold-300); color: var(--gold-500); background: #fffbeb; }

    @media (max-width: 575.98px) {
        .pl-search-row { max-width: 100%; }
        .pl-actions-bar { flex-direction: column-reverse; align-items: stretch; }
        .pl-actions-bar .btn-gold, .pl-actions-bar .btn-ghost { width: 100%; justify-content: center; }
    }
</style>
@endpush

@section('content')
<div class="content-header pl-header">
    <h2>@lang('users.parent_link_children')</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.parents.index') }}">@lang('users.parents')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.parents.show', $parent->id) }}">{{ $parent->name }}</a></li>
        <li class="breadcrumb-item active">@lang('users.parent_link_children')</li>
    </ol>
</div>

<div class="content-body">
    @if(session('status'))
        <div class="pl-alert"><x-svg-icon name="check-circle" /><span>{{ session('status') }}</span></div>
    @endif

    @php
        $psInitials = collect(preg_split('/\s+/u', trim($parent->name)))
            ->filter()->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('');
    @endphp
    <div class="pl-parent-strip">
        <div class="ps-avatar">{{ $psInitials ?: '؟' }}</div>
        <div>
            <div class="ps-name">{{ $parent->name }}</div>
            <div class="ps-username">{{ $parent->username }}</div>
        </div>
        <a href="{{ route('admin.users.parents.show', $parent->id) }}" class="ps-back">
            <i class="la la-arrow-{{ $isRtl ? 'right' : 'left' }}"></i>
            @lang('users.parent_back')
        </a>
    </div>

    <div class="pl-summary">
        <x-svg-icon name="info-circle" />
        <div>
            <strong>{{ $parent->name }}</strong>
            <span class="d-block">@lang('users.parent_link_help')</span>
        </div>
        <span class="pl-counter ms-auto" id="pl-counter">
            <x-svg-icon name="mortarboard" />
            <span id="pl-counter-num">{{ count($linkedIds) }}</span>
            <span>/ <span id="pl-counter-total">0</span></span>
        </span>
    </div>

    <form action="{{ route('admin.users.parents.students.sync', $parent->id) }}" method="POST" id="pl-form">
        @csrf
        {{-- Hidden inputs persist linked-but-not-shown rows when search filters --}}
        <div id="pl-persist-wrap"></div>

        <div class="pl-card">
            <div class="head">
                <h5><x-svg-icon name="mortarboard" /> @lang('users.parent_search_students')</h5>
                <form action="{{ route('admin.users.parents.students', $parent->id) }}" method="GET" class="pl-search-row m-0">
                    <input type="search" name="q" value="{{ $q ?? '' }}" placeholder="@lang('users.parent_search_hint')" />
                    <button type="submit" class="btn-go" title="@lang('users.search')"><x-svg-icon name="search" /></button>
                </form>
            </div>

            <div class="body p-0">
                <div class="table-responsive">
                    <table class="pl-table" id="pl-table">
                        <thead>
                            <tr>
                                <th style="width: 56px;">
                                    <input type="checkbox" id="pl-check-all" class="pl-check" title="@lang('users.parent_select_all')">
                                </th>
                                <th>@lang('users.name')</th>
                                <th>@lang('users.national_id')</th>
                                <th>@lang('users.class')</th>
                                <th>@lang('users.gender')</th>
                                <th class="text-center">@lang('users.status')</th>
                            </tr>
                        </thead>
                        <tbody>
                        @php
                            $availableIds = $available->pluck('id')->all();
                            $shownLinkedIds = array_intersect($linkedIds, $availableIds);
                            $hiddenLinkedIds = array_diff($linkedIds, $availableIds);
                            // Build a combined list: linked-not-in-search at top
                            $hiddenLinked = $linked->whereIn('id', $hiddenLinkedIds);
                        @endphp
                        @foreach($hiddenLinked as $s)
                            <tr class="is-linked">
                                <td>
                                    <input type="checkbox" name="student_ids[]" value="{{ $s->id }}" class="pl-check pl-row-check" checked />
                                </td>
                                <td>
                                    <div class="pl-name-primary">{{ $s->name }}</div>
                                    <div class="pl-name-secondary">{{ $s->username }}</div>
                                </td>
                                <td>
                                    @if($s->national_id)
                                        <span class="pl-id-chip">{{ $s->national_id }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ optional($s->classRoom)->name ?? '—' }}</td>
                                <td>{{ $s->gender ? __('users.gender_'.$s->gender) : '—' }}</td>
                                <td class="text-center">
                                    <span class="pl-pill linked"><x-svg-icon name="link-45deg" />@lang('users.parent_currently_linked')</span>
                                </td>
                            </tr>
                        @endforeach

                        @forelse($available as $s)
                            @php $isLinked = in_array($s->id, $linkedIds); @endphp
                            <tr class="{{ $isLinked ? 'is-linked' : '' }}">
                                <td>
                                    <input type="checkbox" name="student_ids[]" value="{{ $s->id }}" class="pl-check pl-row-check" @checked($isLinked) />
                                </td>
                                <td>
                                    <div class="pl-name-primary">{{ $s->name }}</div>
                                    <div class="pl-name-secondary">{{ $s->username }}</div>
                                </td>
                                <td>
                                    @if($s->national_id)
                                        <span class="pl-id-chip">{{ $s->national_id }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ optional($s->classRoom)->name ?? '—' }}</td>
                                <td>{{ $s->gender ? __('users.gender_'.$s->gender) : '—' }}</td>
                                <td class="text-center">
                                    @if($isLinked)
                                        <span class="pl-pill linked"><x-svg-icon name="link-45deg" />@lang('users.parent_currently_linked')</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="pl-empty">
                                        <x-svg-icon name="mortarboard" />
                                        <div>@lang('users.no_results')</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if($available->hasPages())
                <div class="px-3 py-2 border-top">
                    {{ $available->appends(['q' => $q ?? null])->links() }}
                </div>
                @endif

                <div class="pl-actions-bar">
                    <div class="pl-quick">
                        <button type="button" class="btn-ghost-sm" id="pl-select-all"><x-svg-icon name="check-square" /> @lang('users.parent_select_all')</button>
                        <button type="button" class="btn-ghost-sm" id="pl-clear-all"><x-svg-icon name="x-lg" /> @lang('users.parent_clear_all')</button>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.users.parents.show', $parent->id) }}" class="btn-ghost">@lang('users.cancel')</a>
                        <button type="submit" class="btn-gold"><x-svg-icon name="save" /> @lang('users.save')</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var rows  = document.querySelectorAll('.pl-row-check');
    var all   = document.getElementById('pl-check-all');
    var num   = document.getElementById('pl-counter-num');
    var total = document.getElementById('pl-counter-total');

    function refresh() {
        var checked = 0;
        rows.forEach(function (r) { if (r.checked) checked++; });
        num.textContent = checked;
        total.textContent = rows.length;
        if (all) all.checked = (rows.length > 0 && checked === rows.length);
    }
    rows.forEach(function (r) { r.addEventListener('change', refresh); });
    if (all) {
        all.addEventListener('change', function () {
            rows.forEach(function (r) { r.checked = all.checked; });
            refresh();
        });
    }
    var selBtn = document.getElementById('pl-select-all');
    var clrBtn = document.getElementById('pl-clear-all');
    if (selBtn) selBtn.addEventListener('click', function () {
        rows.forEach(function (r) { r.checked = true; }); refresh();
    });
    if (clrBtn) clrBtn.addEventListener('click', function () {
        rows.forEach(function (r) { r.checked = false; }); refresh();
    });

    refresh();
});
</script>
@endsection
