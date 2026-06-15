@php $isEdit = isset($compound) && $compound->exists; @endphp
<div class="card"><div class="card-body">
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">اسم المجمع (عربي) <span class="text-danger">*</span></label>
            <input type="text" name="name_ar" class="form-control" value="{{ old('name_ar', $compound->name_ar ?? '') }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">اسم المجمع (إنجليزي)</label>
            <input type="text" name="name_en" class="form-control" value="{{ old('name_en', $compound->name_en ?? '') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">الترتيب</label>
            <input type="number" name="sort_order" min="0" class="form-control" value="{{ old('sort_order', $compound->sort_order ?? 0) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">الحالة</label>
            <select name="status" class="form-select">
                <option value="active" @selected(old('status', $compound->status ?? 'active') === 'active')>نشط</option>
                <option value="inactive" @selected(old('status', $compound->status ?? '') === 'inactive')>غير نشط</option>
            </select>
        </div>
        <div class="col-md-12">
            <label class="form-label">المدارس التابعة</label>
            <div class="row g-2" style="max-height:280px;overflow:auto;border:1px solid #e2e8f0;border-radius:8px;padding:10px;">
                @forelse($schools as $s)
                    <div class="col-md-4">
                        <label class="d-flex align-items-center gap-2 mb-0" style="font-size:13px;">
                            <input type="checkbox" name="schools[]" value="{{ $s->id }}" @checked(in_array($s->id, old('schools', $selectedSchools ?? []) ?? []))>
                            {{ $s->name ?? $s->name_ar }}
                        </label>
                    </div>
                @empty
                    <div class="col-12 text-muted">لا توجد مدارس.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-warning"><x-svg-icon name="check2-circle" :size="15" /> {{ $isEdit ? 'حفظ التعديلات' : 'إضافة المجمع' }}</button>
        <a href="{{ route('admin.qb.compounds.index') }}" class="btn btn-outline-secondary">رجوع</a>
    </div>
</div></div>
