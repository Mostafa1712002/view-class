@extends('layouts.app')
@section('body_class','theme-light')
@section('title','مواقع تعليمية')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-7 mb-2">
        <h2 class="content-header-title mb-0">المواقع التعليمية</h2>
        <p class="text-muted mb-0">مجموعة من المنصات والمواقع التعليمية المختارة.</p>
    </div>
    @if(auth()->user()->canDo('educational_sites.create') || auth()->user()->isSchoolAdmin() || auth()->user()->isSuperAdmin())
    <div class="content-header-right col-md-5 text-md-right">
        @if(\Illuminate\Support\Facades\Route::has('admin.educational-sites.index') && (auth()->user()->isSuperAdmin() || auth()->user()->isSchoolAdmin()))
            <a href="{{ route('admin.educational-sites.index') }}" class="btn btn-outline-primary btn-sm"><x-svg-icon name="gear" :size="15" /> إدارة المواقع</a>
        @endif
    </div>
    @endif
</div>

<div class="content-body">
    @if($sites->isEmpty())
        <div class="ds-card"><div class="ds-card-body">
            <div class="ds-empty">
                <div class="ds-empty-icon"><x-svg-icon name="globe2" :size="32" /></div>
                <div class="ds-empty-title">لا توجد مواقع تعليمية متاحة حاليًا</div>
                <div class="ds-empty-desc">سيتم عرض المواقع التعليمية هنا فور إضافتها.</div>
            </div>
        </div></div>
    @else
        <div class="row">
            @foreach($sites as $site)
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="ds-card ds-hover-card h-100">
                    <div class="ds-card-body d-flex flex-column" style="min-height:230px;">
                        <div class="d-flex align-items-center mb-3">
                            <div class="mr-3" style="width:56px;height:56px;border-radius:12px;background:#faf7ef;display:flex;align-items:center;justify-content:center;overflow:hidden;flex:0 0 auto;">
                                @if($site->logo_url)
                                    <img src="{{ $site->logo_url }}" alt="{{ $site->display_name }}" style="width:100%;height:100%;object-fit:contain;">
                                @else
                                    <x-svg-icon name="globe2" :size="26" class="text-muted" />
                                @endif
                            </div>
                            <div class="overflow-hidden">
                                <h5 class="mb-0 text-truncate" style="font-weight:700;">{{ $site->display_name }}</h5>
                                @if($site->name_ar && $site->name_en && $site->name_ar !== $site->name_en)
                                    <small class="text-muted d-block text-truncate" dir="ltr">{{ $site->name_en }}</small>
                                @endif
                                @if($site->category)<span class="ds-badge-gold mt-1 d-inline-block">{{ $site->category }}</span>@endif
                            </div>
                        </div>

                        @if($site->display_description)
                            <p class="text-muted flex-grow-1" style="font-size:.9rem;">{{ \Illuminate\Support\Str::limit($site->display_description, 140) }}</p>
                        @else
                            <div class="flex-grow-1"></div>
                        @endif

                        @php $siteUrl = \Illuminate\Support\Str::startsWith(strtolower((string) $site->url), ['http://','https://']) ? $site->url : '#'; @endphp
                        <a href="{{ $siteUrl }}"
                           class="btn btn-primary btn-block mt-2"
                           rel="noopener noreferrer"
                           @if($site->opens_new_tab) target="_blank" @endif>
                            <x-svg-icon name="box-arrow-up-right" :size="15" /> زيارة الموقع
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
