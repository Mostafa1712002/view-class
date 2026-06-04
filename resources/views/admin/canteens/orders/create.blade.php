@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('canteen.orders.add'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('canteen.orders.add')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.canteen-orders.index') }}">@lang('canteen.orders.title')</a></li>
            <li class="breadcrumb-item active">@lang('canteen.orders.add')</li>
        </ol>
    </div>
</div>
<div class="content-body">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 pr-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    {{-- Step 1: pick the canteen (reloads to show its products) --}}
    <div class="card mb-3"><div class="card-body">
        <form method="GET" action="{{ route('admin.canteen-orders.create') }}" class="form-row align-items-end">
            <div class="form-group col-md-6 mb-0">
                <label class="form-label small mb-1">@lang('canteen.orders.fields.canteen')</label>
                <select name="canteen" class="custom-select" onchange="this.form.submit()">
                    <option value="">@lang('canteen.orders.choose_canteen')</option>
                    @foreach($canteens as $c)
                        <option value="{{ $c->id }}" @selected($selected && $selected->id===$c->id)>{{ $c->name_ar }}</option>
                    @endforeach
                </select>
            </div>
            @if($canteens->isEmpty())<div class="col-12"><small class="text-muted">@lang('canteen.orders.no_active_canteens')</small></div>@endif
        </form>
    </div></div>

    @if($selected)
        <form method="POST" action="{{ route('admin.canteen-orders.store') }}">
            @csrf
            <input type="hidden" name="canteen_id" value="{{ $selected->id }}">
            <div class="card mb-3"><div class="card-body">
                <div class="form-group mb-3">
                    <label class="form-label">@lang('canteen.orders.fields.student') <span class="text-danger">*</span></label>
                    <select name="student_id" class="custom-select" required>
                        <option value="">@lang('canteen.orders.choose_student')</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}" @selected((string)old('student_id')===(string)$s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">@lang('canteen.orders.fields.note')</label>
                    <input type="text" name="note" value="{{ old('note') }}" class="form-control" maxlength="500">
                </div>
            </div></div>

            <div class="card"><div class="card-header"><h5 class="mb-0"><i class="la la-box"></i> @lang('canteen.orders.products')</h5></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>@lang('canteen.products.cols.name')</th><th>@lang('canteen.products.cols.category')</th><th>@lang('canteen.products.cols.price')</th><th style="width:140px">@lang('canteen.orders.fields.qty')</th></tr></thead>
                    <tbody>
                        @forelse($products as $p)
                            <tr>
                                <td>{{ $p->name }}</td>
                                <td>{{ optional($p->category)->name ?? '—' }}</td>
                                <td>{{ number_format((float) $p->price, 2) }}</td>
                                <td><input type="number" name="items[{{ $p->id }}]" value="0" min="0" max="1000" class="form-control form-control-sm"></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">@lang('canteen.orders.no_products')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($products->isNotEmpty())
            <div class="card-body d-flex" style="gap:.5rem;">
                <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('canteen.orders.save')</button>
                <a href="{{ route('admin.canteen-orders.index') }}" class="btn btn-outline-secondary">@lang('canteen.actions.cancel')</a>
            </div>
            @endif
            </div>
        </form>
    @endif
</div>
@endsection
