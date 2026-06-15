@extends('layouts.app')
@section('title', 'الأسابيع الدراسية')
@section('body_class', 'theme-light')
@php
    $user = auth()->user();
    $canCreate = $user->canDo('weeks.create');
    $canEdit   = $user->canDo('weeks.edit');
    $canDelete = $user->canDo('weeks.delete');
@endphp
@section('content')
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">الأسابيع الدراسية</h2>
    <div class="breadcrumb-wrapper"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
        <li class="breadcrumb-item active">الأسابيع الدراسية</li>
    </ol></div>
</div></div>
<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-warning">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    {{-- Term selector --}}
    <div class="card mb-2"><div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label" style="font-size:12px;font-weight:600;color:#475569;">الفصل الدراسي</label>
                <select name="term_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @forelse($terms as $t)
                        <option value="{{ $t->id }}" @selected($termId == $t->id)>{{ $t->name }}{{ optional($t->academicYear)->name ? ' — '.$t->academicYear->name : '' }}</option>
                    @empty
                        <option value="">لا توجد فصول دراسية ضمن نطاقك</option>
                    @endforelse
                </select>
            </div>
        </form>
    </div></div>

    @if($term)
        <div class="row">
            {{-- Add single week --}}
            @if($canCreate)
            <div class="col-md-6 mb-2">
                <div class="card h-100"><div class="card-body">
                    <h6 class="mb-3">إضافة أسبوع</h6>
                    <form method="POST" action="{{ route('admin.qb.weeks.store') }}" class="row g-2">
                        @csrf
                        <input type="hidden" name="academic_term_id" value="{{ $term->id }}">
                        <div class="col-md-3"><label class="form-label">رقم الأسبوع</label><input type="number" name="sort_order" min="1" class="form-control form-control-sm" required></div>
                        <div class="col-md-9"><label class="form-label">اسم الأسبوع</label><input type="text" name="name" class="form-control form-control-sm" required></div>
                        <div class="col-md-6"><label class="form-label">تاريخ البداية</label><input type="date" name="start_date" class="form-control form-control-sm" required></div>
                        <div class="col-md-6"><label class="form-label">تاريخ النهاية</label><input type="date" name="end_date" class="form-control form-control-sm" required></div>
                        <div class="col-12"><button class="btn btn-warning btn-sm"><x-svg-icon name="plus-circle-fill" :size="14" /> إضافة الأسبوع</button></div>
                    </form>
                </div></div>
            </div>
            {{-- Bulk create --}}
            <div class="col-md-6 mb-2">
                <div class="card h-100"><div class="card-body">
                    <h6 class="mb-3">إدخال أسابيع دفعة واحدة</h6>
                    <form method="POST" action="{{ route('admin.qb.weeks.bulk-store') }}" class="row g-2">
                        @csrf
                        <input type="hidden" name="academic_term_id" value="{{ $term->id }}">
                        <div class="col-md-4"><label class="form-label">عدد الأسابيع</label><input type="number" name="count" min="1" max="40" value="19" class="form-control form-control-sm" required></div>
                        <div class="col-md-8"><label class="form-label">تاريخ بداية الأسبوع الأول</label><input type="date" name="start_date" class="form-control form-control-sm" required></div>
                        <div class="col-md-12"><label class="form-label">بادئة الاسم</label><input type="text" name="name_prefix" value="الأسبوع" class="form-control form-control-sm"></div>
                        <div class="col-12"><button class="btn btn-outline-warning btn-sm"><x-svg-icon name="list-ol" :size="14" /> إنشاء الأسابيع</button>
                            <small class="text-muted d-block mt-1">يبدأ الترقيم بعد آخر أسبوع موجود، وكل أسبوع 7 أيام.</small>
                        </div>
                    </form>
                </div></div>
            </div>
            @endif
        </div>

        {{-- Weeks table --}}
        <form method="POST" action="{{ route('admin.qb.weeks.bulk-destroy') }}" id="bulkDeleteForm" onsubmit="return confirm('حذف الأسابيع المحددة؟')">
            @csrf
            <input type="hidden" name="academic_term_id" value="{{ $term->id }}">
            <div class="card"><div class="card-body p-0">
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <strong>قائمة الأسابيع ({{ $weeks->count() }})</strong>
                    @if($canDelete && $weeks->isNotEmpty())
                        <button type="submit" class="btn btn-sm btn-outline-danger"><x-svg-icon name="trash-fill" :size="14" /> حذف المحدد</button>
                    @endif
                </div>
                @if($weeks->isEmpty())
                    <div class="ds-empty"><div class="ds-empty-icon"><x-svg-icon name="calendar-week" :size="34" /></div>
                        <div class="ds-empty-title">لا توجد أسابيع</div>
                        <div class="ds-empty-desc">أضف أسبوعًا أو استخدم الإدخال الجماعي.</div></div>
                @else
                    <div class="table-responsive"><table class="table mb-0" style="font-size:13px;">
                        <thead><tr>
                            @if($canDelete)<th><input type="checkbox" onclick="document.querySelectorAll('.wk-cb').forEach(c=>c.checked=this.checked)"></th>@endif
                            <th>رقم الأسبوع</th><th>اسم الأسبوع</th><th>تاريخ البداية</th><th>تاريخ النهاية</th><th>الترم</th><th>العمليات</th>
                        </tr></thead>
                        <tbody>
                            @foreach($weeks as $w)
                                <tr>
                                    @if($canDelete)<td><input type="checkbox" class="wk-cb" name="ids[]" value="{{ $w->id }}"></td>@endif
                                    <td>{{ $w->sort_order }}</td>
                                    <td>{{ $w->name }}</td>
                                    <td>{{ optional($w->start_date)->format('Y-m-d') }}</td>
                                    <td>{{ optional($w->end_date)->format('Y-m-d') }}</td>
                                    <td>{{ $term->name }}</td>
                                    <td><div class="d-flex gap-1">
                                        @if($canEdit)<button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#wkEdit{{ $w->id }}"><x-svg-icon name="pencil-fill" :size="14" /></button>@endif
                                        @if($canDelete)
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="if(confirm('حذف الأسبوع؟'))document.getElementById('wkDel{{ $w->id }}').submit();"><x-svg-icon name="trash-fill" :size="14" /></button>
                                        @endif
                                    </div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table></div>
                @endif
            </div></div>
        </form>

        {{-- per-week delete + edit modals (outside the bulk form) --}}
        @foreach($weeks as $w)
            @if($canDelete)
                <form method="POST" action="{{ route('admin.qb.weeks.destroy', $w->id) }}" id="wkDel{{ $w->id }}" class="d-none">@csrf @method('DELETE')</form>
            @endif
            @if($canEdit)
            <div class="modal fade" id="wkEdit{{ $w->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
                <form method="POST" action="{{ route('admin.qb.weeks.update', $w->id) }}">@csrf @method('PUT')
                    <div class="modal-header"><h5 class="modal-title">تعديل الأسبوع</h5><button type="button" class="close" data-dismiss="modal" aria-label="إغلاق"><span aria-hidden="true">&times;</span></button></div>
                    <div class="modal-body row g-2">
                        <div class="col-md-4"><label class="form-label">رقم الأسبوع</label><input type="number" name="sort_order" min="1" value="{{ $w->sort_order }}" class="form-control" required></div>
                        <div class="col-md-8"><label class="form-label">اسم الأسبوع</label><input type="text" name="name" value="{{ $w->name }}" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">تاريخ البداية</label><input type="date" name="start_date" value="{{ optional($w->start_date)->format('Y-m-d') }}" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">تاريخ النهاية</label><input type="date" name="end_date" value="{{ optional($w->end_date)->format('Y-m-d') }}" class="form-control" required></div>
                    </div>
                    <div class="modal-footer"><button class="btn btn-warning">حفظ</button><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">إلغاء</button></div>
                </form>
            </div></div></div>
            @endif
        @endforeach
    @endif
</div>
@endsection
