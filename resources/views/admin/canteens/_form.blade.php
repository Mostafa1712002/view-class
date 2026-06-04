@csrf
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0 pr-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="row">
    <div class="form-group mb-3 col-md-6">
        <label class="form-label">@lang('canteen.fields.name_ar') <span class="text-danger">*</span></label>
        <input type="text" name="name_ar" value="{{ old('name_ar', $canteen->name_ar) }}" class="form-control" required maxlength="255">
    </div>
    <div class="form-group mb-3 col-md-6">
        <label class="form-label">@lang('canteen.fields.name_en')</label>
        <input type="text" name="name_en" value="{{ old('name_en', $canteen->name_en) }}" class="form-control" maxlength="255">
    </div>
</div>

<div class="form-group mb-3">
    <label class="form-label">@lang('canteen.fields.school') <span class="text-danger">*</span></label>
    @php $sid = old('school_id', $canteen->school_id); @endphp
    <select name="school_id" class="custom-select" required>
        <option value="">@lang('canteen.choose_school')</option>
        @foreach($schools as $s)
            <option value="{{ $s->id }}" @selected((string)$sid===(string)$s->id)>{{ $s->name }}</option>
        @endforeach
    </select>
</div>

<div class="form-group mb-3">
    <label class="form-label">@lang('canteen.fields.target_grades')</label>
    @php $selectedGrades = (array) old('target_grades', $canteen->target_grades ?? []); @endphp
    <div class="d-flex flex-wrap" style="gap:.75rem;">
        @foreach($grades as $val => $label)
            <div class="form-check">
                <input type="checkbox" name="target_grades[]" value="{{ $val }}" id="grade-{{ $val }}" class="form-check-input" @checked(in_array((string)$val, array_map('strval',$selectedGrades), true))>
                <label class="form-check-label" for="grade-{{ $val }}">{{ $label }}</label>
            </div>
        @endforeach
    </div>
    <small class="text-muted d-block mt-1">@lang('canteen.fields.target_grades_hint')</small>
</div>

<div class="d-flex" style="gap:.5rem;">
    <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('canteen.actions.save')</button>
    <a href="{{ route('admin.canteens.index') }}" class="btn btn-outline-secondary">@lang('canteen.actions.cancel')</a>
</div>
