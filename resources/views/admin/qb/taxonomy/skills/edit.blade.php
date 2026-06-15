@extends('layouts.app')
@section('title', 'تعديل مهارة')
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">تعديل مهارة</h2>
    <div class="breadcrumb-wrapper"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.qb.skills.index') }}">المهارات</a></li>
        <li class="breadcrumb-item active">تعديل</li>
    </ol></div>
</div></div>
<div class="content-body">
    <form method="POST" action="{{ route('admin.qb.skills.update', $skill->id) }}">
        @csrf @method('PUT')
        @include('admin.qb.taxonomy.skills._form')
    </form>
</div>
@endsection
