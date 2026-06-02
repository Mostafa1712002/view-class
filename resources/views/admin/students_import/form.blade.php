@extends('layouts.app')
@section('title', __('student_import.page_title'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('student_import.page_title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.students.index') }}">@lang('users.students')</a></li>
            <li class="breadcrumb-item active">@lang('student_import.breadcrumb')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-4 col-12 text-md-left">
        <a href="#operations-archive" class="btn btn-warning btn-sm">
            <i class="la la-history"></i> @lang('student_import.archive')
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

    {{-- Blue header banner --}}
    <div class="card" style="background:linear-gradient(135deg,#1d4ed8,#2563eb);color:#fff;">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="mb-0"><i class="la la-file-excel"></i> @lang('student_import.upload_card_title')</h4>
            <a href="{{ route('admin.users.students.import.template') }}" class="btn btn-light btn-sm">
                <i class="la la-download"></i> @lang('student_import.download_template')
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Upload form --}}
        <div class="col-lg-7 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted">@lang('student_import.upload_help')</p>
                    <form method="POST" action="{{ route('admin.users.students.import.preview') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group mb-3">
                            <label class="form-label" for="school_id">@lang('student_import.school_label') <span class="text-danger">*</span></label>
                            <select name="school_id" id="school_id" class="form-control" @if($lockedSchoolId) disabled @endif required>
                                @unless($lockedSchoolId)
                                    <option value="">@lang('student_import.school_choose')</option>
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
                            <label class="form-label" for="file">@lang('student_import.file_label') <span class="text-danger">*</span></label>
                            <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.xls,.csv,.txt" required>
                            <small class="text-muted d-block mt-1">@lang('student_import.file_hint')</small>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="la la-eye"></i> @lang('student_import.read_file')
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Steps + grade copy buttons --}}
        <div class="col-lg-5 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">@lang('student_import.steps_title')</h5>
                </div>
                <div class="card-body">
                    <ol class="pr-3 mb-3" style="line-height:1.9">
                        <li><a href="{{ route('admin.users.students.import.template') }}">@lang('student_import.step_1')</a></li>
                        <li>@lang('student_import.step_2')</li>
                        <li>@lang('student_import.step_3')</li>
                        <li>@lang('student_import.step_4')</li>
                    </ol>

                    <h6 class="mb-2">@lang('student_import.grades_title')</h6>
                    @if (count($grades))
                        <div class="d-flex flex-wrap" style="gap:.4rem;">
                            @foreach ($grades as $grade)
                                <button type="button" class="btn btn-sm btn-outline-primary grade-copy" data-grade="{{ $grade }}">
                                    {{ $grade }}
                                </button>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">@lang('student_import.grades_empty')</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Columns reference table --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">@lang('student_import.columns_title')</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 text-center" style="white-space:nowrap;">
                    <thead style="background:#1d4ed8;color:#fff;">
                        <tr>
                            @foreach ($columns as $col)
                                <th style="font-weight:600;">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @foreach ($columns as $col)
                                <td>&nbsp;</td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Operations archive --}}
    <div class="card" id="operations-archive">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="la la-history"></i> @lang('student_import.history.title')</h5>
        </div>
        <div class="card-body p-0">
            @if (count($history))
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>@lang('student_import.history.file')</th>
                                <th>@lang('student_import.history.status')</th>
                                <th>@lang('student_import.history.total')</th>
                                <th>@lang('student_import.history.created')</th>
                                <th>@lang('student_import.history.failed')</th>
                                <th>@lang('student_import.history.date')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $h)
                                <tr>
                                    <td>{{ $h->id }}</td>
                                    <td>{{ $h->original_name }}</td>
                                    <td>
                                        @php $st = $h->status; @endphp
                                        <span class="badge
                                            @if($st==='completed') badge-success
                                            @elseif($st==='failed') badge-danger
                                            @else badge-info @endif">
                                            {{ __('student_import.history.status_' . $st) }}
                                        </span>
                                    </td>
                                    <td>{{ $h->total_rows }}</td>
                                    <td class="text-success">{{ $h->created_count }}</td>
                                    <td class="text-danger">{{ $h->failed_count }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($h->created_at)->format('Y/m/d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted text-center p-3 mb-0">@lang('student_import.history.empty')</p>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.grade-copy').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var text = btn.getAttribute('data-grade');
            var done = function () {
                var original = btn.textContent;
                btn.textContent = @json(__('student_import.copied'));
                btn.classList.add('btn-success');
                btn.classList.remove('btn-outline-primary');
                setTimeout(function () {
                    btn.textContent = original;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-primary');
                }, 1200);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done).catch(done);
            } else {
                var ta = document.createElement('textarea');
                ta.value = text; document.body.appendChild(ta); ta.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(ta); done();
            }
        });
    });
</script>
@endpush
@endsection
