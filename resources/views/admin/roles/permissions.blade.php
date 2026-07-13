@extends('layouts.app')

@section('title', __('roles.title') . ' — ' . $role->name)
@section('body_class', 'theme-light')

@php
    $isSuperAdmin = $role->slug === 'super-admin';
@endphp

@push('styles')
<style>
/* ===== Role Permission Matrix (cloned from the job-title matrix) ===== */
.jtp-header { margin-bottom: 1.25rem; }
.jtp-header h2 { font-size: 1.4rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: .5rem; }
.jtp-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }

.jtp-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
    box-shadow: 0 1px 2px rgba(15,23,42,.04); margin-bottom: 1.5rem; }
.jtp-card .card-header { background: #f8fafc; border-bottom: 1px solid #e5e7eb; border-radius: 14px 14px 0 0;
    padding: .9rem 1.1rem; display: flex; align-items: center; justify-content: space-between; }
.jtp-card .card-header h5 { margin: 0; font-size: 1rem; font-weight: 700; color: #0f172a;
    display: flex; align-items: center; gap: .5rem; }
.jtp-card .card-header h5 i { color: #C9A227; }

.jtp-alert { display: flex; align-items: center; gap: .75rem;
    background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;
    padding: .75rem 1rem; margin-bottom: 1rem; color: #166534; font-size: .88rem; }
.jtp-alert.err { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
.jtp-alert.info { background: #fefce8; border-color: #fde68a; color: #92400e; }

.jtp-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
.jtp-table thead th { background: #f1f5f9; color: #475569; font-weight: 600; font-size: .78rem;
    text-transform: uppercase; letter-spacing: .5px; padding: .6rem 1rem; border-bottom: 2px solid #e2e8f0; }
.jtp-table thead th:first-child { width: 220px; text-align: right; }
.jtp-table tbody tr { transition: background .12s ease; }
.jtp-table tbody tr:hover { background: #fafbfc; }
.jtp-table tbody td { padding: .6rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.jtp-table tbody td.module-name { font-weight: 600; color: #0f172a; font-size: .9rem; white-space: nowrap; }
.jtp-table tbody tr:nth-child(even) { background: #fafbfc; }

.action-pills { display: flex; flex-wrap: wrap; gap: .4rem .9rem; }
.action-pill { display: inline-flex; align-items: center; gap: .3rem; }
.action-pill input[type=checkbox] { width: 15px; height: 15px; accent-color: #C9A227; cursor: pointer; }
.action-pill label { cursor: pointer; color: #334155; font-size: .8rem; user-select: none; }

.jtp-save-bar { background: #fff; border-top: 1px solid #e5e7eb; border-radius: 0 0 14px 14px;
    padding: .75rem 1.1rem; display: flex; justify-content: flex-end; gap: .75rem; }

.btn-gold { background: linear-gradient(135deg, #C9A227, #a07d1b);
    color: #fff; border: none; padding: .45rem 1.4rem; border-radius: 8px;
    font-weight: 600; font-size: .9rem; cursor: pointer; }
.btn-gold:hover { opacity: .9; }
</style>
@endpush

@section('content')
<div class="content-header jtp-header">
    <h2><i class="la la-shield-alt"></i> @lang('roles.permissions_of') {{ $role->name }}</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('roles.home')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">@lang('roles.title')</a></li>
        <li class="breadcrumb-item active">@lang('roles.permissions')</li>
    </ol>
</div>

<div class="content-body">

    @if(session('status'))
        <div class="jtp-alert"><i class="la la-check-circle"></i><span>{{ session('status') }}</span></div>
    @endif

    @if($errors->any())
        <div class="jtp-alert err"><i class="la la-exclamation-triangle"></i>
            <div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        </div>
    @endif

    @if($isSuperAdmin)
    <div class="jtp-alert info">
        <i class="la la-lock"></i>
        <span>@lang('roles.super_admin_locked')</span>
    </div>
    @endif

    <form action="{{ route('admin.roles.permissions.update', $role) }}" method="POST" id="perm-form">
        @csrf
        @method('PUT')

        @php
            // Arabic labels for action slugs — the matrix shows Arabic only.
            $actionLabels = [
                'view'               => 'عرض',
                'create'             => 'إضافة',
                'edit'               => 'تعديل',
                'delete'             => 'حذف',
                'archive'            => 'أرشفة',
                'approve'            => 'اعتماد',
                'reject'             => 'رفض',
                'export'             => 'تصدير',
                'import'             => 'استيراد',
                'print'             => 'طباعة',
                'view_details'       => 'عرض التفاصيل',
                'manage_permissions' => 'إدارة الصلاحيات',
                'send_notifications' => 'إرسال إشعارات',
                'send_whatsapp'      => 'إرسال واتساب',
                'login_as_user'      => 'الدخول للإطلاع',
                'publish'            => 'نشر',
                'read_log'           => 'سجل القراءة',
                'create_event'       => 'إضافة حدث',
                'edit_event'         => 'تعديل حدث',
                'delete_event'       => 'حذف حدث',
                'start'              => 'بدء',
                'join'               => 'انضمام',
                'view_attendance'    => 'عرض الحضور',
                'recalc_attendance'  => 'إعادة احتساب الحضور',
                'clear_cache'        => 'مسح الذاكرة المؤقتة',
                'toggle_comments'    => 'تفعيل/إيقاف التعليقات',
                'send'               => 'إرسال',
                'draft'              => 'مسودة',
                'send_excel'         => 'إرسال من Excel',
                'templates'          => 'القوالب',
                'reports'            => 'التقارير',
                'sender_name'        => 'اسم المرسل',
                'credit'             => 'الرصيد',
                'manage'             => 'إدارة',
                'record_present'     => 'تسجيل حضور',
                'record_absent'      => 'تسجيل غياب',
                'record_late'        => 'تسجيل تأخير',
                'record_excuse'      => 'تسجيل استئذان',
                'record_period'      => 'تسجيل حضور حصة',
                'add_excuse'         => 'إضافة عذر',
                'add_note'           => 'إضافة ملاحظة',
                'bulk_present'       => 'تعيين جماعي للحضور',
                'bulk_absent'        => 'تعيين جماعي للغياب',
                'bulk_late'          => 'تعيين جماعي للتأخير',
                'notify_parent'      => 'إشعار ولي الأمر',
                'view_reports'       => 'عرض التقارير',
                'send_message'       => 'إرسال رسالة',
                'create_card'        => 'إنشاء بطاقة',
                'print_card'         => 'طباعة بطاقة',
                'export_cards'       => 'تصدير البطاقات',
                'scan'               => 'تشغيل الماسح',
                'view_log'           => 'عرض السجل',
                'close_day'          => 'إغلاق اليوم',
                'group_create'       => 'إنشاء مجموعة',
                'group_edit'         => 'تعديل مجموعة',
                'group_delete'       => 'حذف مجموعة',
                'link_students'      => 'ربط الطلاب',
                'link_devices'       => 'ربط الأجهزة',
                'template_create'    => 'إضافة قالب',
                'template_edit'      => 'تعديل قالب',
                'template_delete'    => 'حذف قالب',
                'issue'              => 'إصدار',
                'upload_file'        => 'رفع ملف',
                'preview'            => 'معاينة',
                'copy_link'          => 'نسخ الرابط',
                'copy_company_link'  => 'نسخ رابط الشركة',
                'reply'              => 'رد',
                'assign'             => 'تحويل',
                'change_status'      => 'تغيير الحالة',
                'close'              => 'إغلاق',
                'view_attachments'   => 'عرض المرفقات',
                'schedule'           => 'تحديد موعد',
                'edit_school_settings' => 'إعدادات المدرسة',
                'edit_settings'      => 'إعدادات التسجيل',
                'edit_info'          => 'معلومات التسجيل',
                'convert_to_student' => 'تحويل إلى طالب',
                'reorder'            => 'إعادة ترتيب',
                'toggle_active'      => 'تفعيل/تعطيل',
            ];
        @endphp

        <div class="jtp-card">
            <div class="card-header">
                <h5><i class="la la-list-check"></i> @lang('roles.matrix')</h5>
                @unless($isSuperAdmin)
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAll()">
                    <i class="la la-check-square"></i> @lang('roles.toggle_all')
                </button>
                @endunless
            </div>
            <div class="table-responsive">
                <table class="jtp-table">
                    <thead>
                        <tr>
                            <th>@lang('roles.module')</th>
                            <th>@lang('roles.available_actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($modules as $group => $def)
                        @php
                            $moduleHasAny = false;
                            foreach ($def['actions'] as $action) {
                                if ($permModels->has("{$group}.{$action}")) { $moduleHasAny = true; break; }
                            }
                        @endphp
                        @if($moduleHasAny)
                        <tr>
                            <td class="module-name">{{ $def['label'] }}</td>
                            <td>
                                <div class="action-pills">
                                @foreach($def['actions'] as $action)
                                    @php
                                        $slug = "{$group}.{$action}";
                                        $perm = $permModels->get($slug);
                                        $isChecked = $isSuperAdmin || $configured->has($slug);
                                    @endphp
                                    @if($perm)
                                    <div class="action-pill">
                                        <input type="checkbox"
                                               id="perm_{{ $perm->id }}"
                                               name="permissions[]"
                                               value="{{ $slug }}"
                                               {{ $isChecked ? 'checked' : '' }}
                                               {{ $isSuperAdmin ? 'disabled' : '' }}
                                               class="perm-cb" />
                                        <label for="perm_{{ $perm->id }}">{{ $actionLabels[$action] ?? $action }}</label>
                                    </div>
                                    @endif
                                @endforeach
                                </div>
                            </td>
                        </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="jtp-save-bar">
                <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary btn-sm">
                    @lang('roles.cancel')
                </a>
                @unless($isSuperAdmin)
                <button type="submit" class="btn-gold">
                    <i class="la la-save"></i> @lang('roles.save')
                </button>
                @endunless
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function toggleAll() {
    const boxes = document.querySelectorAll('.perm-cb:not([disabled])');
    const anyUnchecked = Array.from(boxes).some(b => !b.checked);
    boxes.forEach(b => { b.checked = anyUnchecked; });
}
</script>
@endpush
