@extends('layouts.app')
@section('title', __('users.students_link').' - '.$parent->name)
@section('content')
<div class="content-header"><h2 class="mb-2">@lang('users.students_link') — {{ $parent->name }}</h2></div>
<div class="card"><div class="card-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <form action="{{ route('admin.users.parents.students.sync', $parent->id) }}" method="POST">
        @csrf
        @php $linkedIds = $linked->pluck('id')->all(); @endphp
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>✓</th>
                    <th>@lang('users.name')</th>
                    <th>@lang('users.national_id')</th>
                    <th>@lang('users.class')</th>
                </tr>
            </thead>
            <tbody>
                @foreach($available as $s)
                <tr>
                    <td><input type="checkbox" name="student_ids[]" value="{{ $s->id }}" @checked(in_array($s->id, $linkedIds))></td>
                    <td>{{ $s->name }}</td>
                    <td>{{ $s->national_id ?? '—' }}</td>
                    <td>{{ optional($s->classRoom)->name ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <button class="btn btn-primary"><i class="la la-save"></i> @lang('users.save')</button>
        <a href="{{ route('admin.users.parents.index') }}" class="btn btn-outline-secondary">@lang('users.cancel')</a>
    </form>
</div></div>
@endsection
