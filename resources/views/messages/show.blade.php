@extends('layouts.admin')

@section('title', $conversation->getDisplayName(auth()->user()))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="{{ route('messages.index') }}" class="btn btn-outline-secondary me-3">
                <i class="bi bi-arrow-right"></i>
            </a>
            <div>
                <h1 class="h4 mb-0">{{ $conversation->getDisplayName(auth()->user()) }}</h1>
                @if($conversation->type === 'group')
                    <small class="text-muted">
                        {{ $conversation->participants->count() }} مشاركين
                    </small>
                @endif
            </div>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary" id="toggleMute" title="{{ $conversation->pivot?->is_muted ? 'إلغاء الكتم' : 'كتم' }}">
                <i class="bi {{ $conversation->pivot?->is_muted ? 'bi-bell-slash' : 'bi-bell' }}"></i>
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="height: 500px; overflow-y: auto;" id="messagesContainer">
            @if($messages->count() > 0)
                @php $currentDate = null; @endphp
                @foreach($messages as $message)
                    @if($currentDate !== $message->created_at->format('Y-m-d'))
                        @php $currentDate = $message->created_at->format('Y-m-d'); @endphp
                        <div class="text-center my-3">
                            <span class="badge bg-secondary">
                                {{ $message->created_at->translatedFormat('l, j F Y') }}
                            </span>
                        </div>
                    @endif

                    <div class="d-flex mb-3 {{ $message->isSentBy(auth()->user()) ? 'justify-content-end' : '' }}">
                        @if(!$message->isSentBy(auth()->user()))
                            <div class="me-2">
                                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                                     style="width: 35px; height: 35px;">
                                    <span>{{ mb_substr($message->sender->name, 0, 1) }}</span>
                                </div>
                            </div>
                        @endif
                        <div class="{{ $message->isSentBy(auth()->user()) ? 'bg-primary text-white' : 'bg-light' }} rounded-3 p-3"
                             style="max-width: 70%;">
                            @if(!$message->isSentBy(auth()->user()) && $conversation->type === 'group')
                                <small class="d-block text-{{ $message->isSentBy(auth()->user()) ? 'white-50' : 'muted' }} mb-1">
                                    {{ $message->sender->name }}
                                </small>
                            @endif
                            <p class="mb-1" style="white-space: pre-wrap;">{{ $message->body }}</p>

                            @if($message->hasAttachment())
                                <div class="mt-2 p-2 rounded {{ $message->isSentBy(auth()->user()) ? 'bg-primary-subtle' : 'bg-white' }}">
                                    @if($message->isImage())
                                        <a href="{{ Storage::url($message->attachment_path) }}" target="_blank">
                                            <img src="{{ Storage::url($message->attachment_path) }}"
                                                 class="img-fluid rounded" style="max-height: 200px;">
                                        </a>
                                    @else
                                        <a href="{{ Storage::url($message->attachment_path) }}"
                                           class="text-{{ $message->isSentBy(auth()->user()) ? 'white' : 'primary' }} text-decoration-none"
                                           download="{{ $message->attachment_name }}">
                                            <i class="bi bi-file-earmark me-1"></i>
                                            {{ $message->attachment_name }}
                                        </a>
                                    @endif
                                </div>
                            @endif

                            <small class="d-block text-{{ $message->isSentBy(auth()->user()) ? 'white-50' : 'muted' }} mt-1">
                                {{ $message->created_at->format('H:i') }}
                                @if($message->isEdited())
                                    <span class="ms-1">(معدل)</span>
                                @endif
                            </small>
                        </div>
                        @if($message->isSentBy(auth()->user()))
                            <div class="ms-2 align-self-end">
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 delete-message"
                                        data-id="{{ $message->id }}" title="حذف">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="text-center py-5">
                    <i class="bi bi-chat display-1 text-muted"></i>
                    <p class="mt-3 text-muted">لا توجد رسائل بعد</p>
                </div>
            @endif
        </div>

        <div class="card-footer">
            <form action="{{ route('messages.reply', $conversation) }}" method="POST" enctype="multipart/form-data" id="replyForm">
                @csrf
                <div class="d-flex gap-2">
                    <div class="flex-grow-1">
                        <textarea name="message" class="form-control" rows="2"
                                  placeholder="اكتب رسالتك هنا..." required></textarea>
                    </div>
                    <div class="d-flex flex-column gap-1">
                        <label class="btn btn-outline-secondary" title="إرفاق ملف">
                            <i class="bi bi-paperclip"></i>
                            <input type="file" name="attachment" class="d-none" id="attachmentInput">
                        </label>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                </div>
                <div id="attachmentPreview" class="mt-2 d-none">
                    <span class="badge bg-secondary">
                        <i class="bi bi-file-earmark me-1"></i>
                        <span id="attachmentName"></span>
                        <button type="button" class="btn-close btn-close-white ms-2" id="removeAttachment" style="font-size: 0.5rem;"></button>
                    </span>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Scroll to bottom
    const container = document.getElementById('messagesContainer');
    container.scrollTop = container.scrollHeight;

    // Attachment preview
    const attachmentInput = document.getElementById('attachmentInput');
    const attachmentPreview = document.getElementById('attachmentPreview');
    const attachmentName = document.getElementById('attachmentName');
    const removeAttachment = document.getElementById('removeAttachment');

    attachmentInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            attachmentName.textContent = this.files[0].name;
            attachmentPreview.classList.remove('d-none');
        }
    });

    removeAttachment.addEventListener('click', function() {
        attachmentInput.value = '';
        attachmentPreview.classList.add('d-none');
    });

    // Delete message
    document.querySelectorAll('.delete-message').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('هل أنت متأكد من حذف هذه الرسالة؟')) return;

            const id = this.dataset.id;
            fetch(`/messages/message/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.closest('.d-flex.mb-3').remove();
                }
            });
        });
    });

    // Toggle mute
    document.getElementById('toggleMute').addEventListener('click', function() {
        fetch(`/messages/{{ $conversation->id }}/mute`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                const icon = this.querySelector('i');
                if (data.is_muted) {
                    icon.classList.remove('bi-bell');
                    icon.classList.add('bi-bell-slash');
                } else {
                    icon.classList.remove('bi-bell-slash');
                    icon.classList.add('bi-bell');
                }
            }
        });
    });
});
</script>
@endpush
@endsection
