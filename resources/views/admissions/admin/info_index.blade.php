@extends('layouts.app')
@section('title','معلومات التسجيل')
@section('body_class','theme-light')

@section('content')
<div class="content-header">
    <h2>معلومات التسجيل</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admissions.index') }}">القبول والتسجيل</a></li>
        <li class="breadcrumb-item active">معلومات التسجيل</li>
    </ol>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card"><div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>اسم القسم</th><th width="120">الترتيب</th><th width="120">الحالة</th><th width="120">الإجراء</th></tr></thead>
            <tbody>
            @foreach($sections as $section)
                <tr>
                    <td>{{ $section->title }}</td>
                    <td>{{ $section->sort_order }}</td>
                    <td>
                        @if($section->is_active)<span class="badge badge-success">إظهار</span>
                        @else<span class="badge badge-secondary">إخفاء</span>@endif
                    </td>
                    <td>
                        <a href="{{ route('admissions.info.edit', $section->id) }}" class="btn btn-sm btn-outline-primary">
                            <x-svg-icon name="pencil" :size="15" /> تعديل
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div></div>
</div>
@endsection
