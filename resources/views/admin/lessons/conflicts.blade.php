@extends('layouts.app')
@section('title', __('lessons_admin.conflicts_page.title'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('lessons_admin.conflicts_page.title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('lessons_admin.breadcrumb_home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.lessons.index') }}">@lang('lessons_admin.breadcrumb_index')</a></li>
            <li class="breadcrumb-item active">@lang('lessons_admin.conflicts_page.title')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-4 col-12 text-md-left">
        <a href="{{ route('admin.lessons.index') }}" class="btn btn-soft btn-sm"><i class="la la-arrow-right"></i> @lang('lessons_admin.breadcrumb_index')</a>
    </div>
</div>
<div class="content-body">
    @if(empty($conflicts))
        <div class="card"><div class="card-body text-center text-success py-5"><i class="la la-check-circle" style="font-size:2.5rem;"></i><p class="mb-0 mt-2">@lang('lessons_admin.conflicts_page.none')</p></div></div>
    @else
        @foreach($conflicts as $c)
            <div class="card mb-3">
                <div class="card-header">
                    <span class="badge {{ $c['type']==='teacher' ? 'badge-danger' : 'badge-warning' }}">
                        {{ $c['type']==='teacher' ? __('lessons_admin.conflicts_page.type_teacher') : __('lessons_admin.conflicts_page.type_class') }}
                    </span>
                    @php $first = $c['periods'][0]; @endphp
                    <span class="ms-2">@lang('lessons_admin.conflicts_page.day'): {{ $days[$first->day_of_week] ?? $first->day_of_week }} — @lang('lessons_admin.conflicts_page.period'): {{ $first->period_number }}</span>
                </div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm mb-0">
                    <thead><tr><th>@lang('lessons_admin.table.teacher')</th><th>@lang('lessons_admin.table.subject')</th><th>@lang('lessons_admin.table.section')</th><th>@lang('lessons_admin.table.class')</th></tr></thead>
                    <tbody>
                        @foreach($c['periods'] as $p)
                            <tr>
                                <td>{{ optional($p->teacher)->name ?? '—' }}</td>
                                <td>{{ optional($p->subject)->name ?? '—' }}</td>
                                <td>{{ optional(optional(optional($p->schedule)->classRoom)->section)->name ?? '—' }}</td>
                                <td>{{ optional(optional($p->schedule)->classRoom)->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div></div>
            </div>
        @endforeach
    @endif
</div>
@endsection
