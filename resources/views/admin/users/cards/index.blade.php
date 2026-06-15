@extends('layouts.app')

@section('title', __('user_cards.page_title'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@push('styles')
<style>
    .uc-lead { color:#475569; font-size:.92rem; max-width: 60ch; margin-bottom: 1rem; }

    .uc-tabs { display:flex; gap:.5rem; margin-bottom: 1rem; flex-wrap: wrap; }
    .uc-tab {
        display:inline-flex; align-items:center; gap:.5rem;
        padding:.55rem 1rem; border-radius: 999px; background:#fff;
        border:1px solid #e5e7eb; color:#475569; font-weight:600;
        text-decoration:none; transition: all .2s ease; font-size:.88rem;
    }
    .uc-tab:hover { color:#0f172a; border-color:#cbd5e1; text-decoration:none; }
    .uc-tab.is-active { background: linear-gradient(135deg,#0f172a,#1e293b); color:#fff; border-color:transparent; box-shadow: 0 4px 12px rgba(15,23,42,.18); }
    .uc-tab .count { background: rgba(255,255,255,.18); border-radius: 999px; padding:.1rem .55rem; font-size:.72rem; }
    .uc-tab:not(.is-active) .count { background:#f1f5f9; color:#475569; }

    .uc-panel {
        background:#fff; border:1px solid #e5e7eb; border-radius: 16px;
        padding: 1.25rem; box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
        margin-bottom: 1.25rem;
    }
    .uc-panel h5 { font-size:.95rem; font-weight:700; color:#0f172a; margin-bottom:.85rem; display:flex; align-items:center; gap:.5rem; }
    .uc-panel h5::before { content:""; display:inline-block; width: 6px; height:18px; background:linear-gradient(180deg,#c9a04b,#a37a23); border-radius:3px; }

    .uc-filters .form-group { margin-bottom: .85rem; }
    .uc-filters label { font-size:.78rem; color:#475569; font-weight:600; text-transform: none; margin-bottom:.35rem; }
    .uc-filters .form-control { border-radius: 10px; border-color:#e5e7eb; font-size:.9rem; padding:.55rem .75rem; }
    .uc-filters .form-control:focus { border-color:#c9a04b; box-shadow: 0 0 0 .2rem rgba(201,160,75,.18); }

    .uc-btn { display:inline-flex; align-items:center; gap:.4rem; border-radius:10px; padding:.55rem 1.1rem; font-weight:600; font-size:.88rem; border:1px solid transparent; transition: all .2s ease; cursor:pointer; }
    .uc-btn-primary { background: linear-gradient(135deg,#c9a04b,#a37a23); color:#fff !important; box-shadow: 0 4px 12px rgba(201,160,75,.25); }
    .uc-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(201,160,75,.32); color:#fff; }
    .uc-btn-dark { background:#0f172a; color:#fff !important; }
    .uc-btn-dark:hover { background:#1e293b; color:#fff; }
    .uc-btn-ghost { background:#fff; color:#475569 !important; border-color:#e5e7eb; }
    .uc-btn-ghost:hover { color:#0f172a !important; border-color:#cbd5e1; }
    .uc-btn[disabled] { opacity:.55; cursor: not-allowed; transform:none !important; }

    .uc-toolbar {
        display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap;
        padding: .75rem 1rem; background: linear-gradient(180deg,#f8fafc,#fff); border:1px solid #e5e7eb; border-radius:12px; margin-bottom:.85rem;
    }
    .uc-toolbar .left { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }
    .uc-toolbar .right { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .uc-toolbar .count-pill { background:#fff; border:1px solid #e5e7eb; border-radius:999px; padding:.3rem .8rem; font-size:.78rem; color:#475569; font-weight:600; }
    .uc-toolbar .selected-pill { background:#0f172a; color:#fff; border-radius:999px; padding:.3rem .8rem; font-size:.78rem; font-weight:600; }

    .uc-table { width:100%; background:#fff; border:1px solid #e5e7eb; border-radius: 12px; overflow:hidden; }
    .uc-table thead th {
        background: #f8fafc; color:#475569; font-size:.75rem; text-transform: uppercase; letter-spacing:.04em;
        padding: .75rem .85rem; border-bottom:1px solid #e5e7eb; font-weight: 700;
    }
    .uc-table tbody td { padding: .85rem .85rem; border-bottom:1px solid #f1f5f9; vertical-align: middle; font-size:.88rem; }
    .uc-table tbody tr:last-child td { border-bottom: none; }
    .uc-table tbody tr:hover { background: #fafbfc; }
    .uc-table .uc-pk { width:36px; }
    .uc-table .name { color:#0f172a; font-weight:600; }
    .uc-table .username code { background:#f1f5f9; color:#0f172a; padding:.15rem .45rem; border-radius:6px; font-size:.82rem; }
    .uc-table .uc-role-pill {
        display:inline-block; padding:.18rem .55rem; border-radius:999px; font-size:.72rem; font-weight:700;
    }
    .uc-table .uc-role-student { background:#eff6ff; color:#1d4ed8; }
    .uc-table .uc-role-parent  { background:#fef3c7; color:#92400e; }
    .uc-table .uc-role-teacher { background:#ecfeff; color:#0e7490; }
    .uc-table .uc-role-admin   { background:#f3e8ff; color:#7e22ce; }

    .uc-pwd-badge { display:inline-flex; align-items:center; gap:.35rem; padding:.2rem .55rem; border-radius:999px; font-size:.72rem; font-weight:700; }
    .uc-pwd-ok   { background:#dcfce7; color:#166534; }
    .uc-pwd-no   { background:#fee2e2; color:#991b1b; }

    .uc-empty { padding: 3rem 1rem; text-align:center; color:#94a3b8; font-size:.95rem; background:#fff; border:1px dashed #cbd5e1; border-radius:12px; }
    .uc-empty .uc-empty-ico { font-size:2rem; opacity:.4; margin-bottom:.5rem; }

    .uc-notice { background: #fffbeb; border:1px solid #fde68a; border-radius:12px; padding:.85rem 1rem; font-size:.85rem; color:#78350f; display:flex; gap:.7rem; align-items:flex-start; margin-bottom:1rem; }
    .uc-notice .uc-notice-ico { flex-shrink:0; width:30px; height:30px; border-radius:8px; background:#fef3c7; color:#92400e; display:inline-flex; align-items:center; justify-content:center; }
    .uc-notice strong { display:block; margin-bottom:.15rem; color:#7c2d12; }

    .uc-success-banner { background: linear-gradient(135deg,#dcfce7,#bbf7d0); border:1px solid #86efac; border-radius:12px; padding:1rem 1.25rem; margin-bottom:1rem; color:#14532d; }
    .uc-success-banner .pw-box { display:inline-block; background:#fff; border:1px dashed #16a34a; border-radius:8px; padding:.45rem .8rem; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace; font-size:1.05rem; font-weight:700; color:#14532d; margin-{{ $isRtl ? 'right' : 'left' }}: .5rem; user-select:all; }

    @media (max-width: 768px) {
        .uc-table { font-size:.82rem; }
        .uc-table thead { display:none; }
        .uc-table, .uc-table tbody, .uc-table tr, .uc-table td { display:block; width:100%; }
        .uc-table tr { border:1px solid #e5e7eb; border-radius:10px; margin-bottom:.75rem; padding:.6rem; }
        .uc-table tr:hover { background: #fff; }
        .uc-table td { padding:.35rem .25rem; border:none; }
        .uc-table td::before {
            content: attr(data-label);
            display: inline-block; min-width: 90px; font-weight: 700; color:#475569; font-size:.74rem; text-transform: uppercase;
        }
        .uc-toolbar { padding:.6rem; }
    }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('user_cards.page_title')</h2>
        <div class="breadcrumb-wrapper mt-1">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('user_cards.breadcrumb_home')</a></li>
                <li class="breadcrumb-item">@lang('user_cards.breadcrumb_users')</li>
                <li class="breadcrumb-item active">@lang('user_cards.breadcrumb_cards')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    <p class="uc-lead">@lang('user_cards.page_lead')</p>

    @if(session('status'))
        <div class="uc-success-banner">
            <strong>✓</strong>
            {{ session('status') }}
            @if($flashPassword)
                <span class="pw-box">{{ $flashPassword }}</span>
            @endif
        </div>
    @endif
    @if($errors->any() || session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <div class="uc-notice">
        <span class="uc-notice-ico"><x-svg-icon name="info-circle" /></span>
        <div>
            <strong>@lang('user_cards.notice_title')</strong>
            @lang('user_cards.notice_body')
        </div>
    </div>

    <div class="uc-tabs" role="tablist">
        @php $base = url()->current(); @endphp
        <a class="uc-tab {{ $tab === 'students' ? 'is-active' : '' }}"
           href="{{ $base }}?tab=students{{ $filters['school_id'] ? '&school_id='.$filters['school_id'] : '' }}">
            <x-svg-icon name="people" />
            @lang('user_cards.tab_students_parents')
            <span class="count">{{ number_format($totalStudents) }}</span>
        </a>
        <a class="uc-tab {{ $tab === 'staff' ? 'is-active' : '' }}"
           href="{{ $base }}?tab=staff{{ $filters['school_id'] ? '&school_id='.$filters['school_id'] : '' }}">
            <x-svg-icon name="person-badge" />
            @lang('user_cards.tab_staff')
            <span class="count">{{ number_format($totalStaff) }}</span>
        </a>
    </div>

    <form method="GET" action="{{ route('admin.users.cards.index') }}" class="uc-panel uc-filters">
        <h5>@lang('user_cards.filters_title')</h5>
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="row">
            <div class="form-group col-md-4 col-12">
                <label>@lang('user_cards.filter_search')</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="@lang('user_cards.filter_search_ph')" />
            </div>

            @if($tab === 'students')
                <div class="form-group col-md-2 col-6">
                    <label>@lang('user_cards.filter_role')</label>
                    <select name="role" class="form-control">
                        <option value="">@lang('user_cards.all')</option>
                        <option value="student" {{ $filters['role'] === 'student' ? 'selected' : '' }}>@lang('user_cards.role_student')</option>
                        <option value="parent"  {{ $filters['role'] === 'parent'  ? 'selected' : '' }}>@lang('user_cards.role_parent')</option>
                    </select>
                </div>
                <div class="form-group col-md-2 col-6">
                    <label>@lang('user_cards.filter_grade')</label>
                    <select name="section_id" class="form-control">
                        <option value="">@lang('user_cards.all')</option>
                        @foreach($sections as $s)
                            <option value="{{ $s->id }}" {{ (int)$filters['section_id'] === (int)$s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 col-6">
                    <label>@lang('user_cards.filter_class')</label>
                    <select name="class_room_id" class="form-control">
                        <option value="">@lang('user_cards.all')</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ (int)$filters['class_room_id'] === (int)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="form-group col-md-3 col-6">
                    <label>@lang('user_cards.filter_role')</label>
                    <select name="role" class="form-control">
                        <option value="">@lang('user_cards.all')</option>
                        <option value="teacher"      {{ $filters['role'] === 'teacher'      ? 'selected' : '' }}>@lang('user_cards.role_teacher')</option>
                        <option value="school-admin" {{ $filters['role'] === 'school-admin' ? 'selected' : '' }}>@lang('user_cards.role_admin')</option>
                    </select>
                </div>
            @endif

            @if($schools->isNotEmpty())
                <div class="form-group col-md-{{ $tab === 'students' ? '2' : '3' }} col-6">
                    <label>@lang('user_cards.filter_school')</label>
                    <select name="school_id" class="form-control">
                        <option value="">@lang('user_cards.all')</option>
                        @foreach($schools as $sch)
                            <option value="{{ $sch->id }}" {{ (int)$filters['school_id'] === (int)$sch->id ? 'selected' : '' }}>{{ $sch->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <div class="d-flex" style="gap:.5rem;flex-wrap:wrap;">
            <button type="submit" class="uc-btn uc-btn-primary"><x-svg-icon name="search" /> @lang('user_cards.apply_filters')</button>
            <a href="{{ route('admin.users.cards.index', ['tab' => $tab]) }}" class="uc-btn uc-btn-ghost"><x-svg-icon name="arrow-repeat" /> @lang('user_cards.reset_filters')</a>
        </div>
    </form>

    <form id="ucGenerateForm" method="POST" action="{{ route('admin.users.cards.generate') }}" target="_blank">
        @csrf
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="hidden" name="q" value="{{ $filters['q'] }}">
        <input type="hidden" name="school_id" value="{{ $filters['school_id'] }}">
        <input type="hidden" name="section_id" value="{{ $filters['section_id'] }}">
        <input type="hidden" name="class_room_id" value="{{ $filters['class_room_id'] }}">
        <input type="hidden" name="role" value="{{ $filters['role'] }}">

        <div class="uc-toolbar">
            <div class="left">
                <label class="m-0 d-flex align-items-center" style="gap:.4rem;cursor:pointer;">
                    <input type="checkbox" id="ucSelectAll"> <span>@lang('user_cards.list_select_all')</span>
                </label>
                <span class="count-pill"><x-svg-icon name="list" /> @lang('user_cards.showing_count', ['count' => $showingCount])</span>
                <span class="selected-pill" id="ucSelectedPill" style="display:none;">
                    @lang('user_cards.list_selected_count', ['count' => '<span id="ucSelectedCount">0</span>'])
                </span>
            </div>
            <div class="right">
                <button type="submit" class="uc-btn uc-btn-dark" id="ucPrintSelectedBtn" disabled>
                    <x-svg-icon name="printer" /> @lang('user_cards.btn_print_selected')
                </button>
                <button type="submit" class="uc-btn uc-btn-primary" name="print_all" value="1" id="ucPrintAllBtn" @if($showingCount === 0) disabled @endif>
                    <x-svg-icon name="file-earmark-pdf" /> @lang('user_cards.btn_print_all_filtered')
                </button>
            </div>
        </div>

        @if($users->isEmpty())
            <div class="uc-empty">
                <div class="uc-empty-ico"><x-svg-icon name="search" /></div>
                @lang('user_cards.list_empty')
            </div>
        @else
        <div class="table-responsive">
            <table class="uc-table">
                <thead>
                    <tr>
                        <th class="uc-pk"></th>
                        <th>@lang('user_cards.col_name')</th>
                        <th>@lang('user_cards.col_username')</th>
                        <th>@lang('user_cards.col_role')</th>
                        @if($schools->isNotEmpty())<th>@lang('user_cards.col_school')</th>@endif
                        @if($tab === 'students')<th>@lang('user_cards.col_grade')</th>@endif
                        @if($tab === 'staff')<th>@lang('user_cards.col_job')</th>@endif
                        <th>@lang('user_cards.col_password_status')</th>
                        <th class="text-{{ $isRtl ? 'left' : 'right' }}">@lang('user_cards.col_actions')</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($users as $u)
                    <tr>
                        <td class="uc-pk" data-label="">
                            <input type="checkbox" name="user_ids[]" value="{{ $u['id'] }}" class="uc-row-check">
                        </td>
                        <td data-label="@lang('user_cards.col_name')" class="name">{{ $u['name'] }}</td>
                        <td data-label="@lang('user_cards.col_username')" class="username"><code>{{ $u['username'] ?? '—' }}</code></td>
                        <td data-label="@lang('user_cards.col_role')">
                            <span class="uc-role-pill uc-role-{{ $u['kind'] }}">@lang('user_cards.role_'.$u['kind'])</span>
                        </td>
                        @if($schools->isNotEmpty())
                            <td data-label="@lang('user_cards.col_school')">{{ $u['school_name'] ?? '—' }}</td>
                        @endif
                        @if($tab === 'students')
                            <td data-label="@lang('user_cards.col_grade')">
                                {{ $u['grade'] ?? '—' }}{{ $u['class'] ? ' / '.$u['class'] : '' }}
                            </td>
                        @endif
                        @if($tab === 'staff')
                            <td data-label="@lang('user_cards.col_job')">{{ $u['job_title'] ?? '—' }}</td>
                        @endif
                        <td data-label="@lang('user_cards.col_password_status')">
                            @if($u['password_ready'])
                                <span class="uc-pwd-badge uc-pwd-ok"><x-svg-icon name="check" /> @lang('user_cards.pwd_available')</span>
                            @else
                                <span class="uc-pwd-badge uc-pwd-no"><x-svg-icon name="x-lg" /> @lang('user_cards.pwd_unavailable')</span>
                            @endif
                        </td>
                        <td data-label="@lang('user_cards.col_actions')" class="text-{{ $isRtl ? 'left' : 'right' }}" style="white-space:nowrap;">
                            <button type="button" class="uc-btn uc-btn-ghost uc-print-one" data-id="{{ $u['id'] }}" {{ $u['password_ready'] ? '' : 'disabled' }} title="@lang('user_cards.btn_print_one')">
                                <x-svg-icon name="printer" /> @lang('user_cards.btn_print_one')
                            </button>
                            @if(auth()->id() !== $u['id'])
                                <button type="button"
                                        class="uc-btn uc-btn-primary uc-regen-btn"
                                        data-id="{{ $u['id'] }}"
                                        data-name="{{ $u['name'] }}"
                                        title="@lang('user_cards.pwd_regen_help')">
                                    <x-svg-icon name="arrow-repeat" /> @lang('user_cards.btn_regenerate')
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </form>

    {{-- Hidden regenerate form (one per user button click) --}}
    <form id="ucRegenForm" method="POST" action="" style="display:none;">@csrf</form>
</div>

<script>
(function() {
    var selectAll  = document.getElementById('ucSelectAll');
    var rows       = Array.prototype.slice.call(document.querySelectorAll('.uc-row-check'));
    var selBtn     = document.getElementById('ucPrintSelectedBtn');
    var selPill    = document.getElementById('ucSelectedPill');
    var selCount   = document.getElementById('ucSelectedCount');

    function recompute() {
        var n = rows.filter(function(r){ return r.checked; }).length;
        if (selCount) selCount.textContent = n;
        if (selPill)  selPill.style.display = n > 0 ? '' : 'none';
        if (selBtn)   selBtn.disabled = (n === 0);
    }
    if (selectAll) {
        selectAll.addEventListener('change', function(){
            rows.forEach(function(r){ r.checked = selectAll.checked; });
            recompute();
        });
    }
    rows.forEach(function(r){ r.addEventListener('change', recompute); });

    // Print-one buttons: uncheck all, check this one, submit
    document.querySelectorAll('.uc-print-one').forEach(function(btn){
        btn.addEventListener('click', function(){
            rows.forEach(function(r){ r.checked = false; });
            var id = btn.getAttribute('data-id');
            var match = rows.find(function(r){ return r.value === id; });
            if (match) { match.checked = true; }
            recompute();
            document.getElementById('ucGenerateForm').submit();
            setTimeout(function(){
                if (match) match.checked = false;
                recompute();
            }, 600);
        });
    });

    // Regenerate password
    document.querySelectorAll('.uc-regen-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = btn.getAttribute('data-id');
            var name = btn.getAttribute('data-name') || '';
            var msg = "@lang('user_cards.pwd_regen_confirm', ['name' => ':NAME'])".replace(':NAME', name);
            if (!window.confirm(msg)) return;
            var f = document.getElementById('ucRegenForm');
            f.action = "{{ url('admin/users/cards') }}/" + id + "/regenerate-password";
            f.submit();
        });
    });
})();
</script>
@endsection
