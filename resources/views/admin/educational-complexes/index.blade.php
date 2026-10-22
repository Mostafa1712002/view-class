@extends('layouts.app')

@section('title', __('educational_complexes.title'))

@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">@lang('educational_complexes.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('educational_complexes.title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12">
        <div class="d-flex flex-wrap gap-1 justify-content-md-end">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-bs-toggle="modal" data-target="#addComplexModal" data-bs-target="#addComplexModal">
                <x-svg-icon name="plus" /> @lang('educational_complexes.add_complex')
            </button>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-header">
            <form action="{{ route('admin.educational-complexes.index') }}" method="GET" class="d-flex flex-wrap gap-1">
                <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:240px" placeholder="@lang('educational_complexes.search_placeholder')">
                <select name="company" class="form-control form-control-sm" style="max-width:220px">
                    <option value="">@lang('educational_complexes.all_companies')</option>
                    @foreach($companies as $c)
                        <option value="{{ $c->id }}" @selected((string) $companyId === (string) $c->id)>{{ $c->name_ar ?: $c->name_en }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-outline-secondary btn-sm">@lang('common.search')</button>
                @if($q || $companyId)
                    <a href="{{ route('admin.educational-complexes.index') }}" class="btn btn-outline-secondary btn-sm">@lang('common.clear')</a>
                @endif
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>@lang('educational_complexes.logo')</th>
                            <th>@lang('common.name')</th>
                            <th>@lang('educational_complexes.company')</th>
                            <th>@lang('educational_complexes.schools_count')</th>
                            <th>@lang('common.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($complexes as $complex)
                        <tr>
                            <td>{{ $loop->iteration + ($complexes->currentPage() - 1) * $complexes->perPage() }}</td>
                            <td>
                                @if($complex->logo)
                                    <img src="{{ asset('storage/' . $complex->logo) }}" alt="" style="height:32px; max-width:60px; object-fit:contain;">
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ app()->getLocale() === 'en' ? ($complex->name_en ?: $complex->name_ar) : ($complex->name_ar ?: $complex->name_en) }}</strong>
                            </td>
                            <td>{{ $complex->company?->name_ar ?: $complex->company?->name_en ?: __('educational_complexes.no_company') }}</td>
                            <td>{{ $complex->schools_count }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary" title="@lang('common.edit')" data-toggle="modal" data-bs-toggle="modal" data-target="#editComplexModal{{ $complex->id }}" data-bs-target="#editComplexModal{{ $complex->id }}"><x-svg-icon name="pencil" /></button>
                                    <form action="{{ route('admin.educational-complexes.destroy', $complex) }}" method="POST" onsubmit="return confirm(@json(__('common.confirm_delete')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="@lang('common.delete')"><x-svg-icon name="trash" /></button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ __('educational_complexes.no_complexes') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $complexes->links() }}
            </div>
        </div>
    </div>
</div>

{{-- Edit modals live outside the table/card: inside .table-responsive the card's
     transform traps position:fixed, so the dialog was clipped under the backdrop. --}}
@foreach($complexes as $complex)
                        {{-- Edit modal --}}
                        <div class="modal fade" id="editComplexModal{{ $complex->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form action="{{ route('admin.educational-complexes.update', $complex) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">@lang('educational_complexes.edit_complex')</h5>
                                            <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">@lang('educational_complexes.name_ar')</label>
                                                <input type="text" class="form-control" name="name_ar" value="{{ $complex->name_ar }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">@lang('educational_complexes.name_en')</label>
                                                <input type="text" class="form-control" name="name_en" value="{{ $complex->name_en }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">@lang('educational_complexes.company')</label>
                                                <select class="form-control" name="educational_company_id">
                                                    <option value="">@lang('educational_complexes.no_company')</option>
                                                    @foreach($companies as $c)
                                                        <option value="{{ $c->id }}" @selected($complex->educational_company_id == $c->id)>{{ $c->name_ar ?: $c->name_en }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-0">
                                                <label class="form-label">@lang('educational_complexes.logo')</label>
                                                @if($complex->logo)
                                                    <div class="mb-2"><img src="{{ asset('storage/' . $complex->logo) }}" style="max-height:50px;"></div>
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
<div class="modal fade" id="addComplexModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.educational-complexes.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">@lang('educational_complexes.add_complex')</h5>
                    <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">@lang('educational_complexes.name_ar')</label>
                        <input type="text" class="form-control" name="name_ar" value="{{ old('name_ar') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">@lang('educational_complexes.name_en')</label>
                        <input type="text" class="form-control" name="name_en" value="{{ old('name_en') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">@lang('educational_complexes.company')</label>
                        <select class="form-control" name="educational_company_id">
                            <option value="">@lang('educational_complexes.no_company')</option>
                            @foreach($companies as $c)
                                <option value="{{ $c->id }}" @selected((string) $companyId === (string) $c->id)>{{ $c->name_ar ?: $c->name_en }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">@lang('educational_complexes.logo')</label>
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
