@extends('layouts.admin')

@section('title', 'درجاتي')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">درجاتي</h1>
    </div>

    {{-- Year Selection --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">العام الدراسي</label>
                    <select name="academic_year_id" class="form-select" onchange="this.form.submit()">
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ $selectedYear?->id == $year->id ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if($subjects->count() > 0)
        <div class="row">
            @foreach($subjects as $subjectData)
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">{{ $subjectData['subject']->name }}</h5>
                            <span class="badge bg-{{ $subjectData['average'] >= 50 ? 'success' : 'danger' }} fs-6">
                                المعدل: {{ number_format($subjectData['average'], 1) }}%
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>الفترة</th>
                                            <th class="text-center">الأعمال</th>
                                            <th class="text-center">الاختبار</th>
                                            <th class="text-center">المشاركة</th>
                                            <th class="text-center">المجموع</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($subjectData['grades'] as $grade)
                                            <tr>
                                                <td>{{ $grade->term }}</td>
                                                <td class="text-center">{{ $grade->homework_score ?? '-' }}</td>
                                                <td class="text-center">{{ $grade->exam_score ?? '-' }}</td>
                                                <td class="text-center">{{ $grade->participation_score ?? '-' }}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-{{ $grade->total >= 50 ? 'success' : 'danger' }}">
                                                        {{ number_format($grade->total, 1) }}%
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-award display-1 text-muted"></i>
                <p class="mt-3 text-muted">لا توجد درجات منشورة لهذا العام الدراسي</p>
            </div>
        </div>
    @endif
</div>
@endsection
