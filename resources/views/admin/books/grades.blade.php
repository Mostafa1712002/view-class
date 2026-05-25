@extends('layouts.app')

@section('body_class','theme-light')
@section('title', __('books_admin.grades.page_title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-7 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('books_admin.grades.page_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('manage.books.index') }}">@lang('books_admin.breadcrumb')</a></li>
                <li class="breadcrumb-item active">@lang('books_admin.grades.breadcrumb')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-5 col-12 text-md-end">
        <a href="{{ route('manage.books.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="la la-arrow-left"></i> @lang('books_admin.back')
        </a>
    </div>
</div>

<div class="content-body">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(!$school)
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="la la-school la-3x d-block mb-2"></i>
                @lang('books_admin.grades.no_school')
            </div>
        </div>
    @else
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <span class="text-muted">@lang('books_admin.grades.current_school'):</span>
                    <strong>{{ $school->name }}</strong>
                </div>
                <small class="text-muted">@lang('books_admin.grades.intro')</small>
            </div>
        </div>

        @if($stages->isEmpty())
            <div class="card">
                <div class="card-body text-center py-5 text-muted">
                    <i class="la la-layer-group la-3x d-block mb-2"></i>
                    @lang('books_admin.grades.no_stages')
                </div>
            </div>
        @elseif($availableBooks->isEmpty())
            <div class="card">
                <div class="card-body text-center py-5 text-muted">
                    <i class="la la-book-open la-3x d-block mb-2"></i>
                    @lang('books_admin.grades.no_books')
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('manage.books.grades.save') }}" id="grade-books-form">
                @csrf

                <div class="accordion" id="stagesAccordion">
                    @foreach($stages as $sIdx => $stage)
                        <div class="card mb-2">
                            <div class="card-header" id="stageHead{{ $stage->id }}">
                                <h5 class="mb-0">
                                    <button class="btn btn-link text-decoration-none p-0" type="button"
                                            data-toggle="collapse" data-target="#stageBody{{ $stage->id }}"
                                            aria-expanded="{{ $sIdx === 0 ? 'true' : 'false' }}"
                                            aria-controls="stageBody{{ $stage->id }}">
                                        <i class="la la-layer-group"></i>
                                        @lang('books_admin.grades.stage'): {{ $stage->name }}
                                        <span class="text-muted small">({{ $stage->level_label }})</span>
                                    </button>
                                </h5>
                            </div>
                            <div id="stageBody{{ $stage->id }}" class="collapse {{ $sIdx === 0 ? 'show' : '' }}"
                                 aria-labelledby="stageHead{{ $stage->id }}" data-parent="#stagesAccordion">
                                <div class="card-body">
                                    @forelse($stage->classes as $class)
                                        @php $linkedIds = $linked[$class->id] ?? []; @endphp
                                        <div class="border rounded p-3 mb-3 grade-block" data-grade="{{ $class->id }}">
                                            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                                                <h6 class="mb-0">
                                                    <i class="la la-chalkboard"></i>
                                                    @lang('books_admin.grades.grade'): {{ $class->name }}
                                                    @if($class->division)<span class="text-muted">/ {{ $class->division }}</span>@endif
                                                </h6>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary js-select-all">@lang('books_admin.grades.select_all')</button>
                                                    <button type="button" class="btn btn-outline-secondary js-clear-all">@lang('books_admin.grades.clear_all')</button>
                                                </div>
                                            </div>
                                            <div class="row">
                                                @foreach($availableBooks as $book)
                                                    <div class="col-md-4 col-sm-6 mb-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="grades[{{ $class->id }}][]"
                                                                   value="{{ $book->id }}"
                                                                   id="g{{ $class->id }}b{{ $book->id }}"
                                                                   @checked(in_array($book->id, $linkedIds))>
                                                            <label class="form-check-label" for="g{{ $class->id }}b{{ $book->id }}">
                                                                {{ $book->title }}
                                                                @if($book->is_ministry)
                                                                    <span class="badge bg-warning text-dark">@lang('books_admin.grades.ministry_badge')</span>
                                                                @endif
                                                                @if(optional($book->subject)->name)
                                                                    <small class="text-muted d-block">{{ $book->subject->name }}</small>
                                                                @endif
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-muted mb-0">@lang('books_admin.grades.no_grades_in_stage')</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="la la-save"></i> @lang('books_admin.grades.save')
                    </button>
                </div>
            </form>
        @endif
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.grade-block').forEach(function (block) {
        var setAll = function (checked) {
            block.querySelectorAll('input[type="checkbox"]').forEach(function (cb) { cb.checked = checked; });
        };
        var selBtn = block.querySelector('.js-select-all');
        var clrBtn = block.querySelector('.js-clear-all');
        if (selBtn) { selBtn.addEventListener('click', function () { setAll(true); }); }
        if (clrBtn) { clrBtn.addEventListener('click', function () { setAll(false); }); }
    });
});
</script>
@endpush
