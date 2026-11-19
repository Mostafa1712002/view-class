<div class="form-row">
<div class="col-md-3 col-6 mb-2"><label>{{ __('points_manage.period_name') }}</label><input class="form-control" name="name" maxlength="150" value="{{ $period?->name }}" required></div>
<div class="col-md-3 col-6 mb-2"><label>{{ __('points_manage.grade') }}</label><select class="form-control" name="section_id"><option value="">{{ __('points_manage.all_grades') }}</option>
@foreach($sections as $s)<option value="{{ $s->id }}" @selected($period?->section_id == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
<div class="col-md-2 col-6 mb-2"><label>{{ __('points_manage.starts_on') }}</label><input type="date" class="form-control" name="starts_on" value="{{ $period?->starts_on->format('Y-m-d') }}" required></div>
<div class="col-md-2 col-6 mb-2"><label>{{ __('points_manage.ends_on') }}</label><input type="date" class="form-control" name="ends_on" value="{{ $period?->ends_on->format('Y-m-d') }}" required></div>
<div class="col-md-2 col-6 mb-2"><label>{{ __('points_manage.min_points') }}</label><input type="number" min="1" class="form-control" name="min_points" value="{{ $period?->min_points }}" required></div>
</div>
