@csrf
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">@lang('sprint4.question_banks.form.name_ar') <span class="text-danger">*</span></label>
        <input type="text" name="name_ar" value="{{ old('name_ar', $bank->name_ar) }}" class="form-control @error('name_ar') is-invalid @enderror" required>
        @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">@lang('sprint4.question_banks.form.name_en')</label>
        <input type="text" name="name_en" value="{{ old('name_en', $bank->name_en) }}" class="form-control">
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">@lang('sprint4.question_banks.form.subjects')</label>
        @php $selectedSubjects = old('subject_ids', $bank->subjects?->pluck('id')->all() ?? []); @endphp
        <div class="d-flex flex-wrap gap-2">
            @forelse($subjects as $subject)
                <label class="me-2">
                    <input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}" {{ in_array($subject->id, $selectedSubjects) ? 'checked' : '' }}>
                    {{ $subject->name }}
                    @if($subject->name_en)<small class="text-muted">({{ $subject->name_en }})</small>@endif
                </label>
            @empty
                <small class="text-muted">@lang('common.no_results')</small>
            @endforelse
        </div>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">@lang('sprint4.question_banks.form.editors') / @lang('sprint4.question_banks.form.viewers')</label>
        @php
            $memberRoles = old('member_roles', []);
            if (empty($memberRoles) && $bank->exists) {
                foreach ($bank->members as $m) { $memberRoles[$m->id] = $m->pivot->role; }
            }
        @endphp
        @if($teachers->isEmpty())
            <p class="text-muted small">@lang('common.no_results')</p>
        @else
            <div class="row">
                @foreach($teachers as $teacher)
                    <div class="col-md-4 mb-1">
                        <div class="d-flex align-items-center">
                            <span class="me-2 small">{{ $teacher->name ?? $teacher->username }}</span>
                            <select name="member_roles[{{ $teacher->id }}]" class="form-select form-select-sm" style="max-width: 160px">
                                <option value="" {{ ($memberRoles[$teacher->id] ?? '') === '' ? 'selected' : '' }}>@lang('sprint4.question_banks.form.role_none')</option>
                                <option value="viewer" {{ ($memberRoles[$teacher->id] ?? '') === 'viewer' ? 'selected' : '' }}>@lang('sprint4.question_banks.form.role_viewer')</option>
                                <option value="editor" {{ ($memberRoles[$teacher->id] ?? '') === 'editor' ? 'selected' : '' }}>@lang('sprint4.question_banks.form.role_editor')</option>
                            </select>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
<div class="text-end">
    <a href="{{ route('admin.question-banks.index') }}" class="btn btn-outline-secondary">@lang('sprint4.question_banks.form.cancel')</a>
    <button type="submit" class="btn btn-primary">@lang('sprint4.question_banks.form.save')</button>
</div>
