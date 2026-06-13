@extends('layouts.admin')

@section('title', __('exam_bank.picker_title') . ' — ' . $exam->title)

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="content-header-row d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">{{ __('exam_bank.picker_title') }}</h1>
            <p class="text-muted mb-0">{{ $exam->title }}</p>
            <nav aria-label="breadcrumb" class="mt-1">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.exams.index') }}">الاختبارات</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.exams.show', $exam) }}">{{ $exam->title }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.exams.questions.index', $exam) }}">الأسئلة</a></li>
                    <li class="breadcrumb-item active">{{ __('exam_bank.picker_title') }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.exams.questions.index', $exam) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right me-1"></i>
            العودة للأسئلة
        </a>
    </div>

    {{-- Filter bar --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.exams.questions.bank-picker', $exam) }}" class="row g-3 align-items-end">
                {{-- Bank selector --}}
                <div class="col-md-3">
                    <label class="form-label">{{ __('exam_bank.picker_bank_label') }}</label>
                    <select name="bank_id" class="form-select">
                        <option value="">{{ __('exam_bank.picker_all_banks') }}</option>
                        @foreach($banks as $bank)
                            <option value="{{ $bank->id }}" @selected(request('bank_id') == $bank->id)>
                                {{ $bank->name_ar }}
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- Type selector --}}
                <div class="col-md-2">
                    <label class="form-label">{{ __('exam_bank.picker_type_label') }}</label>
                    <select name="type" class="form-select">
                        <option value="">{{ __('exam_bank.picker_all_types') }}</option>
                        @foreach(\App\Models\BankQuestion::TYPES as $t)
                            <option value="{{ $t }}" @selected(request('type') === $t)>
                                {{ __('questions.types.' . $t) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- Text search --}}
                <div class="col-md-4">
                    <label class="form-label">بحث</label>
                    <input type="text" name="q" class="form-control"
                           placeholder="{{ __('exam_bank.picker_search_ph') }}"
                           value="{{ request('q') }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i>
                        {{ __('exam_bank.picker_filter_btn') }}
                    </button>
                    <a href="{{ route('admin.exams.questions.bank-picker', $exam) }}" class="btn btn-outline-secondary">
                        {{ __('exam_bank.picker_reset_btn') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Picker form --}}
    <form method="POST" action="{{ route('admin.exams.questions.add-from-bank', $exam) }}" id="picker-form">
        @csrf

        <div class="card">
            <div class="card-body p-0">
                @if($bankQuestions->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px">
                                        <input type="checkbox" id="select-all" class="form-check-input">
                                    </th>
                                    <th>{{ __('questions.columns.body') }}</th>
                                    <th>{{ __('questions.columns.type') }}</th>
                                    <th>البنك</th>
                                    <th>{{ __('questions.columns.points') }}</th>
                                    <th>{{ __('questions.columns.difficulty') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bankQuestions as $bq)
                                    <tr>
                                        <td>
                                            <input type="checkbox"
                                                   name="bank_question_ids[]"
                                                   value="{{ $bq->id }}"
                                                   class="form-check-input picker-cb">
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-truncate" style="max-width:420px">
                                                {{ $bq->body_ar ?? $bq->question_code ?? "#{$bq->id}" }}
                                            </div>
                                            @if($bq->lesson)
                                                <small class="text-muted">{{ $bq->lesson->name_ar }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                {{ __('questions.types.' . $bq->type) }}
                                            </span>
                                        </td>
                                        <td class="text-muted small">{{ $bq->bank->name_ar ?? '—' }}</td>
                                        <td>{{ number_format((float)$bq->points, 1) }}</td>
                                        <td>
                                            @php
                                                $diffs = [1 => 'success', 2 => 'warning', 3 => 'danger'];
                                            @endphp
                                            <span class="badge bg-{{ $diffs[$bq->difficulty] ?? 'secondary' }}">
                                                {{ __('questions.difficulty.' . $bq->difficulty) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if($bankQuestions->hasPages())
                        <div class="px-3 py-2 border-top">
                            {{ $bankQuestions->withQueryString()->links() }}
                        </div>
                    @endif

                    {{-- Submit bar --}}
                    <div class="p-3 border-top d-flex justify-content-between align-items-center">
                        <span class="text-muted small" id="selected-count">لا يوجد تحديد</span>
                        <button type="submit" class="btn btn-success" id="add-btn">
                            <i class="bi bi-plus-circle me-1"></i>
                            {{ __('exam_bank.picker_add_btn') }}
                        </button>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-database-slash display-4 text-muted"></i>
                        <p class="mt-3 text-muted">{{ __('exam_bank.picker_empty') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const selectAll = document.getElementById('select-all');
    const cbs       = document.querySelectorAll('.picker-cb');
    const counter   = document.getElementById('selected-count');
    const form      = document.getElementById('picker-form');

    function updateCount() {
        const n = document.querySelectorAll('.picker-cb:checked').length;
        if (counter) counter.textContent = n > 0 ? 'محدد: ' + n + ' سؤال' : 'لا يوجد تحديد';
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            cbs.forEach(cb => { cb.checked = this.checked; });
            updateCount();
        });
    }

    cbs.forEach(cb => cb.addEventListener('change', updateCount));

    if (form) {
        form.addEventListener('submit', function (e) {
            const checked = document.querySelectorAll('.picker-cb:checked').length;
            if (checked === 0) {
                e.preventDefault();
                if (window.vcConfirm) {
                    window.vcConfirm({ title: '{{ __('exam_bank.picker_none_selected') }}', showCancelButton: false });
                } else {
                    alert('{{ __('exam_bank.picker_none_selected') }}');
                }
            }
        });
    }
})();
</script>
@endpush
