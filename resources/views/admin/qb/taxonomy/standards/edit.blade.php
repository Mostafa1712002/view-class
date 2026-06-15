@extends('layouts.app')
@section('title', 'تعديل معيار')
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">تعديل معيار</h2>
    <div class="breadcrumb-wrapper"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.qb.standards.index') }}">المعايير</a></li>
        <li class="breadcrumb-item active">تعديل</li>
    </ol></div>
</div></div>
<div class="content-body">
    <form method="POST" action="{{ route('admin.qb.standards.update', $standard->id) }}">@csrf @method('PUT')
        @include('admin.qb.taxonomy.standards._form')
    </form>
</div>
@endsection
