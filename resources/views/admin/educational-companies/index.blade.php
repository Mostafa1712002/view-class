@extends('layouts.app')

@section('title', __('educational_companies.title'))

@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">@lang('educational_companies.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('educational_companies.title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12">
        <div class="d-flex flex-wrap gap-1 justify-content-md-end">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-bs-toggle="modal" data-target="#addCompanyModal" data-bs-target="#addCompanyModal">
                <x-svg-icon name="plus" /> @lang('educational_companies.add_company')
            </button>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-header">
            <form action="{{ route('admin.educational-companies.index') }}" method="GET" class="d-flex">
                <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:280px" placeholder="@lang('educational_companies.search_placeholder')">
                <button type="submit" class="btn btn-outline-secondary btn-sm ms-1">@lang('common.search')</button>
                @if($q)
                    <a href="{{ route('admin.educational-companies.index') }}" class="btn btn-outline-secondary btn-sm ms-1">@lang('common.clear')</a>
                @endif
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>@lang('educational_companies.logo')</th>
                            <th>@lang('common.name')</th>
                            <th>@lang('educational_companies.complexes_count')</th>
                            <th>@lang('educational_companies.schools_count')</th>
                            <th>@lang('common.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $company)
                        <tr>
                            <td>{{ $loop->iteration + ($companies->currentPage() - 1) * $companies->perPage() }}</td>
                            <td>
                                @if($company->logo)
                                    <img src="{{ asset('storage/' . $company->logo) }}" alt="" style="height:32px; max-width:60px; object-fit:contain;">
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ app()->getLocale() === 'en' ? ($company->name_en ?: $company->name_ar) : ($company->name_ar ?: $company->name_en) }}</strong>
                            </td>
                            <td>{{ $company->compounds_count }}</td>
                            <td>{{ $company->schools_count }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('admin.educational-companies.show', $company) }}" class="btn btn-sm btn-outline-info" title="@lang('common.view')"><x-svg-icon name="eye" /></a>
                                    <button type="button" class="btn btn-sm btn-outline-primary" title="@lang('common.edit')" data-toggle="modal" data-bs-toggle="modal" data-target="#editCompanyModal{{ $company->id }}" data-bs-target="#editCompanyModal{{ $company->id }}"><x-svg-icon name="pencil" /></button>
                                    <form action="{{ route('admin.educational-companies.destroy', $company) }}" method="POST" onsubmit="return confirm(@json(__('common.confirm_delete')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="@lang('common.delete')"><x-svg-icon name="trash" /></button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ __('educational_companies.no_companies') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $companies->links() }}
            </div>
        </div>
    </div>
</div>

{{-- Edit modals live outside the table/card: inside .table-responsive the card's
     transform traps position:fixed, so the dialog was clipped under the backdrop. --}}
@foreach($companies as $company)
                        {{-- Edit modal --}}
                        <div class="modal fade" id="editCompanyModal{{ $company->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form action="{{ route('admin.educational-companies.update', $company) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">@lang('educational_companies.edit_company')</h5>
                                            <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">@lang('educational_companies.name_ar')</label>
                                                <input type="text" class="form-control" name="name_ar" value="{{ $company->name_ar }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">@lang('educational_companies.name_en')</label>
                                                <input type="text" class="form-control" name="name_en" value="{{ $company->name_en }}">
                                            </div>
                                            <div class="mb-0">
                                                <label class="form-label">@lang('educational_companies.logo')</label>
                                                @if($company->logo)
                                                    <div class="mb-2"><img src="{{ asset('storage/' . $company->logo) }}" style="max-height:50px;"></div>
                                                @endif
                                                <input type="file" class="form-control" name="logo" accept="image/*">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">@lang('common.cancel')</button>
                                            <button type="submit" class="btn btn-primary">@lang('common.save')</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
@endforeach

{{-- Add modal --}}
<div class="modal fade" id="addCompanyModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.educational-companies.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">@lang('educational_companies.add_company')</h5>
                    <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">@lang('educational_companies.name_ar')</label>
                        <input type="text" class="form-control" name="name_ar" value="{{ old('name_ar') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">@lang('educational_companies.name_en')</label>
                        <input type="text" class="form-control" name="name_en" value="{{ old('name_en') }}">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">@lang('educational_companies.logo')</label>
                        <input type="file" class="form-control" name="logo" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">@lang('common.cancel')</button>
                    <button type="submit" class="btn btn-primary">@lang('common.save')</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
