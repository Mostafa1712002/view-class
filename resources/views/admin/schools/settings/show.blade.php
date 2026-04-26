@extends('layouts.app')

@section('title', __('schools.general_settings'))

@php
    $editableFields = ['username','password','name','avatar','phone','email'];
    $userRoles = ['student','parent','teacher','other'];
    $get = fn($path, $default = null) => data_get($settings, $path, $default);
@endphp

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

        {{-- 1. School info --}}
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
                            <input type="checkbox" class="form-check-input" id="school_active" name="info[school_active]" value="1" @checked($get('info.school_active', $school->is_active))>
                            <label class="form-check-label" for="school_active">@lang('schools.school_active')</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('common.phone')</label>
                        <input type="text" class="form-control" name="info[phone]" value="{{ $get('info.phone', $school->phone) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('schools.fax')</label>
                        <input type="text" class="form-control" name="info[fax]" value="{{ $get('info.fax', $school->fax) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('common.email')</label>
                        <input type="email" class="form-control" name="info[email]" value="{{ $get('info.email', $school->email) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('schools.website')</label>
                        <input type="url" class="form-control" name="info[website]" value="{{ $get('info.website', $school->website) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Social --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('schools.social_accounts')</h5></div>
            <div class="card-body">
                <div class="row">
                    @foreach(['facebook','twitter','instagram','linkedin'] as $net)
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ ucfirst($net) }}</label>
                            <input type="url" class="form-control" name="social[{{ $net }}]" value="{{ $get('social.'.$net, $school->{$net}) }}">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 3. User-edit permissions per role --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('schools.settings_user_edit_perms')</h5></div>
            <div class="card-body">
                <p class="text-muted">@lang('schools.settings_user_edit_perms_hint')</p>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>@lang('common.role')</th>
                                @foreach($editableFields as $field)
                                    <th class="text-center">@lang('schools.user_field_'.$field)</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($userRoles as $r)
                                <tr>
                                    <td><strong>@lang('schools.user_role_'.$r)</strong></td>
                                    @foreach($editableFields as $field)
                                        <td class="text-center">
                                            <input type="hidden" name="user_edit[{{ $r }}][{{ $field }}]" value="0">
                                            <input type="checkbox" class="form-check-input" name="user_edit[{{ $r }}][{{ $field }}]" value="1"
                                                @checked($get("user_edit.$r.$field", false))>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="form-check mt-2">
                    <input type="hidden" name="classes[allow_overlapping_periods]" value="0">
                    <input type="checkbox" class="form-check-input" id="allow_overlapping_periods" name="classes[allow_overlapping_periods]" value="1" @checked($get('classes.allow_overlapping_periods', false))>
                    <label class="form-check-label" for="allow_overlapping_periods">@lang('schools.classes_allow_overlap')</label>
                </div>
            </div>
        </div>

        {{-- 4. Weekly plan --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('schools.settings_weekly_plan')</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">@lang('schools.week_start_day')</label>
                        <select class="form-control" name="weekly_plan[week_start]">
                            @foreach(['saturday','sunday','monday'] as $d)
                                <option value="{{ $d }}" @selected($get('weekly_plan.week_start', 'sunday') === $d)>@lang('dashboard.day_'.substr($d,0,3))</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label">@lang('schools.working_days')</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(['sat','sun','mon','tue','wed','thu','fri'] as $d)
                                <label class="form-check form-check-inline">
                                    <input type="hidden" name="weekly_plan[working_days][{{ $d }}]" value="0">
                                    <input type="checkbox" class="form-check-input" name="weekly_plan[working_days][{{ $d }}]" value="1" @checked($get("weekly_plan.working_days.$d", in_array($d, ['sun','mon','tue','wed','thu'])))>
                                    <span class="form-check-label">@lang('dashboard.day_'.$d)</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">@lang('schools.calendar_type')</label>
                        <select class="form-control" name="weekly_plan[calendar]">
                            <option value="gregorian" @selected($get('weekly_plan.calendar', 'gregorian') === 'gregorian')>@lang('schools.calendar_gregorian')</option>
                            <option value="hijri" @selected($get('weekly_plan.calendar') === 'hijri')>@lang('schools.calendar_hijri')</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">@lang('schools.target_homework_per_teacher')</label>
                        <input type="number" min="0" class="form-control" name="weekly_plan[homework_target]" value="{{ $get('weekly_plan.homework_target', 0) }}">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">@lang('schools.weekly_plan_print_header')</label>
                        <textarea class="form-control" rows="2" name="weekly_plan[print_header]">{{ $get('weekly_plan.print_header', '') }}</textarea>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">@lang('schools.weekly_plan_print_footer')</label>
                        <textarea class="form-control" rows="2" name="weekly_plan[print_footer]">{{ $get('weekly_plan.print_footer', '') }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="hidden" name="weekly_plan[lock_student_reassign]" value="0">
                            <input type="checkbox" class="form-check-input" id="lock_student_reassign" name="weekly_plan[lock_student_reassign]" value="1" @checked($get('weekly_plan.lock_student_reassign', false))>
                            <label class="form-check-label" for="lock_student_reassign">@lang('schools.lock_student_reassign')</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 5. Class report header/footer --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('schools.settings_class_reports')</h5></div>
            <div class="card-body">
                <div class="form-check">
                    <input type="hidden" name="classes[show_report_header_footer]" value="0">
                    <input type="checkbox" class="form-check-input" id="show_report_hf" name="classes[show_report_header_footer]" value="1" @checked($get('classes.show_report_header_footer', false))>
                    <label class="form-check-label" for="show_report_hf">@lang('schools.classes_show_header_footer')</label>
                </div>
            </div>
        </div>

        {{-- 6. Attendance --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('schools.settings_attendance')</h5></div>
            <div class="card-body">
                <div class="form-check mb-2">
                    <input type="hidden" name="attendance[auto_from_biometric]" value="0">
                    <input type="checkbox" class="form-check-input" id="auto_from_biometric" name="attendance[auto_from_biometric]" value="1" @checked($get('attendance.auto_from_biometric', false))>
                    <label class="form-check-label" for="auto_from_biometric">@lang('schools.attendance_auto_biometric')</label>
                </div>
                <div class="form-check mb-2">
                    <input type="hidden" name="attendance[auto_notify_parents]" value="0">
                    <input type="checkbox" class="form-check-input" id="auto_notify_parents" name="attendance[auto_notify_parents]" value="1" @checked($get('attendance.auto_notify_parents', false))>
                    <label class="form-check-label" for="auto_notify_parents">@lang('schools.attendance_auto_notify')</label>
                </div>
            </div>
        </div>

        {{-- 7. Discussion rooms --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('schools.settings_discussion_rooms')</h5></div>
            <div class="card-body">
                <div class="form-check mb-2">
                    <input type="hidden" name="discussion[teacher_can_create]" value="0">
                    <input type="checkbox" class="form-check-input" id="dis_teacher" name="discussion[teacher_can_create]" value="1" @checked($get('discussion.teacher_can_create', false))>
                    <label class="form-check-label" for="dis_teacher">@lang('schools.discussion_teacher_create')</label>
                </div>
                <div class="form-check mb-2">
                    <input type="hidden" name="discussion[student_can_post]" value="0">
                    <input type="checkbox" class="form-check-input" id="dis_student" name="discussion[student_can_post]" value="1" @checked($get('discussion.student_can_post', false))>
                    <label class="form-check-label" for="dis_student">@lang('schools.discussion_student_post')</label>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">@lang('schools.discussion_max_replies')</label>
                        <input type="number" min="0" class="form-control" name="discussion[max_replies_per_student]" value="{{ $get('discussion.max_replies_per_student', 0) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- 8. Grade reports --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('schools.settings_grade_reports')</h5></div>
            <div class="card-body">
                <label class="form-label">@lang('schools.gpa_system')</label>
                <select class="form-control" name="grade_reports[gpa_system]">
                    @foreach(['100','5','4'] as $g)
                        <option value="{{ $g }}" @selected($get('grade_reports.gpa_system', '100') === $g)>@lang('schools.gpa_'.$g)</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- 9. Messages --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('schools.settings_messages')</h5></div>
            <div class="card-body">
                <div class="form-check mb-2">
                    <input type="hidden" name="messages[supervisors_can_view]" value="0">
                    <input type="checkbox" class="form-check-input" id="supervisors_can_view" name="messages[supervisors_can_view]" value="1" @checked($get('messages.supervisors_can_view', false))>
                    <label class="form-check-label" for="supervisors_can_view">@lang('schools.messages_supervisors_view')</label>
                </div>
                <div class="form-check mb-2">
                    <input type="hidden" name="messages[students_can_message_classmates]" value="0">
                    <input type="checkbox" class="form-check-input" id="students_can_message_classmates" name="messages[students_can_message_classmates]" value="1" @checked($get('messages.students_can_message_classmates', false))>
                    <label class="form-check-label" for="students_can_message_classmates">@lang('schools.messages_students_to_classmates')</label>
                </div>
            </div>
        </div>

        {{-- 10. Virtual classes --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('schools.settings_virtual_classes')</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('schools.virtual_platform')</label>
                        <select class="form-control" name="virtual_classes[platform]">
                            @foreach(['zoom' => 'Zoom', 'teams' => 'Microsoft Teams', 'external' => __('schools.virtual_external_link')] as $k => $label)
                                <option value="{{ $k }}" @selected($get('virtual_classes.platform', 'zoom') === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('schools.virtual_external_url')</label>
                        <input type="url" class="form-control" name="virtual_classes[external_url]" value="{{ $get('virtual_classes.external_url', '') }}">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="hidden" name="virtual_classes[student_can_enter_directly]" value="0">
                            <input type="checkbox" class="form-check-input" id="vc_direct" name="virtual_classes[student_can_enter_directly]" value="1" @checked($get('virtual_classes.student_can_enter_directly', false))>
                            <label class="form-check-label" for="vc_direct">@lang('schools.virtual_student_direct')</label>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <small class="text-muted">@lang('schools.virtual_zoom_note')</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- 11. WhatsApp --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">@lang('schools.settings_whatsapp')</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('schools.whatsapp_host')</label>
                        <input type="text" class="form-control" name="whatsapp[host]" value="{{ $get('whatsapp.host', '') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('schools.whatsapp_token')</label>
                        <input type="text" class="form-control" name="whatsapp[token]" value="{{ $get('whatsapp.token', '') }}">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="hidden" name="whatsapp[enabled]" value="0">
                            <input type="checkbox" class="form-check-input" id="wa_enabled" name="whatsapp[enabled]" value="1" @checked($get('whatsapp.enabled', false))>
                            <label class="form-check-label" for="wa_enabled">@lang('schools.whatsapp_enabled')</label>
                        </div>
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
