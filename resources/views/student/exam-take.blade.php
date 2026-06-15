@extends('layouts.admin')

@section('title', $exam->title)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $exam->title }}</h1>
        <a href="{{ route('student.exams') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-right me-1"></i>رجوع
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-3 text-muted small">
                <span><i class="bi bi-journal-bookmark me-1"></i>{{ $exam->subject->name ?? '-' }}</span>
                @if($exam->duration_minutes)
                    <span><i class="bi bi-clock me-1"></i>{{ $exam->duration_minutes }} دقيقة</span>
                @endif
                <span><i class="bi bi-award me-1"></i>{{ $exam->total_marks }} درجة</span>
                <span><i class="bi bi-list-ol me-1"></i>{{ $exam->questions->count() }} سؤال</span>
            </div>
            @if($exam->description)
                <p class="mt-3 mb-0">{{ $exam->description }}</p>
            @endif
        </div>
    </div>

    @if(! $attempt)
        {{-- Intro: not started yet --}}
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-pencil-square display-4 text-primary"></i>
                <h5 class="mt-3">أنت على وشك بدء الاختبار</h5>
                <p class="text-muted">بمجرد الضغط على "ابدأ الاختبار" يبدأ احتساب الوقت.</p>
                <form method="POST" action="{{ route('student.exams.start', $exam) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-play-fill me-1"></i>ابدأ الاختبار
                    </button>
                </form>
            </div>
        </div>
    @else
        {{-- === Anti-cheat (ac) === warning banner shown while the attempt is active --}}
        <div class="alert alert-warning d-flex align-items-center" id="anti-cheat-notice">
            <i class="bi bi-shield-exclamation me-2"></i>
            <span>
                هذا الاختبار محمي ضد الغش. لا تغادر الصفحة ولا تغيّر التبويب ولا تفتح الاختبار في نافذة أخرى.
                تُسجَّل كل محاولة خروج، وبعد {{ $exam->max_exit_attempts ?: \App\Http\Controllers\StudentExamController::AUTO_END_THRESHOLD }} محاولات يتم إنهاء الاختبار تلقائياً.
            </span>
        </div>

        {{-- Active attempt: render the questions form --}}
        <form method="POST" action="{{ route('student.exams.submit', $exam) }}" id="exam-form">
            @csrf
            @foreach($exam->questions as $index => $question)
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h6 class="mb-3">
                                <span class="badge bg-secondary me-2">{{ $index + 1 }}</span>
                                {{ $question->question }}
                            </h6>
                            <span class="text-muted small">{{ $question->marks }} درجة</span>
                        </div>

                        @if($question->type === 'multiple_choice')
                            @foreach($question->getOptionsArray() as $opt)
                                <div class="form-check">
                                    <input class="form-check-input" type="radio"
                                           name="answers[{{ $question->id }}]"
                                           id="q{{ $question->id }}_{{ $loop->index }}"
                                           value="{{ $opt }}">
                                    <label class="form-check-label" for="q{{ $question->id }}_{{ $loop->index }}">{{ $opt }}</label>
                                </div>
                            @endforeach
                        @elseif($question->type === 'true_false')
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="answers[{{ $question->id }}]"
                                       id="q{{ $question->id }}_true" value="صح">
                                <label class="form-check-label" for="q{{ $question->id }}_true">صح</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="answers[{{ $question->id }}]"
                                       id="q{{ $question->id }}_false" value="خطأ">
                                <label class="form-check-label" for="q{{ $question->id }}_false">خطأ</label>
                            </div>
                        @elseif($question->type === 'short_answer')
                            <input type="text" class="form-control" name="answers[{{ $question->id }}]"
                                   placeholder="اكتب إجابتك">
                        @else
                            <textarea class="form-control" rows="4" name="answers[{{ $question->id }}]"
                                      placeholder="اكتب إجابتك"></textarea>
                        @endif
                    </div>
                </div>
            @endforeach

            <div class="d-flex justify-content-end mb-5">
                <button type="submit" class="btn btn-success" id="exam-submit-btn"
                        onclick="return confirm('هل أنت متأكد من تسليم الاختبار؟');">
                    <i class="bi bi-check-circle me-1"></i>تسليم الاختبار
                </button>
            </div>
        </form>

        {{-- === Anti-cheat (ac) === full-screen lock overlay (hidden until triggered) --}}
        <div id="exam-lock-overlay" style="display:none;position:fixed;inset:0;z-index:2000;background:rgba(33,37,41,.92);color:#fff;align-items:center;justify-content:center;text-align:center;padding:24px;">
            <div>
                <i class="bi bi-lock-fill" style="font-size:3rem;color:#cfa046;"></i>
                <h4 class="mt-3 mb-2" id="exam-lock-title">تم قفل الاختبار</h4>
                <p class="mb-3" id="exam-lock-message">تم فتح هذا الاختبار في نافذة أو جهاز آخر.</p>
                <a href="{{ route('student.exams') }}" class="btn btn-light btn-sm" id="exam-lock-back" style="display:none;">العودة للاختبارات</a>
            </div>
        </div>
    @endif
