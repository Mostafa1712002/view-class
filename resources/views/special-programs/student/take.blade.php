@extends('layouts.app')
@section('title', $exam->title)
@section('page-title', $exam->title.' — '.$subject?->name_ar)
@push('styles')
<style>
    .sp-bar{position:sticky;top:60px;z-index:20;background:#fff;border:1px solid #e5e5e5;border-radius:8px;padding:.6rem .8rem;margin-bottom:1rem}
    .sp-timer{font-weight:700;font-variant-numeric:tabular-nums;font-size:1.2rem}.sp-timer.warn{color:#e0a800}.sp-timer.danger{color:#dc3545}
    .sp-dots{display:flex;flex-wrap:wrap;gap:.3rem}.sp-dot{width:2rem;height:2rem;border-radius:50%;border:1px solid #bbb;background:#fff;font-size:.8rem;padding:0}
    .sp-dot.answered{background:#28a745;border-color:#28a745;color:#fff}.sp-dot.current{outline:2px solid #007bff}
    .sp-screen{display:none}.sp-screen.active{display:block}
    .sp-passage{background:#f8f9fa;border:1px solid #e5e5e5;border-radius:8px;padding:1rem;margin-bottom:1rem;max-height:60vh;overflow:auto;line-height:2;white-space:normal}
    @media(min-width:992px){.sp-screen.has-passage.active{display:grid;grid-template-columns:1fr 1fr;gap:1rem;align-items:start}.sp-passage{position:sticky;top:130px}}
    .sp-q{margin-bottom:1rem}.sp-opt{display:flex;gap:.6rem;align-items:flex-start;border:1px solid #ddd;border-radius:8px;padding:.6rem .8rem;margin-bottom:.5rem;cursor:pointer}
    .sp-opt:has(input:checked){border-color:#007bff;background:#eaf3ff}.sp-opt img{max-width:100%;max-height:180px}
    .sp-zoomable{max-width:100%;cursor:zoom-in}.sp-zoom{position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:2000;display:none;align-items:center;justify-content:center}
    .sp-zoom img{max-width:95vw;max-height:95vh}.sp-zoom.on{display:flex}
</style>
@endpush
@section('content')
<div id="sp-player"
     data-remaining="{{ $remaining }}"
     data-anti-cheat="{{ $antiCheat ? 1 : 0 }}"
     data-answer-url="{{ route('student.programs.answer', $attempt->id) }}"
     data-heartbeat-url="{{ route('student.programs.heartbeat', $attempt->id) }}"
     data-exit-url="{{ route('student.programs.exit', $attempt->id) }}"
     data-finish-url="{{ route('student.programs.finish', $attempt->id) }}"
     data-result-url="{{ route('student.programs.result', $attempt->id) }}"
     data-csrf="{{ csrf_token() }}"
     data-msg="{{ json_encode(__('special_programs/student.take'), JSON_UNESCAPED_UNICODE) }}">
    <div class="sp-bar">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>{{ __('special_programs/student.take.time_left') }}: <span class="sp-timer" id="sp-timer">--:--</span></div>
            <small id="sp-status" class="text-danger"></small>
        </div>
        <div class="sp-dots" id="sp-dots">
            @foreach($screens as $i => $group)
                <button type="button" class="sp-dot" data-go="{{ $i }}">{{ $i + 1 }}</button>
            @endforeach
        </div>
    </div>

    @foreach($screens as $i => $group)
        @php($passage = $group[0]->passage_id ? ($passages[$group[0]->passage_id] ?? null) : null)
        <section class="sp-screen {{ $passage ? 'has-passage' : '' }}" data-screen="{{ $i }}">
            @if($passage)
                <div class="sp-passage">
                    <strong>{{ __('special_programs/student.take.passage') }}</strong>
                    @if($passage->passage_image)<div class="my-1"><img class="sp-zoomable" src="{{ asset('storage/'.$passage->passage_image) }}" alt=""></div>@endif
                    {!! nl2br(e($passage->passage_text)) !!}
                </div>
            @endif
            <div>
            @foreach($group as $item)
                <div class="card sp-q"><div class="card-body" data-item="{{ $item->id }}">
                    <div class="text-muted small mb-1">{{ __('special_programs/student.take.question') }} {{ $item->order_index }}</div>
                    @if($item->stem)<p class="font-medium-1">{!! nl2br(e($item->stem)) !!}</p>@endif
                    @if($item->image_path)<img class="sp-zoomable mb-1" src="{{ asset('storage/'.$item->image_path) }}" alt="{{ __('special_programs/student.take.zoom') }}">@endif
                    @foreach($item->options as $k => $opt)
                        <label class="sp-opt">
                            <input type="radio" name="q{{ $item->id }}" value="{{ $k }}" data-item="{{ $item->id }}" @checked($item->answer_index !== null && (int) $item->answer_index === $k)>
                            <span>
                                <strong>{{ __('special_programs/student.take.letters')[$k] ?? $k + 1 }}.</strong>
                                {{ $opt['text'] ?? '' }}
                                @if(! empty($opt['image']))<br><img src="{{ asset('storage/'.$opt['image']) }}" alt="">@endif
                            </span>
                        </label>
                    @endforeach
                </div></div>
            @endforeach
            </div>
        </section>
    @endforeach

    <div class="d-flex justify-content-between mt-2">
        <button type="button" class="btn btn-outline-secondary" id="sp-prev">{{ __('special_programs/student.take.prev') }}</button>
        <button type="button" class="btn btn-success" id="sp-finish">{{ __('special_programs/student.take.finish') }}</button>
        <button type="button" class="btn btn-primary" id="sp-next">{{ __('special_programs/student.take.next') }}</button>
    </div>
    <div class="sp-zoom" id="sp-zoom"><img alt=""></div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/special-programs/player.js') }}"></script>
@endpush
