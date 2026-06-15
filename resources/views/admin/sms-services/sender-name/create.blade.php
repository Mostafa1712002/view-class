@extends('layouts.app')

@section('title', 'طلب اسم مرسل')
@section('page-title', 'طلب اسم مرسل')
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">طلب اسم مرسل</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item">الرسائل القصيرة</li>
                <li class="breadcrumb-item"><a href="{{ route('admin.sms.sender-name.index') }}">اسم المرسل</a></li>
                <li class="breadcrumb-item active">طلب اسم مرسل</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    {{-- Info steps --}}
    <div class="alert alert-info mb-3" role="alert">
        <h6 class="alert-heading mb-2"><x-svg-icon name="info-circle" :size="16" /> خطوات طلب اسم المرسل</h6>
        <ol class="mb-0 ps-3 small">
            <li class="mb-1">اختر نوع اسم المرسل (تنبيهات أو إعلاني).</li>
            <li class="mb-1">أدخل اسم المرسل باللغتين العربية والإنجليزية.</li>
            <li class="mb-1">ارفع المستندات المطلوبة (خطاب الطلب، نماذج المشغلين، السجل التجاري).</li>
            <li>اضغط «إرسال الطلب» لتقديمه للمراجعة، أو «حفظ كمسودة» لإكماله لاحقًا.</li>
        </ol>
    </div>

    <form method="POST" action="{{ route('admin.sms.sender-name.store') }}"
          enctype="multipart/form-data" id="sender-form">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">معلومات الاسم</h5></div>
                    <div class="card-body">

                        {{-- Kind --}}
                        <div class="mb-3">
                            <label class="form-label">نوع الاسم <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($kinds as $kindKey => $kindLabel)
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="kind"
                                           id="kind_{{ $kindKey }}" value="{{ $kindKey }}"
                                           {{ old('kind', 'alerts') === $kindKey ? 'checked' : '' }}>
                                    <label class="form-check-label" for="kind_{{ $kindKey }}">{{ $kindLabel }}</label>
                                </div>
                                @endforeach
                            </div>
                            @error('kind')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            <div class="form-text text-muted small">
                                تنبيهات: حتى 11 حرفًا | إعلاني: حتى 8 أحرف
                            </div>
                        </div>

                        {{-- name_ar --}}
                        <div class="mb-3">
                            <label class="form-label" for="name_ar">
                                الاسم بالعربي <span class="text-danger">*</span>
                                <span class="text-muted small">(عدد الأحرف: <b id="ar-len">0</b> / <b id="ar-max">11</b>)</span>
                            </label>
                            <input type="text" name="name_ar" id="name_ar" maxlength="11" required
                                   value="{{ old('name_ar') }}"
                                   class="form-control @error('name_ar') is-invalid @enderror"
                                   placeholder="مثال: المدرسة الأولى">
                            @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- name_en --}}
                        <div class="mb-3">
                            <label class="form-label" for="name_en">
                                الاسم بالإنجليزي <span class="text-danger">*</span>
                                <span class="text-muted small">(عدد الأحرف: <b id="en-len">0</b> / <b id="en-max">11</b>)</span>
                            </label>
                            <input type="text" name="name_en" id="name_en" maxlength="11" required
                                   value="{{ old('name_en') }}"
                                   class="form-control @error('name_en') is-invalid @enderror"
                                   placeholder="e.g. FirstSchool"
                                   dir="ltr">
                            @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">المستندات المطلوبة</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">خطاب الطلب <span class="text-danger">*</span></label>
                                <input type="file" name="letter" class="form-control @error('letter') is-invalid @enderror"
                                       accept=".pdf,.jpg,.jpeg,.png">
                                @error('letter')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">نموذج STC</label>
                                <input type="file" name="stc_form" class="form-control @error('stc_form') is-invalid @enderror"
                                       accept=".pdf,.jpg,.jpeg,.png">
                                @error('stc_form')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">نموذج Mobily</label>
                                <input type="file" name="mobily_form" class="form-control @error('mobily_form') is-invalid @enderror"
                                       accept=".pdf,.jpg,.jpeg,.png">
                                @error('mobily_form')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">نموذج ZAIN</label>
                                <input type="file" name="zain_form" class="form-control @error('zain_form') is-invalid @enderror"
                                       accept=".pdf,.jpg,.jpeg,.png">
                                @error('zain_form')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">السجل التجاري <span class="text-danger">*</span></label>
                                <input type="file" name="commercial_reg" class="form-control @error('commercial_reg') is-invalid @enderror"
                                       accept=".pdf,.jpg,.jpeg,.png">
                                @error('commercial_reg')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="form-text text-muted mt-2 small">المستندات المقبولة: PDF, JPG, PNG</div>
                    </div>
                </div>

                <div class="d-flex gap-2 mb-3">
                    <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">
                        <x-svg-icon name="save" :size="14" class="me-1" /> حفظ كمسودة
                    </button>
                    <button type="submit" name="action" value="submit" class="btn btn-primary">
                        <x-svg-icon name="send" :size="14" class="me-1" /> إرسال الطلب
                    </button>
                    <a href="{{ route('admin.sms.sender-name.index') }}" class="btn btn-outline-secondary ms-auto">عودة</a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">متطلبات الاسم</h6></div>
                    <div class="card-body small text-muted">
                        <p><strong>تنبيهات:</strong> حتى 11 حرفًا، للرسائل التشغيلية والتنبيهات.</p>
                        <p><strong>إعلاني:</strong> حتى 8 أحرف، للرسائل التسويقية.</p>
                        <p class="mb-0">يُنصح باستخدام الأحرف اللاتينية في اسم المرسل لضمان التوافق مع جميع المشغلين.</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var maxByKind = { alerts: 11, advertising: 8 };
    var arLen = document.getElementById('ar-len');
    var enLen = document.getElementById('en-len');
    var arMax = document.getElementById('ar-max');
    var enMax = document.getElementById('en-max');
    var nameAr = document.getElementById('name_ar');
    var nameEn = document.getElementById('name_en');
    var radios = document.querySelectorAll('input[name="kind"]');

    function getKind() {
        var checked = document.querySelector('input[name="kind"]:checked');
        return checked ? checked.value : 'alerts';
    }
    function updateMax() {
        var m = maxByKind[getKind()] || 11;
        nameAr.maxLength = m; nameEn.maxLength = m;
        arMax.textContent = m; enMax.textContent = m;
        arLen.textContent = nameAr.value.length;
        enLen.textContent = nameEn.value.length;
    }
    radios.forEach(function (r) { r.addEventListener('change', updateMax); });
    nameAr.addEventListener('input', function () { arLen.textContent = nameAr.value.length; });
    nameEn.addEventListener('input', function () { enLen.textContent = nameEn.value.length; });
    updateMax();
})();
</script>
@endpush