</div>
@endsection

@if($attempt)
@push('scripts')
<script>
(function () {
    'use strict';

    var examId      = {{ $exam->id }};
    var sessionTok  = @json($sessionToken);
    var csrfToken   = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var exitUrl     = "{{ route('student.exams.exit-attempt', $exam) }}";
    var beatUrl     = "{{ route('student.exams.heartbeat', $exam) }}";
    var resultUrl   = "{{ route('student.exams.result', $exam) }}";
    var threshold   = {{ $exam->max_exit_attempts ?: \App\Http\Controllers\StudentExamController::AUTO_END_THRESHOLD }};

    var form        = document.getElementById('exam-form');
    var overlay     = document.getElementById('exam-lock-overlay');
    var lockTitle   = document.getElementById('exam-lock-title');
    var lockMsg     = document.getElementById('exam-lock-message');
    var lockBack    = document.getElementById('exam-lock-back');

    var submitting  = false;   // set true while the student deliberately submits
    var locked      = false;   // exam locked (superseded by another session / auto-ended)
    var lastBeacon  = 0;       // throttle so a single blur doesn't spam the log

    function lockExam(title, msg, showBack) {
        if (locked) { return; }
        locked = true;
        if (title) { lockTitle.textContent = title; }
        if (msg)   { lockMsg.textContent = msg; }
        if (showBack) { lockBack.style.display = 'inline-block'; }
        overlay.style.display = 'flex';
        // disable the form so no further answers / submit are possible
        if (form) {
            form.querySelectorAll('input, textarea, button, select').forEach(function (el) {
                el.disabled = true;
            });
        }
    }

    // --- Exit-attempt beacon -------------------------------------------------
    function reportExit(type, useBeacon) {
        if (locked || submitting) { return; }
        var now = Date.now();
        if (now - lastBeacon < 800) { return; } // de-dupe rapid-fire events
        lastBeacon = now;

        var payload = 'type=' + encodeURIComponent(type) + '&_token=' + encodeURIComponent(csrfToken);

        // keepalive lets the request survive page navigation / unload
        fetch(exitUrl, {
            method: 'POST',
            keepalive: true,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: payload
        }).then(function (r) { return r.json(); })
          .then(function (data) {
              if (data && data.auto_ended) {
                  lockExam('تم إنهاء الاختبار تلقائياً',
                           'تجاوزت الحد المسموح به لمحاولات الخروج (' + threshold + '). تم تسليم الاختبار.',
                           true);
                  setTimeout(function () { window.location = resultUrl; }, 2500);
              }
          }).catch(function () { /* swallow: best-effort logging */ });
    }

    // --- Detection: tab change / window blur --------------------------------
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') { reportExit('tab_hidden'); }
    });
    window.addEventListener('blur', function () { reportExit('window_blur'); });

    // --- Detection: leaving the page (don't allow leaving until submit) -----
    window.addEventListener('beforeunload', function (e) {
        if (submitting || locked) { return; }
        reportExit('beforeunload', true);
        e.preventDefault();
        e.returnValue = '';
        return '';
    });

    // --- Prevent Back button -------------------------------------------------
    history.pushState(null, '', location.href);
    window.addEventListener('popstate', function () {
        if (submitting || locked) { return; }
        reportExit('back_navigation');
        history.pushState(null, '', location.href);
    });

    // --- Multi-tab guard (same browser) via BroadcastChannel ----------------
    if ('BroadcastChannel' in window) {
        var chan = new BroadcastChannel('exam-' + examId);
        // announce that this tab now owns the exam
        chan.postMessage({ claim: sessionTok });
        chan.onmessage = function (ev) {
            if (ev.data && ev.data.claim && ev.data.claim !== sessionTok) {
                // another tab in this browser claimed the exam → this one is stale
                reportExit('multi_tab');
                lockExam('الاختبار مفتوح في تبويب آخر',
                         'لا يُسمح بفتح الاختبار في أكثر من تبويب. أكمل الاختبار في التبويب الآخر.',
                         true);
            }
        };
    }

    // --- Single-session heartbeat (cross-device) ----------------------------
    function heartbeat() {
        if (locked || submitting) { return; }
        fetch(beatUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'token=' + encodeURIComponent(sessionTok) + '&_token=' + encodeURIComponent(csrfToken)
        }).then(function (r) { return r.json(); })
          .then(function (data) {
              if (data && data.valid === false) {
                  if (data.reason === 'submitted') {
                      lockExam('انتهى الاختبار', 'تم تسليم هذا الاختبار بالفعل.', true);
                      setTimeout(function () { window.location = resultUrl; }, 2000);
                  } else {
                      lockExam('جلسة جديدة للاختبار',
                               'تم فتح هذا الاختبار في جلسة أحدث. هذه الجلسة لم تعد صالحة.',
                               true);
                  }
              }
          }).catch(function () { /* ignore transient errors */ });
    }
    setInterval(heartbeat, 5000);

    // --- Allow leaving only via deliberate submit ---------------------------
    if (form) {
        form.addEventListener('submit', function () { submitting = true; });
    }
})();
</script>
@endpush
@endif
