@extends('layouts.app')
@section('title', __('noor.page_title'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('noor.page_title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">@lang('noor.breadcrumb')</li>
        </ol>
    </div>
</div>

<div class="content-body">
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        {{-- Right column: upload (in RTL the right visual is col-lg-7) --}}
        <div class="col-lg-7 mb-3">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="la la-file-excel" style="color:#1f9d55;"></i>
                        @lang('noor.upload_card_title')
                    </h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">@lang('noor.upload_help')</p>

                    <form method="POST" action="{{ route('admin.noor.submit') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group mb-3">
                            <label class="form-label" for="type">@lang('noor.import_type_label') <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-control" required>
                                <option value="">@lang('noor.import_type_choose')</option>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label" for="file">@lang('noor.file_label') <span class="text-danger">*</span></label>
                            <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.xls,.csv,.txt" required>
                            <small class="text-muted d-block mt-1">@lang('noor.file_hint')</small>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="la la-paper-plane"></i> @lang('noor.submit')
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Left column: instructions --}}
        <div class="col-lg-5 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">@lang('noor.instructions_title')</h5>
                </div>
                <div class="card-body">
                    <h6 class="mb-2">@lang('noor.instructions_students_title')</h6>
                    <ol class="ps-3 mb-3" style="line-height:1.9">
                        @foreach (__('noor.instructions_students') as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ol>

                    <h6 class="mb-2">@lang('noor.instructions_teachers_title')</h6>
                    <ol class="ps-3 mb-3" style="line-height:1.9">
                        @foreach (__('noor.instructions_teachers') as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ol>

                    <div class="alert alert-warning mb-0" style="font-size:0.9rem;">
                        <i class="la la-info-circle"></i> @lang('noor.instructions_note')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
