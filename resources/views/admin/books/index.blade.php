@extends('layouts.app')

@section('body_class','theme-light')
@section('title', __('books_admin.page_title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-7 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('books_admin.page_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('books_admin.breadcrumb')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-5 col-12 text-md-right">
        <a href="{{ route('manage.books.create') }}" class="btn btn-primary btn-sm">
            <i class="la la-plus"></i> @lang('books_admin.add_book')
        </a>
        <a href="{{ route('manage.books.create', ['ministry' => 1]) }}" class="btn btn-outline-warning btn-sm">
            <i class="la la-university"></i> @lang('books_admin.add_ministry_book')
        </a>
        <a href="{{ route('manage.books.grades') }}" class="btn btn-outline-primary btn-sm">
            <i class="la la-list-alt"></i> @lang('books_admin.manage_grades')
        </a>
    </div>
</div>

<div class="content-body">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0"><i class="la la-filter"></i> @lang('books_admin.filters.title')</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('manage.books.index') }}">
                <div class="form-row">
                    <div class="col-md-3">
                        <label class="form-label small">@lang('books_admin.filters.title')</label>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">@lang('books_admin.filters.subject')</label>
                        <select name="subject_id" class="custom-select custom-select-sm">
                            <option value="">@lang('books_admin.all')</option>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}" @selected((string)($filters['subject_id'] ?? '')===(string)$s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">@lang('books_admin.filters.grade')</label>
                        <select name="grade_level" class="custom-select custom-select-sm">
                            <option value="">@lang('books_admin.all')</option>
                            @foreach($grades as $val => $label)
                                <option value="{{ $val }}" @selected((string)($filters['grade_level'] ?? '')===(string)$val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">@lang('books_admin.filters.term')</label>
                        <select name="academic_term_id" class="custom-select custom-select-sm">
                            <option value="">@lang('books_admin.all')</option>
                            @foreach($terms as $t)
                                <option value="{{ $t->id }}" @selected((string)($filters['academic_term_id'] ?? '')===(string)$t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">@lang('books_admin.filters.ministry')</label>
                        <select name="is_ministry" class="custom-select custom-select-sm">
                            <option value="">@lang('books_admin.all')</option>
                            <option value="1" @selected((string)($filters['is_ministry'] ?? '')==='1')>@lang('books_admin.yes')</option>
                            <option value="0" @selected((string)($filters['is_ministry'] ?? '')==='0')>@lang('books_admin.no')</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="la la-search"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>@lang('books_admin.columns.title')</th>
                        <th>@lang('books_admin.columns.subject')</th>
                        <th>@lang('books_admin.columns.grade')</th>
                        <th>@lang('books_admin.columns.term')</th>
                        <th>@lang('books_admin.columns.source')</th>
                        <th>@lang('books_admin.columns.status')</th>
                        <th>@lang('books_admin.columns.created_at')</th>
                        <th class="text-right">@lang('books_admin.columns.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($books as $book)
                        <tr>
                            <td>
                                {{ $book->title }}
                                @if($book->is_ministry)
                                    <span class="badge badge-warning ml-1">@lang('books_admin.ministry_yes')</span>
                                @endif
                            </td>
                            <td>{{ optional($book->subject)->name ?? '—' }}</td>
                            <td>{{ $book->grade_level ? __('books_admin.grade_label', ['n' => $book->grade_level]) : '—' }}</td>
                            <td>{{ optional($book->academicTerm)->name ?? '—' }}</td>
                            <td>
                                @if($book->source === \App\Models\Book::SOURCE_FILE)
                                    <i class="la la-file-pdf text-danger"></i> @lang('books_admin.source_file')
                                @else
                                    <i class="la la-link"></i> @lang('books_admin.source_external')
                                @endif
                            </td>
                            <td>
                                @if($book->is_active)
                                    <span class="badge badge-success">@lang('books_admin.status_active')</span>
                                @else
                                    <span class="badge badge-secondary">@lang('books_admin.status_inactive')</span>
                                @endif
                            </td>
                            <td><small>{{ optional($book->created_at)->format('Y-m-d') }}</small></td>
                            <td class="text-right">
                                @if($book->read_url)
                                    <a href="{{ $book->read_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-info" title="@lang('books_admin.view')">
                                        <i class="la la-eye"></i>
                                    </a>
                                @endif
                                <a href="{{ route('manage.books.edit', $book->id) }}" class="btn btn-sm btn-outline-primary" title="@lang('books_admin.edit')">
                                    <i class="la la-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('manage.books.destroy', $book->id) }}" class="d-inline" onsubmit="return confirm('@lang('books_admin.confirm_delete')');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="@lang('books_admin.delete')">
                                        <i class="la la-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="la la-book-open la-3x d-block mb-2"></i>
                                @lang('books_admin.no_records')
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($books->hasPages())
            <div class="card-footer">{{ $books->links() }}</div>
        @endif
    </div>
</div>
@endsection
