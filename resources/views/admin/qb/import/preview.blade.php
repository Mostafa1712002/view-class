@extends('layouts.app')

@section('title', 'معاينة الاستيراد')
@section('body_class', 'theme-light')

@push('styles')
<style>
    .pv-card{border:1px solid #e2e8f0;border-radius:12px;padding:16px;background:#fff}
    .pv-stat{border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;text-align:center}
    .pv-stat .n{font-size:22px;font-weight:700}
    .pv-table td,.pv-table th{font-size:12.5px;vertical-align:middle}
    .pv-row-valid{background:#f0fdf4}.pv-row-invalid{background:#fef2f2}.pv-row-duplicate{background:#fffbeb}
    .pv-badge{padding:2px 8px;border-radius:999px;font-size:11px}
    .pv-b-valid{background:#dcfce7;color:#166534}.pv-b-invalid{background:#fee2e2;color:#991b1b}.pv-b-duplicate{background:#fef3c7;color:#92400e}
    .pv-err{color:#991b1b;font-size:11.5px}
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">معاينة الاستيراد — {{ $bank->name_ar }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.qb.import.index') }}">استيراد من Excel</a></li>
                <li class="breadcrumb-item active">معاينة</li>
            </ol>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col"><div class="pv-stat"><div class="n">{{ count($rows) }}</div>إجمالي الصفوف</div></div>
    <div class="col"><div class="pv-stat"><div class="n text-success">{{ $counts['valid'] }}</div>صالحة</div></div>
    <div class="col"><div class="pv-stat"><div class="n text-danger">{{ $counts['invalid'] }}</div>خاطئة</div></div>
    <div class="col"><div class="pv-stat"><div class="n text-warning">{{ $counts['duplicate'] }}</div>مكررة</div></div>
</div>

<div class="pv-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">جدول المعاينة</h6>
        <div class="d-flex gap-2">
            @if ($counts['valid'] > 0)
                <form method="POST" action="{{ route('admin.qb.import.confirm', $batch->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm">
                        <x-svg-icon name="check-circle-fill" :size="15" /> تأكيد استيراد {{ $counts['valid'] }} سؤال
                    </button>
                </form>
            @else
                <span class="text-muted">لا توجد صفوف صالحة للاستيراد.</span>
            @endif
            <a href="{{ route('admin.qb.import.index') }}" class="btn btn-outline-secondary btn-sm">إلغاء</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table pv-table">
            <thead><tr>
                <th>الصف</th><th>الكود</th><th>المادة</th><th>الصف الدراسي</th><th>الفصل</th>
                <th>الأسبوع</th><th>المهارة</th><th>النوع</th><th>الصعوبة</th><th>الحالة</th><th>ملاحظات الخطأ</th>
            </tr></thead>
            <tbody>
            @foreach ($rows as $r)
                @php $st = $r['status'] ?? 'valid'; @endphp
                <tr class="pv-row-{{ $st }}">
                    <td>{{ $r['rowNumber'] }}</td>
                    <td>{{ $r['raw']['question_code'] ?? '' }}</td>
                    <td>{{ $r['raw']['subject'] ?? '' }}</td>
                    <td>{{ $r['raw']['grade'] ?? '' }}</td>
                    <td>{{ $r['raw']['class'] ?? '' }}</td>
                    <td>{{ $r['raw']['week'] ?? '' }}</td>
                    <td>{{ $r['raw']['skill'] ?? '' }}</td>
                    <td>{{ $r['raw']['question_type'] ?? '' }}</td>
                    <td>{{ $r['raw']['difficulty_level'] ?? '' }}</td>
                    <td><span class="pv-badge pv-b-{{ $st }}">{{ ['valid'=>'صالح','invalid'=>'خطأ','duplicate'=>'مكرر'][$st] ?? $st }}</span></td>
                    <td class="pv-err">{{ implode(' • ', $r['errors'] ?? []) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
