@php
    $type = $question->type;
    $rows = $question->answers; // normalized rows
@endphp

<div class="qb-answer-view">
    <div class="mb-2 text-muted" style="font-size:13px;">
        <strong>نوع السؤال:</strong>
        {{ ['mcq'=>'اختيار من متعدد','true_false'=>'صح وخطأ','essay'=>'سؤال إنشائي','short'=>'إجابة قصيرة','fill_blank'=>'املأ الفراغ','matching'=>'توصيل'][$type] ?? $type }}
    </div>

    @if($rows->isEmpty())
        <div class="alert alert-light mb-0">لا توجد إجابة مسجلة.</div>
    @elseif($type === 'matching')
        <ul class="list-group">
            @foreach($rows as $r)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="d-inline-flex align-items-center gap-2">
                        @if($r->column_a_image)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($r->column_a_image) }}" style="max-width:90px;border-radius:6px;">@endif
                        {{ $r->column_a_text }}
                    </span>
                    <span class="text-muted">⟷</span>
                    <span class="d-inline-flex align-items-center gap-2">
                        @if($r->column_b_image)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($r->column_b_image) }}" style="max-width:90px;border-radius:6px;">@endif
                        {{ $r->column_b_text }}
                    </span>
                </li>
            @endforeach
        </ul>
    @elseif($type === 'fill_blank')
        <ol class="mb-0">
            @foreach($rows as $r)
                <li>الفراغ {{ $r->blank_number ?? $loop->iteration }}: <strong>{{ $r->answer_text }}</strong></li>
            @endforeach
        </ol>
    @else
        <ul class="list-group">
            @foreach($rows as $r)
                <li class="list-group-item d-flex justify-content-between align-items-center {{ $r->is_correct ? 'list-group-item-success' : '' }}">
                    <span>
                        @if($r->answer_image)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($r->answer_image) }}" style="max-width:120px;border-radius:6px;">
                        @endif
                        {{ $r->answer_text }}
                    </span>
                    @if($r->is_correct)
                        <span class="badge bg-success">الإجابة الصحيحة</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
