@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('canteen.actions.assign_manager'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('canteen.actions.assign_manager')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.canteens.index') }}">@lang('canteen.title')</a></li>
            <li class="breadcrumb-item active">{{ $canteen->name_ar }}</li>
        </ol>
    </div>
</div>
<div class="content-body"><div class="card"><div class="card-body">
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 pr-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('admin.canteens.manager.assign', $canteen->id) }}">
        @csrf @method('PUT')
        <div class="form-group mb-3">
            <label class="form-label">@lang('canteen.fields.manager')</label>
            <select name="manager_id" class="custom-select">
                <option value="">@lang('canteen.no_manager')</option>
                @foreach($admins as $a)
                    <option value="{{ $a->id }}" @selected((string)$canteen->manager_id===(string)$a->id)>{{ $a->name }}</option>
                @endforeach
            </select>
            @if($admins->isEmpty())<small class="text-muted d-block mt-1">@lang('canteen.no_admins')</small>@endif
        </div>
        <div class="d-flex" style="gap:.5rem;">
            <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('canteen.actions.save')</button>
            <a href="{{ route('admin.canteens.index') }}" class="btn btn-outline-secondary">@lang('canteen.actions.cancel')</a>
        </div>
    </form>
</div></div></div>
@endsection
