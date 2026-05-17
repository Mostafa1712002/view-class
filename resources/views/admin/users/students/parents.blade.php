@extends('layouts.app')

@section('title', __('users.parents_link').' — '.$student->name)
@section('body_class','theme-light')

@include('admin.users.students._sub_styles')

@section('content')
@php $active = 'parents'; @endphp
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $student->name }} — @lang('users.parents_link')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.students.index') }}">@lang('users.students')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.students.show', $student->id) }}">{{ $student->name }}</a></li>
                <li class="breadcrumb-item active">@lang('users.parents_link')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('admin.users.students._header')
    @include('admin.users.students._subnav')

    <div class="card">
        <div class="card-body">
            @if($parents->isEmpty())
                <div class="empty-state">
                    <div class="icon-wrap"><i class="la la-user-friends"></i></div>
                    <h4>@lang('users.student_no_parents')</h4>
                    <p>@lang('users.parent_link_help')</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>@lang('users.name')</th>
                                <th>@lang('users.username')</th>
                                <th>@lang('users.phone')</th>
                                <th>@lang('users.student_relationship')</th>
                                <th>@lang('users.actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($parents as $p)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.users.parents.show', $p->id) }}" class="fw-bold text-decoration-none">{{ $p->name }}</a>
                                </td>
                                <td><small class="text-muted">{{ '@'.$p->username }}</small></td>
                                <td>{{ $p->phone ?? '—' }}</td>
                                <td>{{ $p->pivot->relationship ?? 'parent' }}{{ ($p->pivot->is_primary ?? 0) ? ' • Primary' : '' }}</td>
                                <td>
                                    <a class="btn btn-sm btn-soft" href="{{ route('admin.users.parents.show', $p->id) }}"><i class="la la-eye"></i></a>
                                    <a class="btn btn-sm btn-soft" href="{{ route('admin.users.parents.edit', $p->id) }}"><i class="la la-edit"></i></a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
