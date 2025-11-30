@extends('layouts.admin')

@section('title', 'إدخال الدرجات')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">إدخال الدرجات</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.grades.class-report') }}" class="btn btn-outline-info">
                <i class="bi bi-file-earmark-bar-graph me-1"></i>
                تقرير الصف
            </a>
            <a href="{{ route('admin.grades.student-report') }}" class="btn btn-outline-info">
                <i class="bi bi-person-lines-fill me-1"></i>
                تقرير الطالب
            </a>
        </div>
    </div>

    {{-- Selection Form --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">اختر الصف والمادة</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
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
                        عرض
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Grades Entry --}}
    @if($grades->count() > 0)
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">درجات {{ $selectedSubject->name }} - {{ $selectedClass->name }}</h5>
                    <small class="text-muted">{{ $selectedYear->name }} - {{ \App\Models\Grade::SEMESTERS[$selectedSemester] }}</small>
                </div>
                <div class="d-flex gap-2">
                    @php
                        $isPublished = $grades->first()?->is_published ?? false;
                    @endphp
                    @if(!$isPublished)
                        <form action="{{ route('admin.grades.publish') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="class_id" value="{{ request('class_id') }}">
                            <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
                            <input type="hidden" name="academic_year_id" value="{{ request('academic_year_id') }}">
                            <input type="hidden" name="semester" value="{{ request('semester') }}">
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="bi bi-send me-1"></i>
                                نشر الدرجات
                            </button>
                        </form>
                    @else
                        <form action="{{ route('admin.grades.unpublish') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="class_id" value="{{ request('class_id') }}">
                            <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
                            <input type="hidden" name="academic_year_id" value="{{ request('academic_year_id') }}">
                            <input type="hidden" name="semester" value="{{ request('semester') }}">
                            <button type="submit" class="btn btn-secondary btn-sm">
                                <i class="bi bi-eye-slash me-1"></i>
                                إلغاء النشر
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.grades.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="class_id" value="{{ request('class_id') }}">
                    <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
                    <input type="hidden" name="academic_year_id" value="{{ request('academic_year_id') }}">
                    <input type="hidden" name="semester" value="{{ request('semester') }}">

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th rowspan="2" class="align-middle">#</th>
                                    <th rowspan="2" class="align-middle">اسم الطالب</th>
                                    <th colspan="5" class="text-center">الدرجات</th>
                                    <th rowspan="2" class="align-middle text-center">المجموع</th>
                                    <th rowspan="2" class="align-middle text-center">التقدير</th>
                                    <th rowspan="2" class="align-middle">ملاحظات</th>
                                </tr>
                                <tr>
                                    <th class="text-center" style="width: 80px;">
                                        الاختبارات<br>
                                        <small class="text-muted">15%</small>
                                    </th>
                                    <th class="text-center" style="width: 80px;">
                                        الواجبات<br>
                                        <small class="text-muted">10%</small>
                                    </th>
                                    <th class="text-center" style="width: 80px;">
                                        النصفي<br>
                                        <small class="text-muted">25%</small>
                                    </th>
                                    <th class="text-center" style="width: 80px;">
                                        النهائي<br>
                                        <small class="text-muted">40%</small>
                                    </th>
                                    <th class="text-center" style="width: 80px;">
                                        المشاركة<br>
                                        <small class="text-muted">10%</small>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grades as $index => $grade)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            {{ $grade->student->name }}
                                            <input type="hidden" name="grades[{{ $index }}][student_id]" value="{{ $grade->student->id }}">
                                        </td>
                                        <td>
                                            <input type="number" name="grades[{{ $index }}][quiz_avg]" class="form-control form-control-sm text-center grade-input" value="{{ $grade->quiz_avg }}" min="0" max="100" step="0.5" data-row="{{ $index }}">
                                        </td>
                                        <td>
                                            <input type="number" name="grades[{{ $index }}][homework_avg]" class="form-control form-control-sm text-center grade-input" value="{{ $grade->homework_avg }}" min="0" max="100" step="0.5" data-row="{{ $index }}">
                                        </td>
                                        <td>
                                            <input type="number" name="grades[{{ $index }}][midterm]" class="form-control form-control-sm text-center grade-input" value="{{ $grade->midterm }}" min="0" max="100" step="0.5" data-row="{{ $index }}">
                                        </td>
                                        <td>
                                            <input type="number" name="grades[{{ $index }}][final]" class="form-control form-control-sm text-center grade-input" value="{{ $grade->final }}" min="0" max="100" step="0.5" data-row="{{ $index }}">
                                        </td>
                                        <td>
                                            <input type="number" name="grades[{{ $index }}][participation]" class="form-control form-control-sm text-center grade-input" value="{{ $grade->participation }}" min="0" max="100" step="0.5" data-row="{{ $index }}">
                                        </td>
                                        <td class="text-center">
                                            <span class="total-display fw-bold" id="total-{{ $index }}">{{ $grade->total ? number_format($grade->total, 1) : '-' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="letter-grade-display" id="letter-{{ $index }}">{{ $grade->letter_grade ?? '-' }}</span>
                                        </td>
                                        <td>
                                            <input type="text" name="grades[{{ $index }}][comments]" class="form-control form-control-sm" value="{{ $grade->comments }}" placeholder="ملاحظة">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>
                            حفظ الدرجات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @elseif(request()->hasAny(['class_id', 'subject_id', 'academic_year_id', 'semester']))
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-people display-1 text-muted"></i>
                <p class="mt-3 text-muted">لا يوجد طلاب في هذا الصف</p>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.querySelectorAll('.grade-input').forEach(input => {
        input.addEventListener('input', function() {
            const row = this.dataset.row;
            calculateTotal(row);
        });
    });

    function calculateTotal(row) {
        const inputs = document.querySelectorAll(`input[data-row="${row}"]`);
        let quiz = parseFloat(inputs[0]?.value) || 0;
        let homework = parseFloat(inputs[1]?.value) || 0;
        let midterm = parseFloat(inputs[2]?.value) || 0;
        let final = parseFloat(inputs[3]?.value) || 0;
        let participation = parseFloat(inputs[4]?.value) || 0;

        const total = (quiz * 0.15) + (homework * 0.10) + (midterm * 0.25) + (final * 0.40) + (participation * 0.10);

        document.getElementById(`total-${row}`).textContent = total.toFixed(1);

        const letterGrade = getLetterGrade(total);
        document.getElementById(`letter-${row}`).textContent = letterGrade;
    }

    function getLetterGrade(percentage) {
        if (percentage >= 95) return 'A+';
        if (percentage >= 90) return 'A';
        if (percentage >= 85) return 'B+';
        if (percentage >= 80) return 'B';
        if (percentage >= 75) return 'C+';
        if (percentage >= 70) return 'C';
        if (percentage >= 65) return 'D+';
        if (percentage >= 60) return 'D';
        return 'F';
    }
</script>
@endpush
@endsection
