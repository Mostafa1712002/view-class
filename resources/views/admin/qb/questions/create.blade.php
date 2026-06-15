@extends('layouts.app')

@section('title', 'إضافة سؤال جديد')
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">إضافة سؤال جديد</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.qb.questions.index') }}">قائمة الأسئلة</a></li>
                <li class="breadcrumb-item active">إضافة سؤال</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('admin.qb.questions.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.qb.questions._form')
    </form>
</div>
@endsection
