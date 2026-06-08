{{-- Evidence display + add trigger for one node (item or indicator).
     The add/delete forms live OUTSIDE the main #ex-form (HTML forbids nested
     forms); this partial only shows chips + a button that opens the shared modal. --}}
@php $nodeEvidence = $evidences[$nodeKey] ?? []; @endphp
<div class="mt-2">
    @forelse ($nodeEvidence as $ev)
        <span class="ev-chip">
            <i class="la {{ $ev->type === 'link' ? 'la-link' : 'la-file' }}"></i>
            @if ($ev->type === 'link')
                <a href="{{ $ev->url }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($ev->url, 40) }}</a>
            @else
                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(optional($ev->file)->path) }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($ev->original_name ?? __('evaluation.evidence.open'), 30) }}</a>
            @endif
            @unless ($locked)
                <button type="button" class="btn btn-link p-0 text-danger ev-del" data-id="{{ $ev->id }}" title="@lang('evaluation.evidence.remove')"><i class="la la-times"></i></button>
            @endunless
        </span>
    @empty
        <span class="text-muted small">@lang('evaluation.evidence.none')</span>
    @endforelse
    @unless ($locked)
        <button type="button" class="btn btn-sm btn-outline-info ev-add-btn"
                data-node-type="{{ $nodeType }}" data-node-id="{{ $nodeId }}">
            <i class="la la-paperclip"></i> @lang('evaluation.execute.actions.add_evidence')
        </button>
    @endunless
</div>
