@php
    $isEdit = isset($branch);
    $val = fn($k, $d=null) => old($k, $isEdit ? ($branch->{$k} ?? $d) : $d);
@endphp
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name_ar" class="form-label">@lang('school_branches.name_ar') <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('name_ar') is-invalid @enderror" id="name_ar" name="name_ar" value="{{ $val('name_ar') }}" required>
        @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="name_en" class="form-label">@lang('school_branches.name_en') <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('name_en') is-invalid @enderror" id="name_en" name="name_en" value="{{ $val('name_en') }}" required>
        @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="sort_order" class="form-label">@lang('school_branches.sort_order')</label>
        <input type="number" min="0" class="form-control" id="sort_order" name="sort_order" value="{{ $val('sort_order') }}">
    </div>
    @if($isEdit)
    <div class="col-md-6 mb-3 d-flex align-items-end">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" @checked($val('is_active', true))>
            <label class="form-check-label" for="is_active">@lang('school_branches.is_active')</label>
        </div>
    </div>
    @endif
</div>
