@php
    $gradeNames = [1=>'الأول',2=>'الثاني',3=>'الثالث',4=>'الرابع',5=>'الخامس',6=>'السادس'];
    $genderLabel = ['boys'=>'بنين','girls'=>'بنات','mixed'=>'مشترك'];
@endphp

<div class="table-responsive">
<table class="table table-sm mb-0">
    <tbody>
        <tr>
            <th style="width:130px;">المدرسة</th>
            <td>{{ $school?->name_ar ?? $school?->name ?? '— (بنك عام)' }}</td>
        </tr>
        <tr>
            <th>المرحلة</th>
            <td>{{ $school?->stage ?? '—' }}</td>
        </tr>
        <tr>
            <th>نوع المدرسة</th>
            <td>{{ $genderLabel[$school?->student_gender] ?? '—' }}</td>
        </tr>
        <tr>
            <th>الصف</th>
            <td>{{ $question->grade_id ? ($gradeNames[$question->grade_id] ?? ('الصف '.$question->grade_id)) : '—' }}</td>
        </tr>
        <tr>
            <th>الفصل</th>
            <td>{{ $class?->name ?? '—' }}</td>
        </tr>
        <tr>
            <th>الفصل الدراسي</th>
            <td>{{ $semester?->name ?? '—' }}</td>
        </tr>
    </tbody>
</table>
</div>
