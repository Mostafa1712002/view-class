@extends('layouts.admin')

@section('title', 'رسالة جديدة')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">رسالة جديدة</h1>
        <a href="{{ route('messages.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right me-1"></i>
            العودة للرسائل
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('messages.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label">المستلم <span class="text-danger">*</span></label>
                    <select name="recipient_id" class="form-select @error('recipient_id') is-invalid @enderror" required>
                        <option value="">اختر المستلم</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('recipient_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                                @if($user->roles->isNotEmpty())
                                    ({{ $user->roles->pluck('name')->join(', ') }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('recipient_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">الموضوع</label>
                    <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                           value="{{ old('subject') }}" placeholder="موضوع الرسالة (اختياري)">
                    @error('subject')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">الرسالة <span class="text-danger">*</span></label>
                    <textarea name="message" class="form-control @error('message') is-invalid @enderror"
                              rows="6" required placeholder="اكتب رسالتك هنا...">{{ old('message') }}</textarea>
                    @error('message')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label">مرفق</label>
                    <input type="file" name="attachment" class="form-control @error('attachment') is-invalid @enderror">
                    <small class="text-muted">الحد الأقصى للحجم: 10 ميجابايت</small>
                    @error('attachment')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>
                        إرسال
                    </button>
                    <a href="{{ route('messages.index') }}" class="btn btn-secondary">
                        إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
