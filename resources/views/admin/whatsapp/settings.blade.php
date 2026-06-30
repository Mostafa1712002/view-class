@extends('layouts.admin')

@section('title', 'إعدادات واتساب')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">إعدادات إشعارات واتساب</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item">رسائل الجوال</li>
                <li class="breadcrumb-item active">إعدادات واتساب</li>
            </ol>
        </div>
    </div>
</div>
<div class="container-fluid">
    <div class="d-flex justify-content-end align-items-center mb-4">
        <a href="{{ route('admin.whatsapp.logs') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-journal-text me-1"></i>سجل الرسائل
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Super-admin: show list of schools --}}
    @if(isset($schools) && !isset($school))
        <div class="card">
            <div class="card-header"><h5 class="mb-0">المدارس وإعدادات واتساب</h5></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>المدرسة</th>
                            <th>الرقم المُرسِل</th>
                            <th>المزود</th>
                            <th class="text-center">مفعّل</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($schools as $s)
                        <tr>
                            <td>{{ $s->name }}</td>
                            <td>{{ $s->whatsappSetting?->whatsapp_number ?? '—' }}</td>
                            <td>{{ $s->whatsappSetting?->provider ?? 'log' }}</td>
                            <td class="text-center">
                                @if($s->whatsappSetting?->is_enabled)
                                    <span class="badge bg-success">مفعّل</span>
                                @else
                                    <span class="badge bg-secondary">معطّل</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.whatsapp.edit', $s) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil me-1"></i>تعديل
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($schools->hasPages())
                <div class="card-footer">{{ $schools->links() }}</div>
            @endif
        </div>
    @else
    {{-- Edit form for specific school --}}
        @php $s = $school; @endphp
        <form method="POST" action="{{ route('admin.whatsapp.update', $s) }}">
            @csrf
            @method('PUT')

            {{-- Connection --}}
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">اتصال واتساب</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">رقم واتساب المدرسة</label>
                            <input type="text" name="whatsapp_number" class="form-control @error('whatsapp_number') is-invalid @enderror"
                                   value="{{ old('whatsapp_number', $setting->whatsapp_number) }}"
                                   placeholder="مثال: 966500000000">
                            @error('whatsapp_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">المزود</label>
                            <select name="provider" class="form-select @error('provider') is-invalid @enderror">
                                <option value="log" {{ old('provider', $setting->provider) === 'log' ? 'selected' : '' }}>Log فقط (تطوير)</option>
                                <option value="http" {{ old('provider', $setting->provider) === 'http' ? 'selected' : '' }}>HTTP (مزود خارجي)</option>
                            </select>
                            @error('provider')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">تفعيل الإشعارات</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_enabled" id="is_enabled" value="1"
                                       {{ old('is_enabled', $setting->is_enabled) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_enabled">إرسال إشعارات واتساب</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">رمز الوصول API Token</label>
                            <input type="password" name="api_token" class="form-control @error('api_token') is-invalid @enderror"
                                   placeholder="{{ $setting->api_token ? '(لم يتغير)' : 'أدخل الرمز' }}">
                            @error('api_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">رابط API (API URL)</label>
                            <input type="url" name="api_url" class="form-control @error('api_url') is-invalid @enderror"
                                   value="{{ old('api_url', $setting->api_url) }}"
                                   placeholder="https://api.provider.com/send">
                            @error('api_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Toggles --}}
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">أحداث الإرسال</h5></div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach([
                            'send_on_day_absence'    => 'غياب يومي',
                            'send_on_period_absence' => 'غياب حصة',
                            'send_on_late'           => 'تأخر',
                            'send_on_edit'           => 'تعديل السجل',
                            'send_on_excuse_accepted' => 'قبول العذر',
                            'send_on_excuse_rejected' => 'رفض العذر',
                        ] as $field => $label)
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="{{ $field }}" id="{{ $field }}" value="1"
                                       {{ old($field, $setting->{$field}) ? 'checked' : '' }}>
                                <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Templates --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">قوالب الرسائل</h5>
                    <small class="text-muted">
                        المتغيرات المتاحة:
                        @foreach(\App\Modules\Whatsapp\Models\SchoolWhatsappSetting::PLACEHOLDERS as $ph => $desc)
                            <code>{{ $ph }}</code> ({{ $desc }})
                        @endforeach
                    </small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach([
                            'template_absence'        => 'رسالة الغياب',
                            'template_late'           => 'رسالة التأخر',
                            'template_excuse_accepted' => 'رسالة قبول العذر',
                            'template_excuse_rejected' => 'رسالة رفض العذر',
                        ] as $field => $label)
                        <div class="col-md-6">
                            <label class="form-label">{{ $label }}</label>
                            <textarea name="{{ $field }}" rows="3"
                                      class="form-control @error($field) is-invalid @enderror"
                                      placeholder="اتركها فارغة للنص الافتراضي">{{ old($field, $setting->{$field}) }}</textarea>
                            @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>حفظ الإعدادات
                </button>
                <a href="{{ route('admin.whatsapp.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </div>
        </form>
    @endif
</div>
@endsection
