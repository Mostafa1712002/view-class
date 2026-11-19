@extends('layouts.app')
@section('title', __('special_programs/bank.title'))
@section('page-title', __('special_programs.programs.'.$program))
@section('content')
@include('special-programs.partials.program-tabs', ['program' => $program, 'active' => 'bank'])
@include('special-programs.staff.bank._subjects', ['route' => 'manage.special-programs.bank.index'])
@if(session('success'))<div class="alert alert-success" role="alert">{{ session('success') }}</div>@endif
<div class="ds-card"><div class="card-body">
    <div class="d-flex flex-wrap mb-3">
        <a class="btn btn-primary btn-sm ml-2" href="{{ route('manage.special-programs.bank.create', [$program, 'subject' => $subject->id]) }}"><x-svg-icon name="plus-lg" :size="14" /> {{ __('special_programs/bank.add_question') }}</a>
        @if($program !== 'tahsili')
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('manage.special-programs.bank.passages', [$program, 'subject' => $subject->id]) }}">{{ __('special_programs/bank.passages') }}</a>
        @endif
    </div>
    <form method="GET" class="form-row align-items-end mb-3">
        <input type="hidden" name="subject" value="{{ $subject->id }}">
        <div class="col-md-3 col-6 mb-2"><label for="q">{{ __('special_programs/bank.search') }}</label><input class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}"></div>
        @if($program !== 'tahsili')
        <div class="col-md-3 col-6 mb-2"><label for="skill_id">{{ __('special_programs/bank.skill') }}</label>
            <select class="form-control" id="skill_id" name="skill_id"><option value="">{{ __('special_programs/bank.all') }}</option>
            @foreach($skills as $k)<option value="{{ $k->id }}" @selected(($filters['skill_id'] ?? '') == $k->id)>{{ $k->name }}@if($k->term) ({{ __('special_programs.terms.'.$k->term) }})@endif</option>@endforeach</select></div>
        @endif
        <div class="col-md-2 col-6 mb-2"><label for="term">{{ __('special_programs/bank.term') }}</label>
            <select class="form-control" id="term" name="term"><option value="">{{ __('special_programs/bank.all') }}</option>
            @foreach(__('special_programs.terms') as $v => $l)<option value="{{ $v }}" @selected(($filters['term'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-md-2 col-6 mb-2"><label for="difficulty">{{ __('special_programs/bank.difficulty') }}</label>
            <select class="form-control" id="difficulty" name="difficulty"><option value="">{{ __('special_programs/bank.all') }}</option>
            @foreach(__('special_programs.difficulty') as $v => $l)<option value="{{ $v }}" @selected(($filters['difficulty'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-md-2 col-6 mb-2"><label for="grade_level">{{ __('special_programs/bank.grade_level') }}</label>
            <select class="form-control" id="grade_level" name="grade_level"><option value="">{{ __('special_programs/bank.all') }}</option>
            @foreach(range(1, 12) as $g)<option value="{{ $g }}" @selected(($filters['grade_level'] ?? '') == $g)>{{ $g }}</option>@endforeach</select></div>
        @if($program === 'qiyas')
        <div class="col-md-2 col-6 mb-2"><label for="week">{{ __('special_programs/bank.week') }}</label><input type="number" min="1" max="19" class="form-control" id="week" name="week" value="{{ $filters['week'] ?? '' }}"></div>
        @endif
        @if($program === 'tahsili')
        <div class="col-md-2 col-6 mb-2"><label for="order_from">{{ __('special_programs/bank.order_from') }}</label><input type="number" min="1" class="form-control" id="order_from" name="order_from" value="{{ $filters['order_from'] ?? '' }}"></div>
        <div class="col-md-2 col-6 mb-2"><label for="order_to">{{ __('special_programs/bank.order_to') }}</label><input type="number" min="1" class="form-control" id="order_to" name="order_to" value="{{ $filters['order_to'] ?? '' }}"></div>
        @endif
        <div class="col-md-2 col-6 mb-2"><label for="status">{{ __('special_programs/bank.status') }}</label>
            <select class="form-control" id="status" name="status"><option value="">{{ __('special_programs/bank.all') }}</option>
            @foreach(['active', 'inactive'] as $v)<option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ __('special_programs/bank.statuses.'.$v) }}</option>@endforeach</select></div>
        <div class="col-auto mb-2"><button class="btn btn-outline-secondary btn-sm">{{ __('special_programs.buttons.search') }}</button></div>
    </form>
    @if($rows->isEmpty())
        <p class="text-muted mb-0">{{ __('special_programs/bank.empty') }}</p>
    @else
    <div class="table-responsive"><table class="table table-sm">
        <thead><tr>
            @if($program === 'tahsili')<th scope="col">#</th>@endif
            <th scope="col">{{ __('special_programs/bank.stem') }}</th>
            @if($program !== 'tahsili')<th scope="col">{{ __('special_programs/bank.skill') }}</th>@endif
            <th scope="col">{{ __('special_programs/bank.term') }}</th>
            @if($program === 'qiyas')<th scope="col">{{ __('special_programs/bank.week') }}</th>@endif
            <th scope="col">{{ __('special_programs/bank.difficulty') }}</th>
            <th scope="col">{{ __('special_programs/bank.status') }}</th><th scope="col"></th>
        </tr></thead>
        <tbody>
        @foreach($rows as $r)
            <tr>
                @if($program === 'tahsili')<td>{{ $r->question_order }}</td>@endif
                <td>@if($r->has_image)<x-svg-icon name="image" :size="14" /> @endif{{ \Illuminate\Support\Str::limit($r->body_ar, 90) ?: __('special_programs/bank.image_question') }}</td>
                @if($program !== 'tahsili')<td>{{ $r->skill_name ?? '—' }}</td>@endif
                <td>{{ $r->term ? __('special_programs.terms.'.$r->term) : '—' }}</td>
                @if($program === 'qiyas')<td>{{ $r->week ?? '—' }}</td>@endif
                <td>{{ __('special_programs.difficulty.'.$r->difficulty) }}</td>
                <td>{{ __('special_programs/bank.statuses.'.($r->is_active ? 'active' : 'inactive')) }}</td>
                <td class="text-nowrap">
                    <a class="btn btn-link btn-sm" href="{{ route('manage.special-programs.bank.show', [$program, $r->id]) }}">{{ __('special_programs.buttons.view') }}</a>
                    <a class="btn btn-link btn-sm" href="{{ route('manage.special-programs.bank.edit', [$program, $r->id]) }}">{{ __('special_programs.buttons.edit') }}</a>
                    <form method="POST" action="{{ route('manage.special-programs.bank.destroy', [$program, $r->id]) }}" class="d-inline" onsubmit="return confirm('{{ __('special_programs/bank.delete_confirm') }}')">
                        @csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">{{ __('special_programs.buttons.delete') }}</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    {{ $rows->links() }}
    @endif
</div></div>
@endsection
