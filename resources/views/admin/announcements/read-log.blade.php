@extends('layouts.app')

@section('title', 'سجل القراءة')
@section('page-title', 'سجل القراءة')

@section('content')
<section class="vc-ann-log">
    <div class="ls-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <div>
            <h2 style="margin:0">سجل القراءة</h2>
            <p class="text-muted" style="margin:0">{{ $announcement->title }}</p>
        </div>
        <a href="{{ route('admin.announcements.index') }}" class="btn btn-light"><x-svg-icon name="arrow-right" :size="16" /> عودة</a>
    </div>

    <div style="display:flex;gap:1rem;margin-bottom:1rem;flex-wrap:wrap">
        <div class="card" style="flex:1;min-width:160px"><div class="card-body" style="text-align:center">
            <div style="font-size:1.6rem;font-weight:700">{{ $read->count() }}</div>
            <div class="text-muted">قرأوا</div>
        </div></div>
        <div class="card" style="flex:1;min-width:160px"><div class="card-body" style="text-align:center">
            <div style="font-size:1.6rem;font-weight:700">{{ $unread->count() }}</div>
            <div class="text-muted">لم يقرأوا</div>
        </div></div>
        <div class="card" style="flex:1;min-width:160px"><div class="card-body" style="text-align:center">
            <div style="font-size:1.6rem;font-weight:700">{{ $read->count() + $unread->count() }}</div>
            <div class="text-muted">إجمالي المستهدفين</div>
        </div></div>
    </div>

    <div class="card" style="margin-bottom:1rem"><div class="card-body" style="padding:0">
        <div style="padding:.7rem 1rem;border-bottom:1px solid #f1f5f9"><strong><x-svg-icon name="check-circle-fill" :size="14" class="ic-success" /> قرأوا الإعلان</strong></div>
        <div class="table-responsive">
            <table class="table" id="readTable">
                <thead><tr><th>المستخدم</th><th>الدور</th><th>وقت العرض</th><th>وقت تأكيد القراءة</th><th>IP</th></tr></thead>
                <tbody>
                @forelse($read as $u)
                    <tr>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->role ?? '—' }}</td>
                        <td>{{ optional($u->viewed_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>{{ optional($u->read_confirmed_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>{{ $u->read_ip ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">لا أحد بعد</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div></div>

    <div class="card"><div class="card-body" style="padding:0">
        <div style="padding:.7rem 1rem;border-bottom:1px solid #f1f5f9"><strong><x-svg-icon name="x-circle-fill" :size="14" class="ic-muted" /> لم يقرأوا الإعلان</strong></div>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>المستخدم</th></tr></thead>
                <tbody>
                @forelse($unread as $u)
                    <tr><td>{{ $u->name }}</td></tr>
                @empty
                    <tr><td class="text-center text-muted">الجميع قرأ الإعلان</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div></div>
</section>
@endsection
