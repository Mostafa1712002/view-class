@extends('layouts.app')

@section('title', 'سجل القراءة')
@section('page-title', 'سجل القراءة')
@section('body_class', 'theme-light')

@section('content')
<section class="vc-ann-log">

    {{-- Page header + breadcrumb --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:.75rem;margin-bottom:1rem">
        <div>
            <h2 style="margin:0;font-size:1.45rem;font-weight:800;color:var(--gray-900)">سجل القراءة</h2>
            <p class="text-muted" style="margin:.15rem 0 0;font-size:.875rem">{{ $announcement->title }}</p>
            <nav><ol class="breadcrumb" style="margin:.1rem 0 0;padding:0;background:transparent">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.announcements.index') }}">الإعلانات</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.announcements.show', $announcement->id) }}">عرض</a></li>
                <li class="breadcrumb-item active" aria-current="page">سجل القراءة</li>
            </ol></nav>
        </div>
        <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary btn-sm">
            <x-svg-icon name="arrow-right" :size="15" /> عودة
        </a>
    </div>

    {{-- KPI strip --}}
    <div style="display:flex;gap:1rem;margin-bottom:1rem;flex-wrap:wrap">
        <div class="ds-card card" style="flex:1;min-width:160px">
            <div class="card-body" style="text-align:center;padding:1rem">
                <div style="font-size:1.6rem;font-weight:800;color:var(--status-success-text)">{{ $read->count() }}</div>
                <div class="text-muted" style="font-size:.82rem;display:flex;align-items:center;justify-content:center;gap:.3rem;margin-top:.2rem">
                    <x-svg-icon name="check-circle-fill" :size="13" /> قرأوا
                </div>
            </div>
        </div>
        <div class="ds-card card" style="flex:1;min-width:160px">
            <div class="card-body" style="text-align:center;padding:1rem">
                <div style="font-size:1.6rem;font-weight:800;color:var(--status-warning-text)">{{ $unread->count() }}</div>
                <div class="text-muted" style="font-size:.82rem;display:flex;align-items:center;justify-content:center;gap:.3rem;margin-top:.2rem">
                    <x-svg-icon name="x-circle-fill" :size="13" /> لم يقرأوا
                </div>
            </div>
        </div>
        <div class="ds-card card" style="flex:1;min-width:160px">
            <div class="card-body" style="text-align:center;padding:1rem">
                <div style="font-size:1.6rem;font-weight:800;color:var(--navy)">{{ $read->count() + $unread->count() }}</div>
                <div class="text-muted" style="font-size:.82rem;display:flex;align-items:center;justify-content:center;gap:.3rem;margin-top:.2rem">
                    <x-svg-icon name="people" :size="13" /> إجمالي المستهدفين
                </div>
            </div>
        </div>
    </div>

    {{-- Read table --}}
    <div class="ds-card card" style="margin-bottom:1rem">
        <div class="ds-card-header card-header" style="display:flex;align-items:center;gap:.4rem">
            <x-svg-icon name="check-circle-fill" :size="14" style="color:var(--status-success)" />
            <h5 class="ds-card-title" style="margin:0">قرأوا الإعلان</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="readTable">
                <thead>
                    <tr>
                        <th>المستخدم</th>
                        <th>الدور</th>
                        <th>وقت العرض</th>
                        <th>وقت تأكيد القراءة</th>
                        <th>IP</th>
                    </tr>
                </thead>
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
                    <tr>
                        <td colspan="5" class="text-center text-muted" style="padding:1.5rem">لا أحد بعد</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Unread table --}}
    <div class="ds-card card">
        <div class="ds-card-header card-header" style="display:flex;align-items:center;gap:.4rem">
            <x-svg-icon name="x-circle-fill" :size="14" class="text-muted" />
            <h5 class="ds-card-title" style="margin:0">لم يقرأوا الإعلان</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>المستخدم</th></tr>
                </thead>
                <tbody>
                @forelse($unread as $u)
                    <tr><td>{{ $u->name }}</td></tr>
                @empty
                    <tr>
                        <td class="text-center text-muted" style="padding:1.5rem">الجميع قرأ الإعلان</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</section>
@endsection
