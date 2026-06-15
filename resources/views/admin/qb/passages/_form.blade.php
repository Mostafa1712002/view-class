@php $isEdit = isset($passage) && $passage->exists; @endphp

<div class="card mb-2"><div class="card-body">
    <div class="section-title" style="font-size:15px;font-weight:700;color:#0f172a;padding:12px 0 8px;border-bottom:1px solid #e2e8f0;margin-bottom:14px;">
        بيانات القطعة
    </div>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">بنك الأسئلة <span class="text-danger">*</span></label>
            <select name="question_bank_id" class="form-select" required @disabled($isEdit)>
                <option value="">— اختر البنك —</option>
                @foreach($banks as $b)
                    <option value="{{ $b->id }}" @selected(old('question_bank_id', $isEdit ? $passage->question_bank_id : null) == $b->id)>{{ $b->name_ar }}</option>
                @endforeach
            </select>
            @if($isEdit)<input type="hidden" name="question_bank_id" value="{{ $passage->question_bank_id }}">@endif
        </div>
        <div class="col-md-4">
            <label class="form-label">المادة</label>
            <select name="subject_id" class="form-select">
                <option value="">— لا شيء —</option>
                @foreach($subjects as $s)<option value="{{ $s->id }}" @selected(old('subject_id', $passage->subject_id ?? null) == $s->id)>{{ $s->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">المهارة</label>
            <select name="skill_id" class="form-select">
                <option value="">— لا شيء —</option>
                @foreach($skills as $sk)<option value="{{ $sk->id }}" @selected(old('skill_id', $passage->skill_id ?? null) == $sk->id)>{{ $sk->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">الفصل الدراسي</label>
            <select name="semester_id" class="form-select">
                <option value="">— لا شيء —</option>
                @foreach($semesters as $sem)<option value="{{ $sem->id }}" @selected(old('semester_id', $passage->semester_id ?? null) == $sem->id)>{{ $sem->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">مستوى الصعوبة</label>
            <select name="difficulty_level" class="form-select">
                @foreach($difficulties as $k => $label)<option value="{{ $k }}" @selected(old('difficulty_level', $passage->difficulty_level ?? 1) == $k)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">كود القطعة</label>
            <input type="text" name="passage_code" class="form-control" value="{{ old('passage_code', $passage->passage_code ?? '') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">حالة القطعة</label>
            <select name="status" class="form-select">
                @foreach($statuses as $k => $label)<option value="{{ $k }}" @selected(old('status', $passage->status ?? 'approved') === $k)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-12">
            <label class="form-label">نص القطعة <span class="text-danger">*</span></label>
            <textarea name="passage_text" class="form-control" rows="6" required>{{ old('passage_text', $passage->passage_text ?? '') }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">صورة القطعة (اختياري)</label>
            <input type="file" name="passage_image" accept="image/*" class="form-control">
            @if($isEdit && $passage->passage_image)
                <div class="mt-2 d-flex align-items-center gap-2">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($passage->passage_image) }}" style="max-width:120px;border-radius:8px;border:1px solid #e2e8f0;">
                    <label class="d-flex align-items-center gap-1 mb-0" style="font-size:13px;"><input type="checkbox" name="remove_image" value="1"> حذف الصورة</label>
                </div>
            @endif
        </div>
    </div>
</div></div>

<div class="d-flex gap-2 mb-4">
    <button type="submit" class="btn btn-warning">{{ $isEdit ? 'حفظ التعديلات' : 'إضافة القطعة' }}</button>
    <a href="{{ route('admin.qb.passages.index') }}" class="btn btn-outline-secondary">رجوع</a>
</div>
