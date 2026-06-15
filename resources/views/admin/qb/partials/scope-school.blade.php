@php
    $genderLabel = ['boys'=>'بنين','girls'=>'بنات','mixed'=>'مشترك'];
    $name = $school->name_ar ?: $school->name;
    $isSel = (string)($selected['school_id'] ?? '') === (string)$school->id;
@endphp
<div class="scope-school {{ $isSel ? 'selected' : '' }}"
     data-scope-school-card
     data-school-id="{{ $school->id }}"
     data-compound-id="{{ $compoundId ?? '' }}"
     data-school-type="{{ $genderLabel[$school->student_gender] ?? '' }}"
     data-gender="{{ $school->student_gender }}"
     data-name="{{ $name }}"
     style="cursor:pointer;">
    <div class="d-flex justify-content-between align-items-center">
        <strong>{{ $name }}</strong>
        <div class="d-flex gap-1">
            <span class="badge bg-light text-dark">{{ $genderLabel[$school->student_gender] ?? '—' }}</span>
            @if($school->stage)<span class="badge bg-light text-dark">{{ $school->stage }}</span>@endif
        </div>
    </div>
</div>
