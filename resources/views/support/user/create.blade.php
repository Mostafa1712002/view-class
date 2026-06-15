@extends('layouts.app')

@section('title', __('support.create_ticket_title'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('support.create_ticket_title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('support.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('my.support.index') }}">@lang('support.breadcrumb_my_tickets')</a></li>
                <li class="breadcrumb-item active">@lang('support.breadcrumb_new_ticket')</li>
            </ol>
        </div>
    </div>
</div>


<div class="card">
    <div class="card-header">
        <h4 class="card-title">@lang('support.create_ticket_title')</h4>
    </div>
    <div class="card-content">
        <div class="card-body">
            <form action="{{ route('my.support.store') }}" method="POST" id="ticketForm" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="type">@lang('support.field_type')</label>
                            <select name="type" id="type" class="form-control @error('type') is-invalid @enderror">
                                <option value="">— @lang('support.placeholder_select_type') —</option>
                                @foreach(\App\Models\SupportTicket::TYPES as $t)
                                    <option value="{{ $t }}" @selected(old('type') === $t)>@lang('support.type_'.$t)</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="department">@lang('support.field_department')</label>
                            <select name="department" id="department" class="form-control @error('department') is-invalid @enderror">
                                <option value="">— @lang('support.placeholder_select_department') —</option>
                                @foreach(\App\Models\SupportTicket::DEPARTMENTS as $d)
                                    <option value="{{ $d }}" @selected(old('department') === $d)>@lang('support.dept_'.$d)</option>
                                @endforeach
                            </select>
                            @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="priority">@lang('support.field_priority')</label>
                            <select name="priority" id="priority" class="form-control @error('priority') is-invalid @enderror">
                                <option value="low" @selected(old('priority') === 'low')>@lang('support.priority_low')</option>
                                <option value="normal" @selected(old('priority', 'normal') === 'normal')>@lang('support.priority_normal')</option>
                                <option value="high" @selected(old('priority') === 'high')>@lang('support.priority_high')</option>
                                <option value="urgent" @selected(old('priority') === 'urgent')>@lang('support.priority_urgent')</option>
                            </select>
                            @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="category">@lang('support.field_category') <span class="text-danger">*</span></label>
                    <select name="category" id="category" class="form-control @error('category') is-invalid @enderror" required>
                        <option value="">— @lang('support.placeholder_select_category') —</option>
                        <option value="technical" @selected(old('category') === 'technical')>@lang('support.category_technical')</option>
                        <option value="academic" @selected(old('category') === 'academic')>@lang('support.category_academic')</option>
                        <option value="billing" @selected(old('category') === 'billing')>@lang('support.category_billing')</option>
                        <option value="account" @selected(old('category') === 'account')>@lang('support.category_account')</option>
                        <option value="other" @selected(old('category') === 'other')>@lang('support.category_other')</option>
                    </select>
                    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- #186: a parent may link the ticket to one of their children --}}
                @if(!empty($children) && $children->count())
                    <div class="form-group">
                        <label for="related_student_id">@lang('support.field_related_student')</label>
                        <select name="related_student_id" id="related_student_id" class="form-control select2 @error('related_student_id') is-invalid @enderror">
                            <option value="">— @lang('support.placeholder_select_student') —</option>
                            @foreach($children as $child)
                                <option value="{{ $child->id }}" @selected((string)old('related_student_id') === (string)$child->id)>{{ $child->name }}</option>
                            @endforeach
                        </select>
                        @error('related_student_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                <div class="form-group">
                    <label for="subject">@lang('support.field_subject') <span class="text-danger">*</span></label>
                    <input type="text" name="subject" id="subject" maxlength="160"
                        class="form-control @error('subject') is-invalid @enderror"
                        value="{{ old('subject') }}" required>
                    @error('subject')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="body">@lang('support.field_body') <span class="text-danger">*</span></label>
                    <textarea name="body" id="body" rows="6"
                        class="form-control @error('body') is-invalid @enderror"
                        required>{{ old('body') }}</textarea>
                    @error('body')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="problem_url">@lang('support.field_problem_url')</label>
                    <input type="url" name="problem_url" id="problem_url"
                        class="form-control @error('problem_url') is-invalid @enderror"
                        value="{{ old('problem_url') }}" placeholder="{{ __('support.placeholder_problem_url') }}">
                    @error('problem_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="attachment">@lang('support.field_attachment')</label>
                    <input type="file" name="attachment" id="attachment"
                        class="form-control-file @error('attachment') is-invalid @enderror">
                    <small class="text-muted">@lang('support.attachment_hint')</small>
                    @error('attachment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="la la-paper-plane"></i> @lang('support.btn_submit_ticket')
                    </button>
                    <a href="{{ route('my.support.index') }}" class="btn btn-secondary mx-1">
                        @lang('support.btn_cancel')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('ticketForm');
    var btn  = document.getElementById('submitBtn');
    if (form && btn) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var msg = btn.getAttribute('data-confirm') || '{{ __("support.confirm_submit") }}';
            if (window.vcConfirm) {
                window.vcConfirm({ title: msg }).then(function (r) { if (r.isConfirmed) { form.submit(); } });
            } else {
                form.submit();
            }
        });
        btn.setAttribute('data-confirm', '{{ __("support.confirm_submit") }}');
    }
});
</script>
@endpush
