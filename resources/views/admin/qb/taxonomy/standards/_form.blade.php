@php $isEdit = isset($standard) && $standard->exists; @endphp
<div class="card"><div class="card-body">
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">اسم المعيار <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $standard->name ?? '') }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">الكود</label>
            <input type="text" name="code" class="form-control" value="{{ old('code', $standard->code ?? '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">المادة</label>
            <select name="subject_id" class="form-select">
                <option value="">— لا شيء —</option>
                @foreach($subjects as $s)<option value="{{ $s->id }}" @selected(old('subject_id', $standard->subject_id ?? null) == $s->id)>{{ $s->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">الترتيب</label>
            <input type="number" name="sort_order" min="0" class="form-control" value="{{ old('sort_order', $standard->sort_order ?? 0) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">الحالة</label>
            <select name="status" class="form-select">
                <option value="active" @selected(old('status', $standard->status ?? 'active') === 'active')>نشط</option>
                <option value="inactive" @selected(old('status', $standard->status ?? '') === 'inactive')>غير نشط</option>
            </select>
        </div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-warning"><x-svg-icon name="check2-circle" :size="15" /> {{ $isEdit ? 'حفظ التعديلات' : 'إضافة المعيار' }}</button>
        <a href="{{ route('admin.qb.standards.index') }}" class="btn btn-outline-secondary">رجوع</a>
    </div>
</div></div>
