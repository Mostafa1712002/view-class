@extends('layouts.app')

@section('title', __('school_search.title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .ss-card { border-radius: 18px; background: #fff;
        box-shadow: 0 4px 18px rgba(15,23,42,.05);
        border: 1px solid #e5e7eb; padding: 1.25rem 1.4rem; margin-bottom: 1.25rem; }
    body.theme-light .ss-section-title { font-weight: 800; color: #0f172a;
        font-size: 1.05rem; margin: 0 0 .9rem; display: flex; align-items: center; gap: .55rem; }
    body.theme-light .ss-section-title i { width: 32px; height: 32px; border-radius: 10px;
        background: linear-gradient(135deg, #fff6dd, #fde8ad);
        color: var(--gold-500, #cfa046); display: inline-flex;
        align-items: center; justify-content: center; font-size: 1rem; }
    body.theme-light .ss-intro { color: #64748b; font-size: .9rem; margin-top: -.55rem; margin-bottom: 1rem; }

    body.theme-light .ss-form .form-label { font-weight: 600; color: #0f172a;
        font-size: .82rem; margin-bottom: .35rem; }
    body.theme-light .ss-form .form-control { border-radius: 10px; border: 1px solid #e5e7eb;
        padding: .55rem .9rem; font-size: .92rem; background: #fff;
        transition: border-color .15s, box-shadow .15s; }
    body.theme-light .ss-form .form-control:focus { border-color: var(--gold-400, #d4ad57);
        box-shadow: 0 0 0 3px rgba(207,160,70,.18); outline: 0; }

    body.theme-light .ss-toggles { display: flex; flex-wrap: wrap; gap: 1.1rem;
        margin: 1rem 0 .5rem; padding-top: .85rem; border-top: 1px dashed #e5e7eb; }
    body.theme-light .ss-toggle { display: inline-flex; align-items: center; gap: .45rem;
        background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 999px;
        padding: .4rem .85rem; font-size: .85rem; color: #475569; cursor: pointer; }
    body.theme-light .ss-toggle input { accent-color: var(--gold-500, #cfa046); margin: 0; }
    body.theme-light .ss-toggle small { color: #94a3b8; font-size: .72rem; }

    body.theme-light .ss-actions { display: flex; gap: .55rem; flex-wrap: wrap;
        margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f1f5f9; }
    body.theme-light .ss-btn-submit { background: linear-gradient(135deg, #4ade80, #16a34a);
        color: #fff !important; border: 0; padding: .55rem 1.4rem; font-weight: 700;
        border-radius: 10px; box-shadow: 0 4px 14px rgba(22,163,74,.25); }
    body.theme-light .ss-btn-submit:hover { transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(22,163,74,.32); }
    body.theme-light .ss-btn-reset { background: #fff; border: 1px solid #e5e7eb;
        color: #475569; padding: .55rem 1.1rem; border-radius: 10px; font-weight: 500; }

    body.theme-light .ss-results { width: 100%; }
    body.theme-light .ss-results thead th { font-size: .76rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .3px; color: #64748b;
        background: #f8fafc; padding: .8rem .85rem; border-bottom: 1px solid #e5e7eb; }
    body.theme-light .ss-results tbody td { vertical-align: middle; padding: .85rem;
        border-bottom: 1px solid #f1f5f9; font-size: .9rem; color: #0f172a; }
    body.theme-light .ss-results tbody tr:hover { background: #fafbfc; }
    body.theme-light .ss-avatar { width: 34px; height: 34px; border-radius: 50%;
        background: linear-gradient(135deg, #fef6df, #fde2a8);
        color: var(--gold-500, #cfa046); font-weight: 700;
        display: inline-flex; align-items: center; justify-content: center;
        margin-inline-end: .55rem; font-size: .85rem; }
    body.theme-light .ss-name { color: #0f172a; font-weight: 600; text-decoration: none; }
    body.theme-light .ss-sub { color: #94a3b8; font-size: .76rem; display: block; }
    body.theme-light .ss-chip { display: inline-block; padding: .15rem .55rem;
        background: #f1f5f9; border-radius: 999px; font-size: .72rem; color: #475569; }
    body.theme-light .ss-status-active { background: #dcfce7; color: #166534; }
    body.theme-light .ss-status-inactive { background: #fee2e2; color: #991b1b; }
    body.theme-light .ss-status-pending { background: #fef3c7; color: #92400e; }
    body.theme-light .ss-view-btn { display: inline-flex; align-items: center; gap: .3rem;
        padding: .35rem .75rem; background: #fff; border: 1px solid #e5e7eb;
        border-radius: 8px; color: #475569; font-size: .82rem; text-decoration: none;
        transition: background .15s, color .15s; }
    body.theme-light .ss-view-btn:hover { background: var(--gold-500, #cfa046);
        color: #fff; border-color: var(--gold-500, #cfa046); }

    body.theme-light .ss-empty { padding: 2.5rem 1rem; text-align: center;
        color: #94a3b8; font-size: .95rem; }
    body.theme-light .ss-empty i { font-size: 2.6rem; color: #cbd5e1;
        display: block; margin-bottom: .6rem; }

    body.theme-light .ss-breadcrumb { display: flex; flex-wrap: wrap; gap: .35rem;
        align-items: center; color: #94a3b8; font-size: .85rem; margin-bottom: 1rem; }
    body.theme-light .ss-breadcrumb a { color: #64748b; text-decoration: none; }
    body.theme-light .ss-breadcrumb a:hover { color: var(--gold-500, #cfa046); }
    body.theme-light .ss-breadcrumb .sep { color: #cbd5e1; }
    body.theme-light .ss-breadcrumb .current { color: #0f172a; font-weight: 600; }

    @media (max-width: 575px) {
        body.theme-light .ss-card { padding: 1rem .9rem; }
        body.theme-light .ss-actions { flex-direction: column; }
        body.theme-light .ss-actions .ss-btn-submit,
        body.theme-light .ss-actions .ss-btn-reset { width: 100%; }
    }
</style>
@endpush

@section('content')
<div class="content-body">

    <nav class="ss-breadcrumb" aria-label="breadcrumb">
        <a href="{{ url('/') }}">@lang('school_search.breadcrumb_home')</a>
        <span class="sep">›</span>
        <a href="{{ route('admin.users.students.index') }}">@lang('school_search.breadcrumb_students')</a>
        <span class="sep">›</span>
        <span class="current">@lang('school_search.breadcrumb_current')</span>
    </nav>

    <div class="ss-card">
        <h4 class="ss-section-title"><i class="la la-search"></i> @lang('school_search.section_filters')</h4>
        <p class="ss-intro">@lang('school_search.intro')</p>

        <form action="{{ route('admin.users.students.global-search') }}" method="GET" class="ss-form" id="ssForm">
            <div class="row g-3">
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label" for="ss-username">@lang('school_search.username')</label>
                    <input type="text" id="ss-username" name="username" class="form-control"
                           value="{{ $filters['username'] }}" autocomplete="off" />
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label" for="ss-email">@lang('school_search.email')</label>
                    <input type="text" id="ss-email" name="email" class="form-control"
                           value="{{ $filters['email'] }}" autocomplete="off" />
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label" for="ss-nid">@lang('school_search.national_id')</label>
                    <input type="text" id="ss-nid" name="national_id" class="form-control"
                           value="{{ $filters['national_id'] }}" autocomplete="off" />
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label" for="ss-phone">@lang('school_search.phone')</label>
                    <input type="text" id="ss-phone" name="phone" class="form-control"
                           value="{{ $filters['phone'] }}" autocomplete="off" />
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label" for="ss-passport">@lang('school_search.passport')</label>
                    <input type="text" id="ss-passport" name="passport" class="form-control"
                           value="{{ $filters['passport'] }}" autocomplete="off" />
                </div>
            </div>

            <div class="ss-toggles">
                <label class="ss-toggle">
                    <input type="checkbox" name="advanced" value="1" {{ $filters['advanced'] ? 'checked' : '' }} />
                    <span>@lang('school_search.advanced')</span>
                    <small>— @lang('school_search.advanced_hint')</small>
                </label>
                <label class="ss-toggle">
                    <input type="checkbox" name="auto" id="ssAuto" value="1" {{ $filters['auto'] ? 'checked' : '' }} />
                    <span>@lang('school_search.auto_search')</span>
                    <small>— @lang('school_search.auto_search_hint')</small>
                </label>
            </div>

            <div class="ss-actions">
                <button type="submit" class="btn ss-btn-submit">
                    <i class="la la-search"></i> @lang('school_search.submit')
                </button>
                <a href="{{ route('admin.users.students.global-search') }}" class="btn ss-btn-reset">
                    <i class="la la-redo"></i> @lang('school_search.reset')
                </a>
            </div>
        </form>
    </div>

    @if($hasAny)
        <div class="ss-card">
            <h4 class="ss-section-title"><i class="la la-list"></i> @lang('school_search.results_title')</h4>
            <p class="ss-intro">@lang('school_search.results_count', ['count' => $students->total()])</p>

            @if($students->total() === 0)
                <div class="ss-empty">
                    <i class="la la-search-minus"></i>
                    @lang('school_search.no_results')
                </div>
            @else
                <div class="table-responsive">
                    <table class="table ss-results align-middle mb-0">
                        <thead>
                            <tr>
                                <th>@lang('school_search.col_name')</th>
                                <th>@lang('school_search.col_national_id')</th>
                                <th>@lang('school_search.col_phone')</th>
                                <th>@lang('school_search.col_email')</th>
                                <th>@lang('school_search.col_school')</th>
                                <th>@lang('school_search.col_grade')</th>
                                <th>@lang('school_search.col_class')</th>
                                <th>@lang('school_search.col_status')</th>
                                <th style="text-align:end;">@lang('school_search.col_actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($students as $s)
                            @php
                                $statusKey = $s->status ?: ($s->is_active ? 'active' : 'inactive');
                                $statusClass = match($statusKey) {
                                    'active' => 'ss-status-active',
                                    'inactive' => 'ss-status-inactive',
                                    default => 'ss-status-pending',
                                };
                                $statusLabel = __('school_search.status_'.($statusKey ?: 'pending'));
                                $initial = mb_substr($s->name ?? '?', 0, 1);
                            @endphp
                            <tr>
                                <td>
                                    <span class="d-inline-flex align-items-center">
                                        <span class="ss-avatar">{{ $initial }}</span>
                                        <span>
                                            <a href="{{ route('admin.users.students.show', $s->id) }}" class="ss-name">{{ $s->name }}</a>
                                            <small class="ss-sub">{{ '@'.$s->username }}</small>
                                        </span>
                                    </span>
                                </td>
                                <td>{{ $s->national_id ?: '—' }}</td>
                                <td>{{ $s->phone ?: '—' }}</td>
                                <td>{{ $s->email ?: '—' }}</td>
                                <td>
                                    @php
                                        $schoolName = optional($s->school)->name_ar ?: optional($s->school)->name;
                                    @endphp
                                    {{ $schoolName ?: '—' }}
                                </td>
                                <td>
                                    @if(optional($s->section)->name)
                                        <span class="ss-chip">{{ $s->section->name }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if(optional($s->classRoom)->name)
                                        <span class="ss-chip">{{ $s->classRoom->name }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td><span class="ss-chip {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                <td style="text-align:end;">
                                    <a href="{{ route('admin.users.students.show', $s->id) }}" class="ss-view-btn">
                                        <i class="la la-eye"></i> @lang('school_search.view_student')
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $students->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="ss-card">
            <div class="ss-empty">
                <i class="la la-search"></i>
                @lang('school_search.empty_state')
            </div>
        </div>
    @endif

</div>

@push('scripts')
<script>
(function() {
    // Auto-search: when toggled on, submit form after a short debounce as the
    // user types in any filter field. Pure progressive enhancement — the form
    // still works the regular way without JS.
    var form = document.getElementById('ssForm');
    var autoBox = document.getElementById('ssAuto');
    if (!form || !autoBox) return;
    var timer = null;
    form.querySelectorAll('input[type="text"]').forEach(function (input) {
        input.addEventListener('input', function () {
            if (!autoBox.checked) return;
            clearTimeout(timer);
            timer = setTimeout(function () { form.submit(); }, 600);
        });
    });
})();
</script>
@endpush

@endsection
