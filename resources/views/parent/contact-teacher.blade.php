@extends('layouts.admin')

@section('title', 'تواصل مع المعلم')
@section('body_class', 'theme-light')

@push('styles')
<style>
    .ct-hero {
        background: linear-gradient(135deg, #fffbeb 0%, #fff 60%);
        border: 1px solid #fde68a; border-radius: 16px;
        padding: 1rem 1.25rem; margin-bottom: 1.25rem;
    }
    .ct-hero h1 { font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 0; }
    .ct-hero small { color: #92400e; }
    .ct-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; margin-bottom: 1.25rem; box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04); }
    .ct-card .head { background: linear-gradient(135deg, var(--gold-300), var(--gold-500)); color: #fff; padding: .8rem 1.1rem; font-weight: 700; }
    .ct-table { width: 100%; margin: 0; }
    .ct-table th { background: #f8fafc; color: #475569; font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; padding: .7rem 1rem; border-bottom: 1px solid #e5e7eb; }
    .ct-table td { padding: .8rem 1rem; vertical-align: middle; color: #0f172a; border-top: 1px solid #f1f5f9; }
    .ct-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #fde68a, #fcd34d); color: #92400e; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; margin-inline-end: .55rem; }
    .ct-name-cell { display: flex; align-items: center; }
    .btn-gold-sm { background: linear-gradient(135deg, var(--gold-300), var(--gold-500)); border: 1px solid var(--gold-400); color: #fff; font-weight: 600; padding: .35rem .8rem; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: .35rem; font-size: .82rem; }
    .btn-gold-sm:hover { color: #fff; transform: translateY(-1px); }
    .ct-empty { text-align: center; padding: 2.5rem 1rem; color: #94a3b8; }
    .ct-empty i { font-size: 2.5rem; color: #cbd5e1; display: block; margin-bottom: .35rem; }
    .ct-empty-state { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; text-align: center; padding: 3rem 1rem; }
    .ct-empty-state i { font-size: 3rem; color: #cbd5e1; }
    .ct-empty-state h4 { color: #0f172a; margin-top: .75rem; font-weight: 700; }
    .ct-empty-state p { color: #64748b; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="ct-hero">
        <h1>تواصل مع المعلمين</h1>
        <small>أرسل رسالة لمعلمي أبنائك</small>
    </div>

    @if($children->count() > 0)
        @foreach($children as $child)
            <div class="ct-card">
                <div class="head"><i class="la la-chalkboard-teacher"></i> معلمو {{ $child->name }}</div>
                @if($child->teachers && $child->teachers->count() > 0)
                    <div class="table-responsive">
                        <table class="ct-table">
                            <thead>
                                <tr>
                                    <th>@lang('common.teacher')</th>
                                    <th>@lang('common.subject')</th>
                                    <th>@lang('common.email')</th>
                                    <th class="text-center">إجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($child->teachers as $teacher)
                                    <tr>
                                        <td>
                                            <div class="ct-name-cell">
                                                <span class="ct-avatar">{{ mb_substr($teacher->name, 0, 1) }}</span>
                                                <span>{{ $teacher->name }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $teacher->pivot->subject_name ?? '-' }}</td>
                                        <td dir="ltr">{{ $teacher->email }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('messages.create') }}?to={{ $teacher->id }}" class="btn-gold-sm">
                                                <i class="la la-envelope"></i> إرسال رسالة
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="ct-empty">
                        <i class="la la-user-slash"></i>
                        <p>لا يوجد معلمون مسجلون لهذا الطالب</p>
                    </div>
                @endif
            </div>
        @endforeach
    @else
        <div class="ct-empty-state">
            <i class="la la-user-friends"></i>
            <h4>لا يوجد أبناء مسجلون</h4>
            <p>يرجى التواصل مع إدارة المدرسة لربط حسابك بأبنائك</p>
        </div>
    @endif
</div>
@endsection
