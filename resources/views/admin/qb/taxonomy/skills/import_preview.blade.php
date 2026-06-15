@extends('layouts.app')
@section('title', 'معاينة استيراد المهارات')
@section('body_class', 'theme-light')
@section('content')
@php
    $valid = collect($rows)->where('status','valid')->count();
    $invalid = count($rows) - $valid;
@endphp
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">معاينة استيراد المهارات</h2>
    <div class="breadcrumb-wrapper"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.qb.skills.import.index') }}">استيراد المهارات</a></li>
        <li class="breadcrumb-item active">معاينة</li>
    </ol></div>
</div></div>
<div class="content-body">
    <div class="card mb-3"><div class="card-body d-flex flex-wrap gap-3 align-items-center">
        <span class="badge bg-info">الإجمالي: {{ count($rows) }}</span>
        <span class="badge bg-success">صالح: {{ $valid }}</span>
        <span class="badge bg-danger">خاطئ: {{ $invalid }}</span>
        @if($valid > 0)
            <form method="POST" action="{{ route('admin.qb.skills.import.confirm', $batch->id) }}" class="ms-auto">
                @csrf
                <button class="btn btn-warning"><x-svg-icon name="check2-circle" :size="15" /> استيراد الصفوف الصالحة ({{ $valid }})</button>
            </form>
        @else
            <span class="text-danger ms-auto">لا توجد صفوف صالحة للاستيراد.</span>
        @endif
        <a href="{{ route('admin.qb.skills.import.index') }}" class="btn btn-outline-secondary">إلغاء</a>
    </div></div>

    <div class="card"><div class="card-body p-0">
        <div class="table-responsive"><table class="table mb-0" style="font-size:13px;">
            <thead><tr>
                <th>الصف</th><th>الحالة</th><th>المهارة</th><th>المادة</th><th>الفصل</th><th>النوع</th><th>الأخطاء</th>
            </tr></thead>
            <tbody>
                @foreach($rows as $r)
                    <tr class="{{ ($r['status'] ?? '') === 'invalid' ? 'table-danger' : '' }}">
                        <td>{{ $r['rowNumber'] }}</td>
                        <td>@if(($r['status'] ?? '')==='valid')<span class="badge bg-success">صالح</span>@else<span class="badge bg-danger">خاطئ</span>@endif</td>
                        <td>{{ $r['raw']['skill_name'] ?? '' }}</td>
                        <td>{{ $r['raw']['subject'] ?? '' }}</td>
                        <td>{{ $r['raw']['semester'] ?? '' }}</td>
                        <td>{{ $r['raw']['skill_type'] ?? '' }}</td>
                        <td class="text-danger">{{ implode(' / ', $r['errors'] ?? []) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
    </div></div>
</div>
@endsection
