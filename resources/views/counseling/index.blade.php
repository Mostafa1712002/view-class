@extends('layouts.app')
@section('title', __('counseling.title'))
@section('page-title', __('counseling.title'))
@section('content')
<div class="card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<a class="btn btn-primary mb-3" href="{{ route('manage.counseling.create') }}">{{ __('counseling.new') }}</a>
@foreach(['status' => $statusCounts, 'category' => $categoryCounts] as $field => $counts)
<h2 class="h5">{{ __('counseling.'.$field.'_report') }}</h2>
<div class="d-flex flex-wrap mb-3">
@foreach($field === 'status' ? \App\Modules\Counseling\Models\CounselingCase::STATUSES : \App\Modules\Counseling\Models\CounselingCase::CATEGORIES as $value)
<span class="badge badge-light m-1">{{ __('counseling.'.($field === 'status' ? 'statuses' : 'categories').'.'.$value) }}: {{ $counts[$value] ?? 0 }}</span>
@endforeach
</div>
@endforeach
<form method="GET" class="mb-3">
@foreach(['status' => 'statuses', 'category' => 'categories'] as $field => $group)
<label for="{{ $field }}">{{ __('counseling.'.$field) }}</label>
<select class="form-control" id="{{ $field }}" name="{{ $field }}"><option value="">{{ __('counseling.all') }}</option>
@foreach(__('counseling.'.$group) as $value => $label)<option value="{{ $value }}" @selected(request($field) === $value)>{{ $label }}</option>@endforeach
</select>
@endforeach
<label for="student">{{ __('counseling.student') }}</label><input class="form-control" id="student" name="student" value="{{ request('student') }}">
<button class="btn btn-primary mt-2">{{ __('counseling.filter') }}</button>
</form>
<div class="table-responsive"><table class="table"><thead><tr>
@foreach(['case_title','student','counselor','category','status'] as $field)<th scope="col">{{ __('counseling.'.$field) }}</th>@endforeach
</tr></thead><tbody>
@forelse($cases as $case)<tr>
<td><a href="{{ route('manage.counseling.show', $case->id) }}">{{ $case->title }}</a></td>
<td>{{ $case->student?->name }}</td><td>{{ $case->counselor?->name }}</td><td>{{ __('counseling.categories.'.$case->category) }}</td><td>{{ __('counseling.statuses.'.$case->status) }}</td>
</tr>@empty<tr><td colspan="5">{{ __('counseling.empty') }}</td></tr>@endforelse
</tbody></table></div>{{ $cases->links() }}
</div></div>
@endsection
