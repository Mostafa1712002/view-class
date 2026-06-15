@extends('layouts.app')

@section('title', 'إضافة سؤال داخل القطعة')
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">إضافة سؤال داخل القطعة #{{ $passage->id }}</h2>
    <div class="breadcrumb-wrapper"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.qb.passages.index') }}">أسئلة القطعة</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.qb.passages.show', $passage->id) }}">القطعة #{{ $passage->id }}</a></li>
        <li class="breadcrumb-item active">إضافة سؤال</li>
    </ol></div>
</div></div>

<div class="content-body">
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="alert alert-info py-2">
        هذا السؤال سيُربط بالقطعة. نص القطعة يظهر للطالب قبل السؤال.
    </div>

    <form method="POST" action="{{ route('admin.qb.passages.questions.store', $passage->id) }}" enctype="multipart/form-data">
        @csrf
        @include('admin.qb.questions._form', ['backUrl' => route('admin.qb.passages.show', $passage->id)])
    </form>
</div>
@endsection
