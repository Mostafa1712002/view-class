@extends('layouts.app')

@section('title', __('weekly_plan.notes_page_title'))
@section('body_class','theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@push('styles')
<style>
    .wpn-header { margin-bottom: 1.25rem; }
    .wpn-header h2 {
        font-size: 1.5rem; font-weight: 700; color: #0f172a;
        margin-bottom: .15rem; letter-spacing: -.2px;
    }
    .wpn-header .subtitle { color: #64748b; font-size: .88rem; }
    .wpn-header .breadcrumb {
        padding: 0; margin: 0; background: transparent; font-size: .85rem;
    }
    .wpn-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: 1rem 1.1rem; margin-bottom: 1rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
    }
    .wpn-toolbar {
        display: flex; justify-content: space-between; align-items: center;
        flex-wrap: wrap; gap: .55rem; margin-bottom: 1rem;
    }
    .wpn-toolbar .form-control {
        border: 1px solid #e2e8f0; border-radius: 10px; padding: .5rem .8rem;
        min-width: 240px; height: auto;
    }
    @media (max-width: 575.98px) {
        .wpn-toolbar form { flex-wrap: wrap; width: 100%; }
        .wpn-toolbar .form-control { min-width: 0; flex: 1 1 auto; }
    }
    .wpn-toolbar .form-control:focus {
        border-color: #fbbf24; box-shadow: 0 0 0 .2rem rgba(207,160,70,.16); outline: none;
    }
    .btn-gold {
        background: linear-gradient(135deg, #fcd34d, #d97706);
        border: 1px solid #d97706; color: #fff;
        font-weight: 600; padding: .5rem 1.1rem; border-radius: 10px;
        display: inline-flex; align-items: center; gap: .4rem;
    }
    .btn-gold:hover { color: #fff; transform: translateY(-1px); }
    .btn-ghost {
        background: #fff; border: 1px solid #e2e8f0; color: #334155;
        font-weight: 600; padding: .5rem 1rem; border-radius: 10px;
        display: inline-flex; align-items: center; gap: .35rem;
    }
    .btn-ghost:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
    .btn-ghost i { color: #b45309; }

    .wpn-surface {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
    }
    .wpn-table { margin: 0; width: 100%; }
    .wpn-table thead th {
        background: #f8fafc !important; color: #475569 !important;
        font-weight: 600; font-size: .78rem; text-transform: uppercase;
        letter-spacing: .5px; padding: .8rem 1rem; border-bottom: 1px solid #e5e7eb;
    }
    .wpn-table tbody td { padding: .85rem 1rem; vertical-align: middle; color: #0f172a; font-size: .9rem; }
    .wpn-table tbody tr + tr td { border-top: 1px solid #f1f5f9; }
    .wpn-table tbody tr:hover { background: #fafbfc; }
    .wpn-table .note-title { font-weight: 700; color: #0f172a; }
    .wpn-table .note-body  { color: #475569; line-height: 1.5; }
    .wpn-empty {
        background: #fff; border: 1px dashed #e5e7eb; border-radius: 14px;
        padding: 3rem 1rem; text-align: center; color: #64748b;
    }
    .wpn-empty i { font-size: 3rem; color: #cbd5e1; }
    .wpn-empty h4 { color: #0f172a; font-weight: 700; margin: .8rem 0 .3rem; }
    .wpn-badge-global {
        background: #fef3c7; color: #b45309; border: 1px solid #fde68a;
        font-size: .7rem; padding: .15rem .5rem; border-radius: 999px;
        font-weight: 600; margin-{{ $isRtl ? 'right' : 'left' }}: .4rem;
    }
    .wpn-actions { display: inline-flex; gap: .3rem; }
    .wpn-actions .btn-icon {
        width: 32px; height: 32px; display: inline-flex; align-items: center;
        justify-content: center; border-radius: 8px; border: 1px solid #e2e8f0;
        background: #fff; color: #475569; transition: all .15s ease;
    }
    .wpn-actions .btn-icon:hover { background: #f8fafc; color: #b45309; }
    .wpn-actions .btn-icon.danger:hover { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }

    .modal-content {
        border: none; border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    }
    .modal-header {
        border-bottom: 1px solid #f1f5f9; padding: 1.1rem 1.25rem;
    }
    .modal-header .modal-title { font-weight: 700; color: #0f172a; }
    .modal-body { padding: 1.1rem 1.25rem; }
    .modal-footer { border-top: 1px solid #f1f5f9; padding: 1rem 1.25rem; }
    .modal label { font-weight: 600; color: #475569; font-size: .85rem; }
    .modal .form-control, .modal textarea.form-control {
        border: 1px solid #e2e8f0; border-radius: 10px; padding: .55rem .8rem;
        font-size: .92rem; transition: all .15s;
    }
    .modal .form-control:focus {
        border-color: #fbbf24; box-shadow: 0 0 0 .2rem rgba(207,160,70,.16); outline: none;
    }
</style>
@endpush

@section('content')
<div class="content-body">
    <div class="wpn-header">
        <h2>@lang('weekly_plan.notes_page_title')</h2>
        <div class="subtitle">@lang('weekly_plan.notes_subtitle')</div>
        <ol class="breadcrumb mt-1">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('manage.weekly-plans.index') }}">@lang('weekly_plan.breadcrumb')</a></li>
            <li class="breadcrumb-item active">@lang('weekly_plan.notes_page_title')</li>
        </ol>
    </div>

    @include('components.alerts')

    <div class="wpn-toolbar">
        <form method="GET" action="{{ route('manage.weekly-plan-notes.index') }}" class="d-flex gap-2">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="@lang('weekly_plan.notes_search_placeholder')">
            <button class="btn-ghost" type="submit"><i class="la la-search"></i> @lang('weekly_plan.btn_search')</button>
        </form>
        <div class="d-flex gap-2">
            <a href="{{ route('manage.weekly-plans.index') }}" class="btn-ghost">
                <i class="la la-arrow-{{ $isRtl ? 'right' : 'left' }}"></i> @lang('weekly_plan.notes_back_to_plan')
            </a>
            <button type="button" class="btn-gold" data-toggle="modal" data-target="#noteAddModal" data-bs-toggle="modal" data-bs-target="#noteAddModal">
                <i class="la la-plus"></i> @lang('weekly_plan.notes_add_btn')
            </button>
        </div>
    </div>

    @if($templates->total() === 0)
        <div class="wpn-empty">
            <i class="la la-sticky-note"></i>
            <h4>@lang('weekly_plan.notes_empty')</h4>
        </div>
    @else
        <div class="wpn-surface">
            <div class="table-responsive">
                <table class="wpn-table">
                    <thead>
                        <tr>
                            <th>@lang('weekly_plan.notes_table_title')</th>
                            <th style="width:200px;">@lang('weekly_plan.notes_table_created_by')</th>
                            <th style="width:160px;">@lang('weekly_plan.notes_table_created_at')</th>
                            <th class="text-center" style="width:120px;">@lang('weekly_plan.notes_table_actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($templates as $tpl)
                            <tr>
                                <td>
                                    @if($tpl->title)
                                        <div class="note-title">{{ $tpl->title }}</div>
                                    @endif
                                    <div class="note-body">{{ \Illuminate\Support\Str::limit($tpl->body, 220) }}</div>
                                    @if(is_null($tpl->school_id))
                                        <span class="wpn-badge-global">@lang('weekly_plan.note_global_badge')</span>
                                    @endif
                                </td>
                                <td>{{ $tpl->creator?->name ?? '—' }}</td>
                                <td>{{ $tpl->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="text-center">
                                    <div class="wpn-actions">
                                        <button type="button" class="btn-icon edit-note-btn"
                                                data-id="{{ $tpl->id }}"
                                                data-title="{{ $tpl->title }}"
                                                data-body="{{ $tpl->body }}"
                                                title="@lang('weekly_plan.notes_btn_edit')">
                                            <i class="la la-edit"></i>
                                        </button>
                                        <form action="{{ route('manage.weekly-plan-notes.destroy', $tpl->id) }}" method="POST" class="d-inline" onsubmit="return confirm('@lang('weekly_plan.notes_confirm_delete')');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn-icon danger" title="@lang('weekly_plan.notes_btn_delete')"><i class="la la-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">
            {{ $templates->withQueryString()->links() }}
        </div>
    @endif
</div>

{{-- Add modal --}}
<div class="modal fade" id="noteAddModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('manage.weekly-plan-notes.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">@lang('weekly_plan.notes_modal_title_add')</h5>
                    <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>@lang('weekly_plan.notes_label_title')</label>
                        <input type="text" name="title" class="form-control" maxlength="200">
                    </div>
                    <div class="mb-3">
                        <label>@lang('weekly_plan.notes_label_body') <span class="text-danger">*</span></label>
                        <textarea name="body" rows="4" class="form-control" required maxlength="2000"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-ghost" data-dismiss="modal" data-bs-dismiss="modal">@lang('weekly_plan.notes_btn_cancel')</button>
                    <button type="submit" class="btn-gold"><i class="la la-save"></i> @lang('weekly_plan.notes_btn_save')</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit modal --}}
<div class="modal fade" id="noteEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="" id="noteEditForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">@lang('weekly_plan.notes_modal_title_edit')</h5>
                    <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>@lang('weekly_plan.notes_label_title')</label>
                        <input type="text" name="title" id="editNoteTitle" class="form-control" maxlength="200">
                    </div>
                    <div class="mb-3">
                        <label>@lang('weekly_plan.notes_label_body') <span class="text-danger">*</span></label>
                        <textarea name="body" id="editNoteBody" rows="4" class="form-control" required maxlength="2000"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-ghost" data-dismiss="modal" data-bs-dismiss="modal">@lang('weekly_plan.notes_btn_cancel')</button>
                    <button type="submit" class="btn-gold"><i class="la la-save"></i> @lang('weekly_plan.notes_btn_save')</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    document.querySelectorAll('.edit-note-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = this.getAttribute('data-id');
            var title = this.getAttribute('data-title') || '';
            var body = this.getAttribute('data-body') || '';
            document.getElementById('editNoteTitle').value = title;
            document.getElementById('editNoteBody').value = body;
            document.getElementById('noteEditForm').action = '{{ url('/manage/weekly-plan-notes') }}/' + id;
            // Support both jQuery (Bootstrap 4) and Bootstrap 5 modal APIs
            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
                window.jQuery('#noteEditModal').modal('show');
            } else if (window.bootstrap && window.bootstrap.Modal) {
                new window.bootstrap.Modal(document.getElementById('noteEditModal')).show();
            }
        });
    });
})();
</script>
@endpush
