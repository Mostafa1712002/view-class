@extends('layouts.app')
@section('title', 'إضافة مهارة')
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">إضافة مهارة</h2>
    <div class="breadcrumb-wrapper"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.qb.skills.index') }}">المهارات</a></li>
        <li class="breadcrumb-item active">إضافة</li>
    </ol></div>
</div></div>
<div class="content-body">
    <form method="POST" action="{{ route('admin.qb.skills.store') }}">
        @csrf
        @include('admin.qb.taxonomy.skills._form')
    </form>
</div>
@endsection
