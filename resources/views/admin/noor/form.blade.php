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
    <div class="content-header-right text-md-end col-md-4 col-12 d-md-block d-none">
        <a href="{{ route('admin.noor.template') }}" class="btn btn-sm btn-outline-success">
            <i class="la la-file-csv"></i> @lang('noor.template_download')
        </a>
        <a href="{{ route('admin.noor.settings') }}" class="btn btn-sm btn-outline-secondary mr-1">
            <i class="la la-cog"></i> @lang('noor.settings.page_title')
        </a>
    </div>
</div>

<div class="content-body">
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 pr-3">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        {{-- Upload form --}}
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

                    <form method="POST" action="{{ route('admin.noor.preview') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group mb-3">
                            <label class="form-label" for="school_id">@lang('noor.school_label') <span class="text-danger">*</span></label>
                            <select name="school_id" id="school_id" class="form-control" @if($lockedSchoolId) disabled @endif required>
                                @unless($lockedSchoolId)
                                    <option value="">@lang('noor.school_choose')</option>
                                @endunless
                                @foreach ($schools as $school)
                                    <option value="{{ $school->id }}" @selected(old('school_id', $lockedSchoolId) == $school->id)>{{ $school->name }}</option>
                                @endforeach
                            </select>
                            @if($lockedSchoolId)
                                <input type="hidden" name="school_id" value="{{ $lockedSchoolId }}">
                            @endif
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label" for="academic_year_id">@lang('noor.year_label')</label>
                            <select name="academic_year_id" id="academic_year_id" class="form-control">
                                <option value="">@lang('noor.year_choose')</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}" @selected(old('academic_year_id') == $year->id)>
                                        {{ $year->name }}@if($year->is_current) — @lang('noor.year_current')@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label" for="type">@lang('noor.import_type_label') <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-control" required>
                                <option value="">@lang('noor.import_type_choose')</option>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', request('type')) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label" for="file">@lang('noor.file_label') <span class="text-danger">*</span></label>
                            <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.xls,.csv,.txt" required>
                            <small class="text-muted d-block mt-1">@lang('noor.file_hint')</small>
                            <small class="d-block mt-1">
                                <a href="{{ route('admin.noor.template') }}" class="text-success">
                                    <i class="la la-download"></i> @lang('noor.template_download')
                                </a>
                                — @lang('noor.template_hint')
                            </small>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="la la-eye"></i> @lang('noor.read_file')
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Instructions --}}
        <div class="col-lg-5 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">@lang('noor.instructions_title')</h5>
                </div>
                <div class="card-body">
                    <h6 class="mb-2">@lang('noor.instructions_students_title')</h6>
                    <ol class="pr-3 mb-3" style="line-height:1.9">
                        @foreach (__('noor.instructions_students') as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ol>

                    <h6 class="mb-2">@lang('noor.instructions_teachers_title')</h6>
                    <ol class="pr-3 mb-3" style="line-height:1.9">
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

    {{-- Import history --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="la la-history"></i> @lang('noor.history_title')</h5>
        </div>
        <div class="card-body p-0">
            @if (count($history))
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>@lang('noor.history_file')</th>
                                <th>@lang('noor.history_type')</th>
                                <th>@lang('noor.history_status')</th>
                                <th>@lang('noor.result_total')</th>
                                <th>@lang('noor.result_created')</th>
                                <th>@lang('noor.result_updated')</th>
                                <th>@lang('noor.result_failed')</th>
                                <th>@lang('noor.history_date')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $h)
                                <tr>
                                    <td>{{ $h->id }}</td>
                                    <td>{{ $h->original_name }}</td>
                                    <td>{{ __('noor.types.' . $h->type) }}</td>
                                    <td>
                                        @php $st = $h->status; @endphp
                                        <span class="badge
                                            @if($st==='completed') badge-success
                                            @elseif($st==='failed') badge-danger
                                            @elseif($st==='previewed') badge-info
                                            @else badge-secondary @endif">
                                            {{ __('noor.status.' . $st) }}
                                        </span>
                                    </td>
                                    <td>{{ $h->total_rows }}</td>
                                    <td class="text-success">{{ $h->created_count }}</td>
                                    <td class="text-primary">{{ $h->updated_count }}</td>
                                    <td class="text-danger">{{ $h->failed_count }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($h->created_at)->format('Y/m/d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted text-center p-3 mb-0">@lang('noor.history_empty')</p>
            @endif
        </div>
    </div>
</div>
@endsection
