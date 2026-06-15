@php $isEdit = isset($skill) && $skill->exists; @endphp
<div class="card"><div class="card-body">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">اسم المهارة <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $skill->name ?? '') }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">المادة</label>
            <select name="subject_id" class="form-select">
                <option value="">— لا شيء —</option>
                @foreach($subjects as $s)<option value="{{ $s->id }}" @selected(old('subject_id', $skill->subject_id ?? null) == $s->id)>{{ $s->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">الفصل الدراسي</label>
            <select name="semester_id" class="form-select">
                <option value="">— لا شيء —</option>
                @foreach($semesters as $t)<option value="{{ $t->id }}" @selected(old('semester_id', $skill->semester_id ?? null) == $t->id)>{{ $t->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">نوع المهارة <span class="text-danger">*</span></label>
            <select name="skill_type" class="form-select" required>
                @foreach($types as $k => $label)<option value="{{ $k }}" @selected(old('skill_type', $skill->skill_type ?? 'normal') === $k)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-center pt-3">
            <label class="d-flex align-items-center gap-2 mb-0">
                <input type="hidden" name="is_ability" value="0">
                <input type="checkbox" name="is_ability" value="1" @checked(old('is_ability', $skill->is_ability ?? false))> مهارة قدرات
            </label>
        </div>
        <div class="col-md-3 d-flex align-items-center pt-3">
            <label class="d-flex align-items-center gap-2 mb-0">
                <input type="hidden" name="is_tahsili" value="0">
                <input type="checkbox" name="is_tahsili" value="1" @checked(old('is_tahsili', $skill->is_tahsili ?? false))> مهارة تحصيلي
            </label>
        </div>
        <div class="col-md-2">
            <label class="form-label">الحالة</label>
            <select name="status" class="form-select">
                <option value="active" @selected(old('status', $skill->status ?? 'active') === 'active')>نشط</option>
                <option value="inactive" @selected(old('status', $skill->status ?? '') === 'inactive')>غير نشط</option>
            </select>
        </div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-warning"><x-svg-icon name="check2-circle" :size="15" /> {{ $isEdit ? 'حفظ التعديلات' : 'إضافة المهارة' }}</button>
        <a href="{{ route('admin.qb.skills.index') }}" class="btn btn-outline-secondary">رجوع</a>
    </div>
</div></div>
