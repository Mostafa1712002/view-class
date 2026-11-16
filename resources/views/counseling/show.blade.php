@extends('layouts.app')
@section('title', __('counseling.title'))
@section('page-title', __('counseling.title'))
@section('content')
<div class="card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<h2>{{ $case->title }}</h2>
<dl>
<dt>{{ __('counseling.student') }}</dt><dd>{{ $case->student?->name }}</dd>
<dt>{{ __('counseling.counselor') }}</dt><dd>{{ $case->counselor?->name }}</dd>
<dt>{{ __('counseling.category') }}</dt><dd>{{ __('counseling.categories.'.$case->category) }}</dd>
<dt>{{ __('counseling.status') }}</dt><dd>{{ __('counseling.statuses.'.$case->status) }}</dd>
<dt>{{ __('counseling.summary') }}</dt><dd style="white-space: pre-wrap">{{ $case->summary }}</dd>
@foreach(['opened_at', 'closed_at'] as $field)<dt>{{ __('counseling.'.$field) }}</dt><dd>{{ $case->$field?->format('Y-m-d H:i') ?? '—' }}</dd>@endforeach
</dl>
<a class="btn btn-primary" href="{{ route('manage.counseling.edit', $case->id) }}">{{ __('counseling.edit') }}</a>
<a href="{{ route('manage.counseling.index') }}">{{ __('counseling.back') }}</a>
<form method="POST" action="{{ route('manage.counseling.status', $case->id) }}" class="my-3">@csrf @method('PATCH')
<label for="status">{{ __('counseling.status') }}</label><select class="form-control" name="status" id="status">
@foreach(__('counseling.statuses') as $value => $label)<option value="{{ $value }}" @selected($case->status === $value)>{{ $label }}</option>@endforeach
</select><button class="btn btn-primary mt-2">{{ __('counseling.change_status') }}</button></form>
@can('delete', $case)<form method="POST" action="{{ route('manage.counseling.destroy', $case->id) }}">@csrf @method('DELETE')<button class="btn btn-danger">{{ __('counseling.delete') }}</button></form>@endcan
<h3 class="mt-4">{{ __('counseling.notes') }}</h3>
@foreach($case->notes->sortBy('id') as $note)<article class="border rounded p-3 my-2">
<p>{{ $note->author?->name }} — <time>{{ $note->created_at->format('Y-m-d H:i') }}</time></p>
<p style="white-space: pre-wrap">{{ $note->body }}</p>
@if($note->action_taken)<p>{{ __('counseling.action_taken') }}: {{ $note->action_taken }}</p>@endif
</article>@endforeach
<form method="POST" action="{{ route('manage.counseling.notes.store', $case->id) }}">@csrf
<label for="body">{{ __('counseling.body') }}</label><textarea class="form-control" id="body" name="body" required rows="4">{{ old('body') }}</textarea>
<label for="action_taken">{{ __('counseling.action_taken') }}</label><input class="form-control" id="action_taken" name="action_taken" maxlength="255" value="{{ old('action_taken') }}">
<button class="btn btn-primary mt-2">{{ __('counseling.add_note') }}</button></form>
</div></div>
@endsection
