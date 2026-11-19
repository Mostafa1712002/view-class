@extends('layouts.app')
@php($t = 'special_programs/student.result_page.')
@section('title', __($t.'title'))
@section('page-title', __($t.'title').' — '.($attempt->examSubject->subject?->name_ar))
@section('content')
@php($pct = (float) $attempt->score_pct)
@php($wrong = $attempt->answered_count - $attempt->correct_count)
@php($spent = (int) $attempt->time_spent_seconds)
<div class="d-flex justify-content-between mb-2">
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('student.programs.exam', $attempt->exam_id) }}">{{ __($t.'to_exam') }}</a>
    <div>
        @if($hasNext)<a class="btn btn-primary btn-sm" href="{{ route('student.programs.exam', $attempt->exam_id) }}">{{ __($t.'next_subject') }}</a>@endif
        @if($multi)<a class="btn btn-success btn-sm" href="{{ route('student.programs.final', $attempt->exam_id) }}">{{ __($t.'final') }}</a>@endif
    </div>
</div>
<div class="card"><div class="card-body text-center">
    <h1 class="mb-0">{{ rtrim(rtrim(number_format($pct, 2), '0'), '.') }}%</h1>
    <span class="badge badge-{{ $level['color'] }}">{{ $level['name'] }}</span>
    <p class="mt-1 mb-0">{{ $pct >= 80 ? __($t.'msg_high') : ($pct >= 60 ? __($t.'msg_mid') : __($t.'msg_low')) }}</p>
    <div class="row mt-2">
        <div class="col-6 col-md-3"><strong>{{ $attempt->correct_count }}</strong><br><small>{{ __($t.'correct') }}</small></div>
        <div class="col-6 col-md-3"><strong>{{ $wrong }}</strong><br><small>{{ __($t.'wrong') }}</small></div>
        <div class="col-6 col-md-3"><strong>{{ $attempt->items_count - $attempt->answered_count }}</strong><br><small>{{ __($t.'unanswered') }}</small></div>
        <div class="col-6 col-md-3"><strong>{{ intdiv($spent, 60) }}:{{ str_pad($spent % 60, 2, '0', STR_PAD_LEFT) }}</strong><br><small>{{ __($t.'time') }}</small></div>
    </div>
    <small class="text-muted">{{ __($t.'status') }}: {{ __('special_programs.attempt_status.'.$attempt->status) }}</small>
</div></div>

@if($breakdown->isNotEmpty())
<div class="card"><div class="card-header"><h5 class="mb-0">{{ $attempt->program === 'tahsili' ? __($t.'by_difficulty') : __($t.'by_skill') }}</h5></div>
<div class="table-responsive"><table class="table mb-0">
    @foreach($breakdown as $b)
        <tr><td>{{ $b['label'] }}</td><td>{{ $b['correct'] }} / {{ $b['total'] }}</td><td>{{ $b['total'] ? round($b['correct'] * 100 / $b['total'], 1) : 0 }}%</td></tr>
    @endforeach
</table></div></div>
@endif

<h5 class="mt-2">{{ __($t.'review') }}</h5>
@php($shownPassages = [])
@foreach($items as $item)
    @if($item->passage_id && ! in_array($item->passage_id, $shownPassages) && isset($passages[$item->passage_id]))
        @php($shownPassages[] = $item->passage_id)
        <div class="card bg-light"><div class="card-body" style="line-height:2">{!! nl2br(e($passages[$item->passage_id]->passage_text)) !!}</div></div>
    @endif
    <div class="card"><div class="card-body">
        <div class="mb-1"><span class="badge badge-{{ $item->is_correct ? 'success' : 'danger' }}">{{ $item->order_index }}</span> {!! nl2br(e($item->stem)) !!}</div>
        @if($item->image_path)<img class="mb-1" style="max-width:100%" src="{{ asset('storage/'.$item->image_path) }}" alt="">@endif
        @foreach($item->options as $k => $opt)
            @php($isRight = $k === (int) $item->correct_index)
            @php($isMine = $item->answer_index !== null && $k === (int) $item->answer_index)
            <div class="border rounded p-1 mb-1 {{ $isRight ? 'border-success bg-light-success' : ($isMine ? 'border-danger' : '') }}">
                <strong>{{ __('special_programs/student.take.letters')[$k] ?? $k + 1 }}.</strong> {{ $opt['text'] ?? '' }}
                @if(! empty($opt['image']))<br><img style="max-width:100%;max-height:160px" src="{{ asset('storage/'.$opt['image']) }}" alt="">@endif
                @if($isRight)<span class="badge badge-success">{{ __($t.'correct_answer') }}</span>@endif
                @if($isMine && ! $isRight)<span class="badge badge-danger">{{ __($t.'your_answer') }}</span>@endif
            </div>
        @endforeach
    </div></div>
@endforeach
@endsection
