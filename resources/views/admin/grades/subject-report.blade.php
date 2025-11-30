@extends('layouts.admin')

@section('title', 'تقرير المادة')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">تقرير المادة</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.grades.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil-square me-1"></i>
                إدخال الدرجات
            </a>
            <a href="{{ route('admin.grades.class-report') }}" class="btn btn-outline-info">
                <i class="bi bi-file-earmark-bar-graph me-1"></i>
                تقرير الصف
            </a>
        </div>
    </div>

    {{-- Selection Form --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">المادة <span class="text-danger">*</span></label>
                    <select name="subject_id" class="form-select" required>
                        <option value="">اختر المادة</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">الصف <span class="text-danger">*</span></label>
                    <select name="class_id" class="form-select" required>
                        <option value="">اختر الصف</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">العام الدراسي <span class="text-danger">*</span></label>
                    <select name="academic_year_id" class="form-select" required>
                        <option value="">اختر العام</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">الفصل <span class="text-danger">*</span></label>
                    <select name="semester" class="form-select" required>
                        <option value="">اختر الفصل</option>
                        @foreach(\App\Models\Grade::SEMESTERS as $key => $label)
                            <option value="{{ $key }}" {{ request('semester') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>
                        عرض التقرير
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Report --}}
    @if($report)
        <div class="row">
            {{-- Statistics --}}
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">إحصائيات المادة</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="text-muted">{{ $report['subject']->name }} - {{ $report['class']->name }}</h6>
                        <p class="text-muted mb-3">{{ $report['academic_year']->name }} - {{ \App\Models\Grade::SEMESTERS[$report['semester']] }}</p>

                        <table class="table table-sm">
                            <tr>
                                <td>عدد الطلاب:</td>
                                <td class="text-end fw-bold">{{ $report['statistics']['total_students'] }}</td>
                            </tr>
                            <tr>
                                <td>المتوسط:</td>
                                <td class="text-end fw-bold">{{ number_format($report['statistics']['average'], 1) }}</td>
                            </tr>
                            <tr>
                                <td>أعلى درجة:</td>
                                <td class="text-end fw-bold text-success">{{ number_format($report['statistics']['highest'], 1) }}</td>
                            </tr>
                            <tr>
                                <td>أقل درجة:</td>
                                <td class="text-end fw-bold text-danger">{{ number_format($report['statistics']['lowest'], 1) }}</td>
                            </tr>
                            <tr class="table-success">
                                <td>ناجح:</td>
                                <td class="text-end fw-bold">{{ $report['statistics']['passed'] }}</td>
                            </tr>
                            <tr class="table-danger">
                                <td>راسب:</td>
                                <td class="text-end fw-bold">{{ $report['statistics']['failed'] }}</td>
                            </tr>
                            <tr>
                                <td>نسبة النجاح:</td>
                                <td class="text-end fw-bold">{{ number_format($report['statistics']['pass_rate'], 1) }}%</td>
                            </tr>
                        </table>
                    </div>
                </div>

                {{-- Grade Distribution --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">توزيع التقديرات</h5>
                    </div>
                    <div class="card-body">
                        @foreach($report['distribution'] as $grade => $count)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-{{ $grade === 'F' ? 'danger' : 'primary' }}">{{ $grade }}</span>
                                <div class="progress flex-grow-1 mx-2" style="height: 20px;">
                                    <div class="progress-bar" style="width: {{ $report['statistics']['total_students'] > 0 ? ($count / $report['statistics']['total_students']) * 100 : 0 }}%">
                                        {{ $count }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Students List --}}
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">درجات الطلاب</h5>
                        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i>
                            طباعة
                        </button>
                    </div>
                    <div class="card-body">
                        @if($report['grades']->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>الترتيب</th>
                                            <th>اسم الطالب</th>
                                            <th class="text-center">الاختبارات</th>
                                            <th class="text-center">الواجبات</th>
                                            <th class="text-center">النصفي</th>
                                            <th class="text-center">النهائي</th>
                                            <th class="text-center">المشاركة</th>
                                            <th class="text-center">المجموع</th>
                                            <th class="text-center">التقدير</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($report['grades'] as $index => $grade)
                                            <tr>
                                                <td>
                                                    @if($index < 3)
                                                        <span class="badge bg-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'dark') }}">
                                                            {{ $index + 1 }}
                                                        </span>
                                                    @else
                                                        {{ $index + 1 }}
                                                    @endif
                                                </td>
                                                <td>{{ $grade->student->name }}</td>
                                                <td class="text-center">{{ $grade->quiz_avg ?? '-' }}</td>
                                                <td class="text-center">{{ $grade->homework_avg ?? '-' }}</td>
                                                <td class="text-center">{{ $grade->midterm ?? '-' }}</td>
                                                <td class="text-center">{{ $grade->final ?? '-' }}</td>
                                                <td class="text-center">{{ $grade->participation ?? '-' }}</td>
                                                <td class="text-center fw-bold {{ $grade->total < 60 ? 'text-danger' : 'text-success' }}">
                                                    {{ number_format($grade->total, 1) }}
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-{{ $grade->total >= 60 ? 'success' : 'danger' }}">
                                                        {{ $grade->letter_grade }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="bi bi-clipboard-x display-1 text-muted"></i>
                                <p class="mt-3 text-muted">لا توجد درجات منشورة</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
