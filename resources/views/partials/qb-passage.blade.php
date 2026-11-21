{{-- Reading passage (القطعة), shown once above its sub-questions. Expects $passageText; $passageImage = public-disk path (optional). --}}
<div class="border rounded p-3 mb-3" style="background:#f8fafc;border-color:#bfdbfe !important">
    <span class="badge bg-primary mb-2">قطعة</span>
    @if (! empty($passageImage))
        <div class="mb-2"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($passageImage) }}" alt="القطعة" style="max-width:100%;border-radius:8px"></div>
    @endif
    <div style="white-space:pre-wrap;line-height:1.9;color:#1e293b">{{ $passageText }}</div>
</div>
