@extends('layouts.app')
@section('body_class','theme-light')
@section('title','المواقع التعليمية')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-7 mb-2">
        <h2 class="content-header-title mb-0">إدارة المواقع التعليمية</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('educational-sites.display') }}">عرض المواقع</a></li>
            <li class="breadcrumb-item active">الإدارة</li>
        </ol>
    </div>
    <div class="content-header-right col-md-5 text-md-right">
        @if(auth()->user()->canDo('educational_sites.create'))
            <a href="{{ route('admin.educational-sites.create') }}" class="btn btn-primary btn-sm">
                <x-svg-icon name="plus-lg" :size="16" /> إضافة موقع
            </a>
        @endif
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="ds-card mb-3"><div class="ds-card-body">
        <form method="GET" class="form-row align-items-end">
            <div class="col-md-4 mb-2">
                <label>اسم الموقع</label>
                <input type="text" name="name" value="{{ request('name') }}" class="form-control" placeholder="بحث بالاسم...">
            </div>
            <div class="col-md-3 mb-2">
                <label>الحالة</label>
                <select name="status" class="form-control">
                    <option value="">— الكل —</option>
                    <option value="1" {{ request('status')==='1'?'selected':'' }}>مفعّل</option>
                    <option value="0" {{ request('status')==='0'?'selected':'' }}>معطّل</option>
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <label>الفئة</label>
                <input type="text" name="category" value="{{ request('category') }}" class="form-control" placeholder="الفئة">
            </div>
            <div class="col-md-2 mb-2">
                <button class="btn btn-primary"><x-svg-icon name="search" :size="14" /> بحث</button>
            </div>
        </form>
    </div></div>

    <div class="ds-card"><div class="ds-card-body">
        @if($sites->isEmpty())
            <div class="ds-empty">
                <div class="ds-empty-icon"><x-svg-icon name="globe2" :size="32" /></div>
                <div class="ds-empty-title">لا توجد مواقع تعليمية بعد</div>
                <div class="ds-empty-desc">أضف موقعًا تعليميًا ليظهر للمستخدمين على شكل كارت.</div>
            </div>
        @else
        {{-- Standalone reorder form (not wrapping the table) so the per-row
             toggle/delete forms are never nested inside it. Order inputs bind to
             it via the HTML5 form="reorderForm" attribute. --}}
        <form method="POST" action="{{ route('admin.educational-sites.reorder') }}" id="reorderForm">@csrf</form>
        <div>
            <div class="table-responsive">
                <table class="table table-hover ds-table-zebra align-middle mb-0">
                    <thead class="ds-thead-gold">
                        <tr>
                            <th style="width:90px">الترتيب</th>
                            <th>الشعار</th>
                            <th>اسم الموقع</th>
                            <th>الفئة</th>
                            <th>الرابط</th>
                            <th>تبويب جديد</th>
                            <th>الحالة</th>
                            <th style="width:170px">التحكم</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sites as $site)
                        <tr>
                            <td>
                                @if(auth()->user()->canDo('educational_sites.reorder'))
                                    <input type="number" form="reorderForm" name="order[{{ $site->id }}]" value="{{ $site->sort_order }}" min="0" class="form-control form-control-sm" style="width:80px;">
                                @else
                                    <span class="ds-badge-navy">{{ $site->sort_order }}</span>
                                @endif
                            </td>
                            <td>
                                @if($site->logo_url)
                                    <img src="{{ $site->logo_url }}" alt="" style="width:42px;height:42px;object-fit:contain;border-radius:8px;">
                                @else
                                    <span class="text-muted"><x-svg-icon name="image" :size="22" /></span>
                                @endif
                            </td>
                            <td>
                                <div class="font-weight-bold">{{ $site->name_ar ?: $site->name_en }}</div>
                                @if($site->name_ar && $site->name_en)<small class="text-muted">{{ $site->name_en }}</small>@endif
                                @if($site->school_id === null)<span class="ds-badge-gold ml-1">عام</span>@endif
                            </td>
                            <td>{{ $site->category ?: '—' }}</td>
                            <td><a href="{{ $site->url }}" target="_blank" rel="noopener" dir="ltr">{{ \Illuminate\Support\Str::limit($site->url, 32) }}</a></td>
                            <td>{!! $site->opens_new_tab ? '<span class="ds-badge-info">نعم</span>' : '<span class="ds-badge-navy">لا</span>' !!}</td>
                            <td>
                                @if($site->is_active)<span class="ds-badge-success">مفعّل</span>@else<span class="ds-badge-danger">معطّل</span>@endif
                            </td>
                            <td>
                                @if(auth()->user()->canDo('educational_sites.toggle_active'))
                                <form method="POST" action="{{ route('admin.educational-sites.toggle', $site->id) }}" class="d-inline">
                                    @csrf
                                    <button class="ds-action-btn" title="{{ $site->is_active ? 'تعطيل' : 'تفعيل' }}">
                                        <x-svg-icon name="{{ $site->is_active ? 'toggle-on' : 'toggle-off' }}" :size="16" />
                                    </button>
                                </form>
                                @endif
                                @if(auth()->user()->canDo('educational_sites.edit'))
                                <a href="{{ route('admin.educational-sites.edit', $site->id) }}" class="ds-action-btn" title="تعديل"><x-svg-icon name="pencil" :size="15" /></a>
                                @endif
                                @if(auth()->user()->canDo('educational_sites.delete'))
                                <form method="POST" action="{{ route('admin.educational-sites.destroy', $site->id) }}" class="d-inline" onsubmit="return confirm('حذف الموقع؟');">
                                    @csrf @method('DELETE')
                                    <button class="ds-action-btn ds-action-danger" title="حذف"><x-svg-icon name="trash" :size="15" /></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(auth()->user()->canDo('educational_sites.reorder'))
            <div class="mt-3 p-2" style="background:#faf7ef;border-radius:8px;">
                <small class="text-muted d-block mb-2">عدّل رقم الترتيب في عمود "الترتيب" لكل موقع، ثم احفظ. الأصغر يظهر أولًا.</small>
                <button type="submit" form="reorderForm" class="btn btn-outline-secondary btn-sm">
                    <x-svg-icon name="arrow-down-up" :size="14" /> حفظ الترتيب
                </button>
            </div>
            @endif
        </div>
        @endif
    </div></div>
</div>
@endsection
