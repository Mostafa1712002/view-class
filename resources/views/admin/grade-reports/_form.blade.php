@php($report = $report ?? null)
@php($val = function ($field, $default = null) use ($report) {
    return old($field, $report?->{$field} instanceof \Illuminate\Support\Carbon
        ? $report->{$field}->format('Y-m-d')
        : ($report?->{$field} ?? $default));
})

<div class="card">
    <div class="card-header"><h4 class="card-title mb-0">{{ trans('grades_admin.edit_report') }}</h4></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label">{{ trans('grades_admin.report_title') }} <span class="text-danger">*</span></label>
                <input type="text" name="title"
                       class="form-control @error('title') is-invalid @enderror"
                       value="{{ $val('title') }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ trans('grades_admin.report_type') }}</label>
                <select name="type" class="form-control">
                    @foreach(['dynamic','static','gradesheet','transcript','notification'] as $t)
                        <option value="{{ $t }}" @selected(old('type', $report?->type ?? 'dynamic') === $t)>
                            {{ trans('grades_admin.type_'.$t) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">{{ trans('grades_admin.academic_year') }}</label>
                <select name="academic_year_id" class="form-control">
                    <option value="">{{ trans('grades_admin.pick') }}</option>
                    @foreach($years as $y)
                        <option value="{{ $y->id }}" @selected($val('academic_year_id') == $y->id)>{{ $y->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">{{ trans('grades_admin.academic_term') }}</label>
                <select name="academic_term_id" class="form-control">
                    <option value="">{{ trans('grades_admin.pick') }}</option>
                    @foreach($terms as $t)
                        <option value="{{ $t->id }}" @selected($val('academic_term_id') == $t->id)>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">{{ trans('grades_admin.class') }}</label>
                <select name="class_id" class="form-control">
                    <option value="">{{ trans('grades_admin.pick') }}</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" @selected($val('class_id') == $c->id)>{{ $c->name }} ({{ $c->grade_level }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">{{ trans('grades_admin.subject') }}</label>
                <select name="subject_id" class="form-control">
                    <option value="">{{ trans('grades_admin.pick') }}</option>
                    @foreach($subjects as $s)
                        <option value="{{ $s->id }}" @selected($val('subject_id') == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">{{ trans('grades_admin.grade_input_starts_at') }}</label>
                <input type="date" name="grade_input_starts_at" class="form-control" value="{{ $val('grade_input_starts_at') }}">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">{{ trans('grades_admin.grade_input_ends_at') }}</label>
                <input type="date" name="grade_input_ends_at" class="form-control" value="{{ $val('grade_input_ends_at') }}">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">{{ trans('grades_admin.opens_at') }}</label>
                <input type="date" name="opens_at" class="form-control" value="{{ $val('opens_at') }}">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">{{ trans('grades_admin.closes_at') }}</label>
                <input type="date" name="closes_at" class="form-control" value="{{ $val('closes_at') }}">
            </div>

            <div class="col-12">
                <hr>
                <strong class="d-block mb-2">{{ trans('grades_admin.visibility_section') }}:</strong>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="visible_to_student" value="1" id="visible_to_student" @checked(old('visible_to_student', $report?->visible_to_student ?? true))>
                            <label class="form-check-label" for="visible_to_student">{{ trans('grades_admin.visible_to_student') }}</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="visible_to_parent" value="1" id="visible_to_parent" @checked(old('visible_to_parent', $report?->visible_to_parent ?? true))>
                            <label class="form-check-label" for="visible_to_parent">{{ trans('grades_admin.visible_to_parent') }}</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="visible_to_teacher" value="1" id="visible_to_teacher" @checked(old('visible_to_teacher', $report?->visible_to_teacher ?? true))>
                            <label class="form-check-label" for="visible_to_teacher">{{ trans('grades_admin.visible_to_teacher') }}</label>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="include_behavior" value="1" id="include_behavior" @checked(old('include_behavior', $report?->include_behavior ?? false))>
                            <label class="form-check-label" for="include_behavior">{{ trans('grades_admin.include_behavior') }}</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="show_subject_bilingual" value="1" id="show_subject_bilingual" @checked(old('show_subject_bilingual', $report?->show_subject_bilingual ?? false))>
                            <label class="form-check-label" for="show_subject_bilingual">{{ trans('grades_admin.show_subject_bilingual') }}</label>
                        </div>
                    </div>
                    @if($report)
                    <div class="col-md-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $report->is_active))>
                            <label class="form-check-label" for="is_active">{{ trans('grades_admin.is_active_field') }}</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="is_locked" value="1" id="is_locked" @checked(old('is_locked', $report->is_locked))>
                            <label class="form-check-label" for="is_locked">{{ trans('grades_admin.is_locked_field') }}</label>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
