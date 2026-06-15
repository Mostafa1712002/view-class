@extends('layouts.app')

@section('title', 'عرض القطعة')
@section('body_class', 'theme-light')

@php
    $user = auth()->user();
    $canCreate = $user->canDo('question_banks.create');
    $canDelete = $user->canDo('question_banks.delete');
    $canEdit   = $user->canDo('question_banks.edit');
    $stLabels = ['draft'=>'مسودة','pending_review'=>'بانتظار المراجعة','approved'=>'معتمد','rejected'=>'مرفوض','archived'=>'مؤرشف'];
@endphp

@section('content')
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">القطعة #{{ $passage->id }}</h2>
    <div class="breadcrumb-wrapper"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.qb.passages.index') }}">أسئلة القطعة</a></li>
        <li class="breadcrumb-item active">عرض القطعة</li>
    </ol></div>
</div></div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-warning">{{ session('error') }}</div>@endif

    {{-- Passage body --}}
    <div class="card mb-3"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <span class="badge bg-secondary">{{ optional($passage->subject)->name ?? 'بدون مادة' }}</span>
                <span class="badge bg-info">{{ $stLabels[$passage->status] ?? $passage->status }}</span>
                @if($passage->passage_code)<span class="badge bg-light text-dark">كود: {{ $passage->passage_code }}</span>@endif
            </div>
            <div class="d-flex gap-2">
                @if($canEdit)<a href="{{ route('admin.qb.passages.edit', $passage->id) }}" class="btn btn-sm btn-outline-primary">تعديل القطعة</a>@endif
                @if($canCreate)<a href="{{ route('admin.qb.passages.questions.create', $passage->id) }}" class="btn btn-sm btn-warning">+ إضافة سؤال داخل القطعة</a>@endif
            </div>
        </div>
        @if($passage->passage_image)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($passage->passage_image) }}" class="mb-3" style="max-width:280px;border-radius:8px;border:1px solid #e2e8f0;">
        @endif
        <div style="white-space:pre-wrap;line-height:1.9;color:#1e293b;">{{ $passage->passage_text }}</div>
    </div></div>

    {{-- Child questions --}}
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">الأسئلة التابعة للقطعة ({{ $passage->questions->count() }})</h5>
        </div>

        @if($passage->questions->isEmpty())
            <div class="text-center text-muted py-4">
                لا توجد أسئلة بعد.
                @if($canCreate)<a href="{{ route('admin.qb.passages.questions.create', $passage->id) }}">أضف أول سؤال</a>@endif
            </div>
        @else
            <div class="accordion" id="pgQuestions">
                @foreach($passage->questions as $i => $q)
                    @php $ans = $q->answer_data ?? []; @endphp
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pgq{{ $q->id }}">
                                <span class="me-2 fw-bold">{{ $i + 1 }}.</span>
                                {{ \Illuminate\Support\Str::limit(strip_tags($q->body_ar ?? ''), 80) ?: '(سؤال صورة)' }}
                                <span class="badge bg-light text-dark ms-2">{{ $types[$q->type] ?? $q->type }}</span>
                                <span class="badge bg-secondary ms-1">{{ $stLabels[$q->status] ?? $q->status }}</span>
                            </button>
                        </h2>
                        <div id="pgq{{ $q->id }}" class="accordion-collapse collapse" data-bs-parent="#pgQuestions">
                            <div class="accordion-body">
                                <p class="mb-2"><strong>نص السؤال:</strong> {{ $q->body_ar ?: '— (صورة كاملة)' }}</p>
                                {{-- per-type answer summary --}}
                                @if($q->type === 'mcq')
                                    <ul class="mb-2">
                                        @foreach(($ans['options'] ?? []) as $oi => $opt)
                                            <li class="{{ ($ans['correct'] ?? null) === $oi ? 'text-success fw-bold' : '' }}">
                                                {{ chr(65 + $oi) }}. {{ $opt }} @if(($ans['correct'] ?? null) === $oi)✓@endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @elseif($q->type === 'true_false')
                                    <p class="mb-2">الإجابة الصحيحة: <strong>{{ in_array(($ans['correct'] ?? null), [true,'true','1',1], true) ? 'صح' : 'خطأ' }}</strong></p>
                                @elseif(in_array($q->type, ['essay','short'], true))
                                    <p class="mb-2">الإجابة النموذجية: <strong>{{ $ans['model_answer'] ?? '—' }}</strong></p>
                                @elseif($q->type === 'fill_blank')
                                    <p class="mb-2">الفراغات: <strong>{{ implode(' | ', $ans['blanks'] ?? []) }}</strong></p>
                                @elseif($q->type === 'matching')
                                    <ul class="mb-2">@foreach(($ans['pairs'] ?? []) as $pair)<li>{{ $pair['left'] ?? '' }} ⟷ {{ $pair['right'] ?? '' }}</li>@endforeach</ul>
                                @endif
                                <div class="d-flex gap-2">
                                    @if($canEdit)<a href="{{ route('admin.qb.questions.edit', $q->id) }}" class="btn btn-sm btn-outline-primary">تعديل السؤال</a>@endif
                                    @if($canDelete)
                                        <form method="POST" action="{{ route('admin.qb.passages.questions.detach', [$passage->id, $q->id]) }}" onsubmit="return confirm('حذف هذا السؤال الفرعي؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">حذف السؤال الفرعي</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div></div>
</div>
@endsection
