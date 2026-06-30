@php
    // Subject + category + audience targeting (#235). Mirrors the virtual-classes
    // form: a target_type select toggles the <x-audience-selector> grids below.
    $targetType        = old('target_type', $room?->target_type ?? 'all');
    $roomTargets       = $room ? $room->targets : collect();
    $selectedUsers     = old('user_target_ids', $room ? $roomTargets->where('kind', 'user')->pluck('target_id')->all() : []);
    $selectedRoles     = old('role_target_ids', $room ? $roomTargets->where('kind', 'role')->pluck('target_id')->all() : []);
    $selectedJobTitles = old('job_title_ids', $room ? $roomTargets->where('kind', 'job_title')->pluck('target_id')->all() : []);
    $selectedGrades    = old('grade_levels', $room ? ($room->grade_levels ?? []) : []);
    $selectedClasses   = old('class_ids', $room ? ($room->class_ids ?? []) : []);
@endphp

<div class="form-row">
    {{-- Subject --}}
    <div class="form-group col-md-6">
        <label for="subject_id">@lang('discussion.field_subject')</label>
        <select name="subject_id" id="subject_id" class="form-control @error('subject_id') is-invalid @enderror">
            <option value="">— @lang('discussion.select_subject') —</option>
            @foreach(($subjects ?? []) as $s)
                <option value="{{ $s->id }}" {{ (int) old('subject_id', $room?->subject_id) === $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
        @error('subject_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Category / prep --}}
    <div class="form-group col-md-6">
        <label for="category">@lang('discussion.field_category')</label>
        <input type="text" name="category" id="category" maxlength="100"
               class="form-control @error('category') is-invalid @enderror"
               value="{{ old('category', $room?->category) }}"
               placeholder="{{ __('discussion.placeholder_category') }}">
        @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

{{-- Target audience --}}
<div class="form-group">
    <label for="dsTargetType">@lang('discussion.field_audience') <span class="text-danger">*</span></label>
    <select name="target_type" id="dsTargetType" class="form-control @error('target_type') is-invalid @enderror">
        <option value="all"            {{ $targetType === 'all' ? 'selected' : '' }}>@lang('discussion.target_all')</option>
        <option value="students"       {{ $targetType === 'students' ? 'selected' : '' }}>@lang('discussion.target_students')</option>
        <option value="teachers"       {{ $targetType === 'teachers' ? 'selected' : '' }}>@lang('discussion.target_teachers')</option>
        <option value="parents"        {{ $targetType === 'parents' ? 'selected' : '' }}>@lang('discussion.target_parents')</option>
        <option value="admins"         {{ $targetType === 'admins' ? 'selected' : '' }}>@lang('discussion.target_admins')</option>
        <option value="job_titles"     {{ $targetType === 'job_titles' ? 'selected' : '' }}>@lang('discussion.target_job_titles')</option>
        <option value="specific_users" {{ $targetType === 'specific_users' ? 'selected' : '' }}>@lang('discussion.target_specific_users')</option>
        <option value="specific_roles" {{ $targetType === 'specific_roles' ? 'selected' : '' }}>@lang('discussion.target_specific_roles')</option>
    </select>
    @error('target_type') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    @error('user_target_ids') <div class="text-danger small">{{ $message }}</div> @enderror
    @error('role_target_ids') <div class="text-danger small">{{ $message }}</div> @enderror
    @error('job_title_ids') <div class="text-danger small">{{ $message }}</div> @enderror

    <div class="mt-2">
        <x-audience-selector
            :grids="['job_titles', 'users', 'roles', 'grades', 'classes']"
            :conditional="true"
            :job-titles="$jobTitles ?? []"
            :users="$users ?? []"
            :roles="$roles ?? []"
            :grade-levels="$gradeLevels ?? []"
            :classes="$classes ?? []"
            :selected-job-titles="$selectedJobTitles"
            :selected-users="$selectedUsers"
            :selected-roles="$selectedRoles"
            :selected-grades="$selectedGrades"
            :selected-classes="$selectedClasses"
        />
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {
    // Show only the audience grids that match the chosen target type. The grids
    // carry data-show (job_titles | specific_users | specific_roles | students).
    var tt = document.getElementById('dsTargetType');
    function syncTarget() {
        var v = tt.value;
        document.querySelectorAll('.ann-cond').forEach(function (el) {
            el.style.display = (el.dataset.show === v) ? '' : 'none';
        });
    }
    if (tt) { tt.addEventListener('change', syncTarget); syncTarget(); }
});
</script>
@endpush
