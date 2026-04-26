@extends('layouts.app')

@section('title', __('schools.general_settings'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            @lang('schools.general_settings') — {{ app()->getLocale() === 'en' ? ($school->name_en ?: $school->name_ar) : ($school->name_ar ?: $school->name) }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.index') }}">@lang('schools.title')</a></li>
                <li class="breadcrumb-item active">@lang('schools.general_settings')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <form action="{{ route('admin.schools.settings.update', $school) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('schools.settings_school_info')</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('schools.name')</label>
                        <input type="text" class="form-control" disabled value="{{ $school->name_ar }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input type="hidden" name="info[school_active]" value="0">
                            <input type="checkbox" class="form-check-input" id="school_active" name="info[school_active]" value="1" @checked(($settings['info']['school_active'] ?? $school->is_active))>
                            <label class="form-check-label" for="school_active">@lang('schools.school_active')</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('common.phone')</label>
                        <input type="text" class="form-control" name="info[phone]" value="{{ $settings['info']['phone'] ?? $school->phone }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('schools.fax')</label>
                        <input type="text" class="form-control" name="info[fax]" value="{{ $settings['info']['fax'] ?? $school->fax }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('common.email')</label>
                        <input type="email" class="form-control" name="info[email]" value="{{ $settings['info']['email'] ?? $school->email }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('schools.website')</label>
                        <input type="url" class="form-control" name="info[website]" value="{{ $settings['info']['website'] ?? $school->website }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('schools.social_accounts')</h5></div>
            <div class="card-body">
                <div class="row">
                    @foreach(['facebook','twitter','instagram','linkedin'] as $net)
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ ucfirst($net) }}</label>
                            <input type="url" class="form-control" name="social[{{ $net }}]" value="{{ $settings['social'][$net] ?? $school->{$net} }}">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('schools.settings_attendance')</h5></div>
            <div class="card-body">
                <div class="form-check mb-2">
                    <input type="hidden" name="attendance[auto_from_biometric]" value="0">
                    <input type="checkbox" class="form-check-input" id="auto_from_biometric" name="attendance[auto_from_biometric]" value="1" @checked($settings['attendance']['auto_from_biometric'] ?? false)>
                    <label class="form-check-label" for="auto_from_biometric">@lang('schools.attendance_auto_biometric')</label>
                </div>
                <div class="form-check mb-2">
                    <input type="hidden" name="attendance[auto_notify_parents]" value="0">
                    <input type="checkbox" class="form-check-input" id="auto_notify_parents" name="attendance[auto_notify_parents]" value="1" @checked($settings['attendance']['auto_notify_parents'] ?? false)>
                    <label class="form-check-label" for="auto_notify_parents">@lang('schools.attendance_auto_notify')</label>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('schools.settings_messages')</h5></div>
            <div class="card-body">
                <div class="form-check mb-2">
                    <input type="hidden" name="messages[supervisors_can_view]" value="0">
                    <input type="checkbox" class="form-check-input" id="supervisors_can_view" name="messages[supervisors_can_view]" value="1" @checked($settings['messages']['supervisors_can_view'] ?? false)>
                    <label class="form-check-label" for="supervisors_can_view">@lang('schools.messages_supervisors_view')</label>
                </div>
                <div class="form-check mb-2">
                    <input type="hidden" name="messages[students_can_message_classmates]" value="0">
                    <input type="checkbox" class="form-check-input" id="students_can_message_classmates" name="messages[students_can_message_classmates]" value="1" @checked($settings['messages']['students_can_message_classmates'] ?? false)>
                    <label class="form-check-label" for="students_can_message_classmates">@lang('schools.messages_students_to_classmates')</label>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('schools.settings_virtual_classes')</h5></div>
            <div class="card-body">
                <label class="form-label">@lang('schools.virtual_platform')</label>
                <select class="form-control select2" name="virtual_classes[platform]">
                    @foreach(['zoom' => 'Zoom', 'teams' => 'Microsoft Teams', 'external' => __('schools.virtual_external_link')] as $k => $label)
                        <option value="{{ $k }}" @selected(($settings['virtual_classes']['platform'] ?? 'zoom') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
                <small class="text-muted d-block mt-2">@lang('schools.virtual_zoom_note')</small>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('schools.settings_whatsapp')</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('schools.whatsapp_host')</label>
                        <input type="text" class="form-control" name="whatsapp[host]" value="{{ $settings['whatsapp']['host'] ?? '' }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('schools.whatsapp_token')</label>
                        <input type="text" class="form-control" name="whatsapp[token]" value="{{ $settings['whatsapp']['token'] ?? '' }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 mb-4">
            <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('common.save')</button>
            <a href="{{ route('admin.schools.index') }}" class="btn btn-secondary"><i class="la la-times"></i> @lang('common.cancel')</a>
        </div>
    </form>
</div>
@endsection
