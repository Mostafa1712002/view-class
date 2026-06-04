@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('canteen.balances.edit'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('canteen.balances.edit') — {{ $student->name }}</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.canteen-balances.index') }}">@lang('canteen.balances.title')</a></li>
            <li class="breadcrumb-item active">{{ $student->name }}</li>
        </ol>
    </div>
</div>
<div class="content-body"><div class="row">
    <div class="col-md-7">
        <div class="card"><div class="card-body">
            @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 pr-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

            <p class="mb-3">@lang('canteen.balances.current'): <strong style="font-size:1.3rem;">{{ number_format((float) ($balance->balance ?? 0), 2) }}</strong></p>

            <form method="POST" action="{{ route('admin.canteen-balances.update', $student->id) }}">
                @csrf @method('PUT')
                <div class="form-group mb-3">
                    <label class="form-label">@lang('canteen.balances.fields.type') <span class="text-danger">*</span></label>
                    @php $t = old('type', 'add'); @endphp
                    <select name="type" class="custom-select">
                        <option value="add" @selected($t==='add')>@lang('canteen.balances.types.add')</option>
                        <option value="deduct" @selected($t==='deduct')>@lang('canteen.balances.types.deduct')</option>
                        <option value="set" @selected($t==='set')>@lang('canteen.balances.types.set')</option>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">@lang('canteen.balances.fields.amount') <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount') }}" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">@lang('canteen.balances.fields.note')</label>
                    <textarea name="note" rows="2" class="form-control" maxlength="500">{{ old('note') }}</textarea>
                </div>
                <div class="d-flex" style="gap:.5rem;">
                    <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('canteen.balances.save')</button>
                    <a href="{{ route('admin.canteen-balances.index') }}" class="btn btn-outline-secondary">@lang('canteen.actions.cancel')</a>
                    <a href="{{ route('admin.canteen-balances.history', $student->id) }}" class="btn btn-outline-info"><i class="la la-history"></i> @lang('canteen.balances.history')</a>
                </div>
            </form>
        </div></div>
    </div>
</div></div>
@endsection
