@extends('layouts.app')

@section('title', __('domains.page_title', ['name' => $subject->name]))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .tree-hero {
        background: linear-gradient(135deg, #fff6dd 0%, #ffffff 65%);
        border: 1px solid #f1e4b8 !important;
    }
    body.theme-light .tree-hero h4 { color: #0f172a; margin-bottom: .25rem; }
    body.theme-light .add-subject-btn {
        background: linear-gradient(135deg, var(--gold-200), var(--gold-500)) !important;
        color: #fff !important; border: none; border-radius: 10px; font-weight: 600;
    }
    body.theme-light .btn-soft {
        background: #fff; border: 1px solid #e5e7eb; color: #475569;
        border-radius: 10px; font-weight: 500;
    }
    body.theme-light .domain-tree ul { list-style: none; padding-inline-start: 1.25rem; }
    body.theme-light .domain-tree > li { font-weight: 700; color: #0f172a; }
    body.theme-light .domain-tree li { padding: .2rem 0; }
    body.theme-light .empty-tree { text-align: center; padding: 2rem 1rem; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('domains.management') — {{ $subject->name }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.subjects.index') }}">@lang('sprint4.subjects.plural')</a></li>
                <li class="breadcrumb-item active">@lang('domains.management')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card tree-hero mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1"><i class="la la-sitemap" style="color: var(--gold-400);"></i> {{ $subject->name }}</h4>
                <div class="text-muted small">{{ $domains->count() }} @lang('domains.count_label')</div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn add-subject-btn" data-toggle="modal" data-bs-toggle="modal" data-target="#addDomainModal" data-bs-target="#addDomainModal">
                    <x-svg-icon name="plus" /> @lang('domains.add')
                </button>
                <button type="button" class="btn btn-soft" data-toggle="modal" data-bs-toggle="modal" data-target="#domainTreeModal" data-bs-target="#domainTreeModal">
                    <x-svg-icon name="diagram-3" /> @lang('domains.tree')
                </button>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><x-svg-icon name="search" /></span>
                </div>
                <input type="text" id="domain-search" class="form-control" placeholder="@lang('domains.search')">
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle mb-0" id="domains-table">
                    <thead>
                        <tr>
                            <th>@lang('domains.domain')</th>
                            <th>@lang('domains.template')</th>
                            <th style="width:140px;">@lang('common.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($domains as $domain)
                            <tr data-name="{{ $domain->name }}">
                                <td>{{ $domain->name }}</td>
                                <td>—</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary edit-domain"
                                            data-id="{{ $domain->id }}" data-name="{{ $domain->name }}"
                                            data-toggle="modal" data-bs-toggle="modal" data-target="#editDomainModal" data-bs-target="#editDomainModal"
                                            title="@lang('common.edit')"><x-svg-icon name="pencil-square" /></button>
                                    <form action="{{ route('admin.subjects.domains.destroy', [$subject->id, $domain->id]) }}" method="POST" class="d-inline" onsubmit="return confirm(@json(__('domains.confirm_delete')))">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="@lang('common.delete')"><x-svg-icon name="trash" /></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr id="domains-empty"><td colspan="3" class="text-center text-muted">@lang('domains.empty')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Add modal --}}
<div class="modal fade" id="addDomainModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('admin.subjects.domains.store', $subject->id) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><x-svg-icon name="plus-circle" /> @lang('domains.add')</h5>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-semibold">@lang('domains.name') <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft" data-dismiss="modal" data-bs-dismiss="modal">@lang('common.close')</button>
                <button type="submit" class="btn add-subject-btn"><x-svg-icon name="save" /> @lang('common.save')</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit modal --}}
<div class="modal fade" id="editDomainModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="edit-domain-form" method="POST" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title"><x-svg-icon name="pencil-square" /> @lang('domains.edit')</h5>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-semibold">@lang('domains.name') <span class="text-danger">*</span></label>
                <input type="text" name="name" id="edit-domain-name" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft" data-dismiss="modal" data-bs-dismiss="modal">@lang('common.close')</button>
                <button type="submit" class="btn add-subject-btn"><x-svg-icon name="save" /> @lang('common.save')</button>
            </div>
        </form>
    </div>
</div>

{{-- Tree modal --}}
<div class="modal fade" id="domainTreeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><x-svg-icon name="diagram-3" /> @lang('domains.tree')</h5>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body domain-tree">
                <ul>
                    <li><i class="la la-folder" style="color:var(--gold-400);"></i> @lang('domains.root')
                        @if($domains->count())
                            <ul>
                                @foreach($domains as $domain)
                                    <li><x-svg-icon name="chevron-left" /> {{ $domain->name }}</li>
                                @endforeach
                            </ul>
                        @else
                            <ul><li class="text-muted">@lang('domains.empty')</li></ul>
                        @endif
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    // Edit modal population
    var editTemplate = @json(route('admin.subjects.domains.update', [$subject->id, 'DOMAIN_ID']));
    document.querySelectorAll('.edit-domain').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('edit-domain-form').setAttribute('action', editTemplate.replace('DOMAIN_ID', btn.dataset.id));
            document.getElementById('edit-domain-name').value = btn.dataset.name;
        });
    });
    // Client-side search
    var search = document.getElementById('domain-search');
    if (search) {
        search.addEventListener('input', function () {
            var q = this.value.trim().toLowerCase();
            document.querySelectorAll('#domains-table tbody tr[data-name]').forEach(function (row) {
                var name = (row.getAttribute('data-name') || '').toLowerCase();
                row.style.display = name.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    }
})();
</script>
@endpush
@endsection
