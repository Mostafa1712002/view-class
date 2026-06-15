@extends('layouts.app')

@section('title', 'شحن الرصيد')
@section('page-title', 'شحن الرصيد')
@section('body_class', 'theme-light')

@php $user = auth()->user(); @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">شحن الرصيد</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item">الرسائل القصيرة</li>
                <li class="breadcrumb-item active">شحن الرصيد</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    {{-- Balance card --}}
    <div class="row g-3 mb-3">
        <div class="col-sm-4 col-lg-3">
            <div class="card text-center border-0" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;">
                <div class="card-body py-3">
                    <div style="font-size:.82rem;opacity:.85;margin-bottom:.25rem;">الرصيد المتاح</div>
                    <div style="font-size:2rem;font-weight:800;">{{ number_format($available) }}</div>
                    <div style="font-size:.75rem;opacity:.7;">رسالة</div>
                </div>
            </div>
        </div>
        @if($setting)
        <div class="col-sm-4 col-lg-3">
            <div class="card text-center border-0" style="background:#f8fafc;">
                <div class="card-body py-3">
                    <div class="small text-muted mb-1">إجمالي الرصيد</div>
                    <div style="font-size:1.5rem;font-weight:700;color:#0f172a;">{{ number_format($setting->sms_total ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4 col-lg-3">
            <div class="card text-center border-0" style="background:#f8fafc;">
                <div class="card-body py-3">
                    <div class="small text-muted mb-1">المستهلك</div>
                    <div style="font-size:1.5rem;font-weight:700;color:#b91c1c;">{{ number_format($setting->sms_used ?? 0) }}</div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="row g-3">
        {{-- LEFT: Banks + Recharge form --}}
        <div class="col-lg-7">
            {{-- Bank info cards --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0"><x-svg-icon name="cash-coin" :size="18" class="me-1" /> بيانات التحويل البنكي</h5></div>
                <div class="card-body">
                    <div class="row g-2" id="bank-cards">
                        @foreach($banks as $bankKey => $bankInfo)
                        <div class="col-sm-6">
                            <div class="bank-card p-3 border rounded" style="cursor:pointer;transition:all .15s;"
                                 data-bank="{{ $bankKey }}"
                                 onclick="selectBank('{{ $bankKey }}', this)">
                                <div class="fw-bold mb-1">{{ $bankInfo['label'] }}</div>
                                <div class="small text-muted">اسم الحساب: {{ $bankInfo['name'] }}</div>
                                <div class="small text-muted">رقم الحساب: <span dir="ltr">{{ $bankInfo['account'] }}</span></div>
                                <div class="small text-muted">IBAN: <span dir="ltr">{{ $bankInfo['iban'] }}</span></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Recharge form --}}
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><x-svg-icon name="upload" :size="18" class="me-1" /> طلب شحن رصيد</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.sms.credit.store') }}" enctype="multipart/form-data">
                        @csrf

                        <input type="hidden" name="bank_name" id="bank_name_hidden" value="{{ old('bank_name') }}">

                        <div class="mb-3">
                            <label class="form-label">البنك <span class="text-danger">*</span></label>
                            <select name="bank_name" id="bank_name_select"
                                    class="form-select @error('bank_name') is-invalid @enderror"
                                    onchange="document.getElementById('bank_name_hidden').value=this.value">
                                <option value="">— اختر البنك —</option>
                                @foreach($banks as $bankKey => $bankInfo)
                                    <option value="{{ $bankKey }}" {{ old('bank_name') === $bankKey ? 'selected' : '' }}>
                                        {{ $bankInfo['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">المبلغ المحوّل <span class="text-danger">*</span></label>
                                <input type="number" name="amount_transferred" min="1" step="0.01"
                                       value="{{ old('amount_transferred') }}"
                                       class="form-control @error('amount_transferred') is-invalid @enderror"
                                       placeholder="0.00">
                                @error('amount_transferred')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">تاريخ التحويل <span class="text-danger">*</span></label>
                                <input type="date" name="transfer_date"
                                       value="{{ old('transfer_date') }}"
                                       class="form-control @error('transfer_date') is-invalid @enderror">
                                @error('transfer_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-4">
                                <label class="form-label">اسم البنك المحوِّل</label>
                                <input type="text" name="from_bank" value="{{ old('from_bank') }}"
                                       class="form-control" placeholder="اسم البنك">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">رقم الحساب المحوِّل</label>
                                <input type="text" name="from_account_no" value="{{ old('from_account_no') }}"
                                       class="form-control" dir="ltr">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">اسم صاحب الحساب</label>
                                <input type="text" name="from_account_name" value="{{ old('from_account_name') }}"
                                       class="form-control">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">عدد الرسائل المطلوبة (اختياري)</label>
                            <input type="number" name="granted_credit" min="1"
                                   value="{{ old('granted_credit') }}"
                                   class="form-control @error('granted_credit') is-invalid @enderror"
                                   style="max-width:220px;" placeholder="عدد الرسائل">
                            @error('granted_credit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">إيصال التحويل <span class="text-danger">*</span></label>
                            <input type="file" name="receipt"
                                   class="form-control @error('receipt') is-invalid @enderror"
                                   accept=".pdf,.jpg,.jpeg,.png">
                            @error('receipt')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <x-svg-icon name="send" :size="14" class="me-1" /> إرسال
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- RIGHT: Requests history + Ledger --}}
        <div class="col-lg-5">
            {{-- Requests table --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">طلبات الشحن</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>البنك</th>
                                    <th>المبلغ</th>
                                    <th>التاريخ</th>
                                    <th>الحالة</th>
                                    <th>الرصيد الممنوح</th>
                                    <th>الإيصال</th>
                                    @if($isSuperAdmin)<th>تحكم</th>@endif
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($requests as $r)
                                @php
                                    $rColors = [
                                        'pending'  => 'bg-warning text-dark',
                                        'approved' => 'bg-success',
                                        'rejected' => 'bg-danger',
                                    ];
                                @endphp
                                <tr>
                                    <td class="small">{{ $r->bank_name ?? '—' }}</td>
                                    <td class="small">{{ number_format($r->amount_transferred ?? 0, 2) }}</td>
                                    <td class="small text-nowrap">{{ optional($r->transfer_date)->format('Y-m-d') ?? $r->transfer_date ?? '—' }}</td>
                                    <td><span class="badge {{ $rColors[$r->status] ?? 'bg-secondary' }}">{{ $r->statusLabel() }}</span></td>
                                    <td class="small">{{ $r->granted_credit ? number_format($r->granted_credit) : '—' }}</td>
                                    <td>
                                        @if($r->receipt_path)
                                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($r->receipt_path) }}"
                                           target="_blank" class="btn btn-xs btn-outline-secondary btn-sm" title="عرض الإيصال">
                                            <x-svg-icon name="eye-fill" :size="13" />
                                        </a>
                                        @else
                                        <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    @if($isSuperAdmin)
                                    <td>
                                        @if($r->status === 'pending')
                                        {{-- Approve --}}
                                        <button type="button" class="btn btn-sm btn-success mb-1"
                                                data-bs-toggle="modal" data-toggle="modal"
                                                data-target="#approveModal{{ $r->id }}"
                                                data-bs-target="#approveModal{{ $r->id }}">
                                            موافقة
                                        </button>
                                        {{-- Reject --}}
                                        <button type="button" class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal" data-toggle="modal"
                                                data-target="#rejectModal{{ $r->id }}"
                                                data-bs-target="#rejectModal{{ $r->id }}">
                                            رفض
                                        </button>
                                        @endif
                                    </td>
                                    @endif
                                </tr>

                                {{-- Approve Modal --}}
                                @if($isSuperAdmin && $r->status === 'pending')
                                <div class="modal fade" id="approveModal{{ $r->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">موافقة على الشحن</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="{{ route('admin.sms.credit.approve', $r->id) }}">
                                                @csrf
                                                <div class="modal-body">
                                                    <div class="mb-2">
                                                        <label class="form-label small">عدد الرسائل الممنوحة <span class="text-danger">*</span></label>
                                                        <input type="number" name="granted_credit" min="1" required
                                                               class="form-control form-control-sm"
                                                               value="{{ $r->granted_credit ?? '' }}">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small">ملاحظة (اختياري)</label>
                                                        <textarea name="admin_note" rows="2" class="form-control form-control-sm"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" class="btn btn-success btn-sm">تأكيد الموافقة</button>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" data-dismiss="modal">إلغاء</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                {{-- Reject Modal --}}
                                <div class="modal fade" id="rejectModal{{ $r->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">رفض الطلب</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="{{ route('admin.sms.credit.reject', $r->id) }}">
                                                @csrf
                                                <div class="modal-body">
                                                    <div class="mb-2">
                                                        <label class="form-label small">سبب الرفض</label>
                                                        <textarea name="admin_note" rows="2" class="form-control form-control-sm" placeholder="اكتب سبب الرفض…"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" class="btn btn-danger btn-sm">تأكيد الرفض</button>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" data-dismiss="modal">إلغاء</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endif

                            @empty
                                <tr><td colspan="{{ $isSuperAdmin ? 7 : 6 }}">
                                    <div class="empty-state text-center py-4">
                                        <div class="icon-wrap mb-2"><x-svg-icon name="cash-coin" :size="40" class="ic-muted" /></div>
                                        <p class="text-muted small">لا توجد طلبات شحن سابقة.</p>
                                    </div>
                                </td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($requests->hasPages())
                <div class="card-footer">{{ $requests->links() }}</div>
                @endif
            </div>

            {{-- Ledger --}}
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">سجل الرصيد (آخر 20 حركة)</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>النوع</th>
                                    <th>الرصيد قبل</th>
                                    <th>القيمة</th>
                                    <th>الرصيد بعد</th>
                                    <th>السبب</th>
                                    <th>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($ledger as $entry)
                                <tr>
                                    <td class="small">{{ $entry->type ?? '—' }}</td>
                                    <td class="small">{{ number_format($entry->balance_before ?? 0) }}</td>
                                    <td class="small fw-bold"
                                        style="color:{{ ($entry->amount ?? 0) >= 0 ? '#15803d' : '#b91c1c' }}">
                                        {{ ($entry->amount ?? 0) >= 0 ? '+' : '' }}{{ number_format($entry->amount ?? 0) }}
                                    </td>
                                    <td class="small">{{ number_format($entry->balance_after ?? 0) }}</td>
                                    <td class="small text-muted">{{ \Illuminate\Support\Str::limit($entry->reason ?? '—', 40) }}</td>
                                    <td class="small text-nowrap">{{ optional($entry->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6">
                                    <div class="text-center text-muted py-3 small">لا توجد حركات بعد.</div>
                                </td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .bank-card { border: 2px solid #e2e8f0 !important; border-radius: 10px; }
    .bank-card.selected { border-color: #1d4ed8 !important; background: #eff6ff; }
    .bank-card:hover { border-color: #93c5fd !important; }
</style>
@endpush

@push('scripts')
<script>
function selectBank(key, el) {
    document.querySelectorAll('.bank-card').forEach(function (c) { c.classList.remove('selected'); });
    el.classList.add('selected');
    document.getElementById('bank_name_hidden').value = key;
    var sel = document.getElementById('bank_name_select');
    if (sel) sel.value = key;
}
// pre-select if old value present
(function () {
    var old = document.getElementById('bank_name_hidden').value;
    if (old) {
        var card = document.querySelector('.bank-card[data-bank="' + old + '"]');
        if (card) card.classList.add('selected');
    }
})();
</script>
@endpush
