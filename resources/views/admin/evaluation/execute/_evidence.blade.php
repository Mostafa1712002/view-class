{{-- Evidence display + add trigger for one node (item or indicator).
     The add/delete forms live OUTSIDE the main #ex-form (HTML forbids nested
     forms); this partial only shows chips + a button that opens the shared modal.

     Phase B (#204): shows evidence status chips, review note, and approve/reject/
     request-edit actions for authorized reviewers. Also renders a "gated" badge
     when the item's score is zeroed due to missing approved evidence.
--}}
@php
    $nodeEvidence  = $evidences[$nodeKey] ?? [];
    $isReviewer    = auth()->user()?->hasAnyRole(['super-admin', 'school-admin']);
    // Detect if this item's score is gated (breakdown entry with gated=true)
    $breakdown     = $evaluation->score_breakdown['breakdown'] ?? [];
    $itemGated     = false;
    if (isset($nodeId) && $nodeType === 'item') {
        foreach ($breakdown as $entry) {
            if ((int) ($entry['item_id'] ?? -1) === (int) $nodeId && !empty($entry['gated'])) {
                $itemGated = true;
                break;
            }
        }
    }
@endphp

@if ($itemGated)
    <div class="alert alert-warning alert-sm py-1 px-2 mt-1 mb-1 d-flex align-items-center gap-1">
        <i class="la la-clock-o"></i>
        <small>@lang('evaluation.evidence_gated_message')</small>
    </div>
@endif

<div class="mt-2">
    @forelse ($nodeEvidence as $ev)
        @php
            $evStatus = $ev->status ?? \App\Modules\Evaluation\Enums\EvidenceStatus::Approved;
            $evSource = $ev->source ?? \App\Modules\Evaluation\Enums\EvidenceSource::Manual;
        @endphp
        <div class="ev-chip-wrapper mb-1">
            <span class="ev-chip">
                <i class="la {{ $ev->type === 'link' ? 'la-link' : 'la-file' }}"></i>
                @if ($ev->type === 'link')
                    <a href="{{ $ev->url }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($ev->url, 40) }}</a>
                @else
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(optional($ev->file)->path) }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($ev->original_name ?? __('evaluation.evidence.open'), 30) }}</a>
                @endif

                {{-- Status chip --}}
                <span class="badge badge-{{ $evStatus->color() }} ms-1">{{ $evStatus->label() }}</span>

                {{-- Source badge (only when non-manual) --}}
                @if ($evSource !== \App\Modules\Evaluation\Enums\EvidenceSource::Manual)
                    <span class="badge badge-secondary ms-1">{{ $evSource->label() }}</span>
                @endif

                @unless ($locked)
                    <button type="button" class="btn btn-link p-0 text-danger ev-del" data-id="{{ $ev->id }}" title="@lang('evaluation.evidence.remove')"><i class="la la-times"></i></button>
                @endunless
            </span>

            {{-- Review note for rejected / needs_edit --}}
            @if ($ev->review_note && in_array($evStatus, [\App\Modules\Evaluation\Enums\EvidenceStatus::Rejected, \App\Modules\Evaluation\Enums\EvidenceStatus::NeedsEdit]))
                <div class="text-{{ $evStatus->color() }} small ps-2 mt-1">
                    <i class="la la-comment"></i> {{ $ev->review_note }}
                </div>
            @endif

            {{-- Review actions for authorized roles --}}
            @if ($isReviewer && $evStatus !== \App\Modules\Evaluation\Enums\EvidenceStatus::Approved)
                <div class="ev-review-actions mt-1 ps-2 d-flex gap-1 flex-wrap">
                    {{-- Approve --}}
                    <form method="POST" action="{{ route('evidence.approve', $ev->id) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-xs btn-success"
                                onclick="return vcConfirm(event, '@lang('evaluation.evidence_approve')')">
                            <i class="la la-check"></i> @lang('evaluation.evidence_approve')
                        </button>
                    </form>

                    {{-- Reject (uses SweetAlert input prompt for reason) --}}
                    <button type="button" class="btn btn-xs btn-danger ev-reject-btn"
                            data-reject-url="{{ route('evidence.reject', $ev->id) }}"
                            data-prompt-label="@lang('evaluation.evidence_reject_reason')">
                        <i class="la la-times-circle"></i> @lang('evaluation.evidence_reject')
                    </button>

                    {{-- Request edit --}}
                    <button type="button" class="btn btn-xs btn-info ev-request-edit-btn"
                            data-url="{{ route('evidence.requestEdit', $ev->id) }}"
                            data-prompt-label="@lang('evaluation.evidence_request_edit_note')">
                        <i class="la la-edit"></i> @lang('evaluation.evidence_request_edit')
                    </button>
                </div>
            @endif

            {{-- Already approved — show reviewer info if available --}}
            @if ($evStatus === \App\Modules\Evaluation\Enums\EvidenceStatus::Approved && $ev->reviewed_by)
                <div class="text-success small ps-2 mt-1">
                    <i class="la la-check-circle"></i>
                    @lang('evaluation.evidence_reviewed_by', ['name' => optional($ev->reviewer)->name ?? '—', 'date' => optional($ev->reviewed_at)->format('Y-m-d')])
                </div>
            @endif
        </div>
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

{{-- Inline reject / request-edit form helpers (hidden forms, submitted via JS) --}}
<form id="ev-reject-form-{{ $nodeKey }}" method="POST" style="display:none">
    @csrf
    <input type="hidden" name="note" class="ev-reject-note">
</form>
<form id="ev-request-edit-form-{{ $nodeKey }}" method="POST" style="display:none">
    @csrf
    <input type="hidden" name="note" class="ev-edit-note">
</form>

@once
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Reject buttons: SweetAlert input prompt for rejection reason
        document.querySelectorAll('.ev-reject-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url   = btn.dataset.rejectUrl;
                var label = btn.dataset.promptLabel;
                Swal.fire({
                    title: label,
                    input: 'textarea',
                    inputAttributes: { required: true },
                    showCancelButton: true,
                    confirmButtonText: btn.textContent.trim(),
                    preConfirm: function (val) {
                        if (!val || !val.trim()) {
                            Swal.showValidationMessage('{{ __('evaluation.evidence_reject_reason_required') }}');
                        }
                        return val;
                    }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = url;
                        form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">'
                                       + '<input type="hidden" name="note" value="">';
                        form.querySelector('[name="note"]').value = result.value;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });

        // Request-edit buttons: optional note prompt
        document.querySelectorAll('.ev-request-edit-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url   = btn.dataset.url;
                var label = btn.dataset.promptLabel;
                Swal.fire({
                    title: label,
                    input: 'textarea',
                    showCancelButton: true,
                    confirmButtonText: btn.textContent.trim(),
                }).then(function (result) {
                    if (result.isConfirmed) {
                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = url;
                        form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">'
                                       + '<input type="hidden" name="note" value="">';
                        form.querySelector('[name="note"]').value = result.value || '';
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    });
    </script>
    @endpush
@endonce
