@extends('layouts.app')

@section('title', __('schools.permissions'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            @lang('schools.permissions') — {{ app()->getLocale() === 'en' ? ($school->name_en ?: $school->name_ar) : ($school->name_ar ?: $school->name) }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.index') }}">@lang('schools.title')</a></li>
                <li class="breadcrumb-item active">@lang('schools.permissions')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <small class="text-muted">@lang('schools.permissions_autosave_hint')</small>
            <form action="{{ route('admin.schools.permissions.copy', $school) }}" method="POST" class="d-flex gap-2 align-items-center" onsubmit="return confirm(@json(__('schools.confirm_copy_permissions')))">
                @csrf
                <select name="source_school_id" class="form-control form-control-sm" required style="min-width:240px;">
                    <option value="">@lang('schools.copy_source_school')...</option>
                    @foreach($otherSchools as $os)
                        <option value="{{ $os->id }}">{{ $os->name_ar ?: $os->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-outline-secondary"><i class="la la-copy"></i> @lang('schools.copy_permissions')</button>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">@lang('schools.permission_roles')</h6></div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="role-list">
                        @forelse($roles as $role)
                            <button type="button" class="list-group-item list-group-item-action role-pick @if($loop->first) active @endif" data-role-id="{{ $role->id }}">
                                {{ $role->name }}
                            </button>
                        @empty
                            <div class="list-group-item text-muted">@lang('common.no_data')</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">@lang('schools.permission_main_functions')</h6></div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="group-list">
                        @forelse($groups as $g)
                            <button type="button" class="list-group-item list-group-item-action group-pick @if($loop->first) active @endif" data-group="{{ $g }}">{{ $g }}</button>
                        @empty
                            <div class="list-group-item text-muted">@lang('common.no_data')</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">@lang('schools.permission_sub_functions')</h6></div>
                <div class="card-body" id="sub-permissions">
                    <p class="text-muted" data-empty>@lang('schools.permissions_pick_role')</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name=_token]')?.value;
    const toggleUrl = @json(route('admin.schools.permissions.toggle', $school));
    const permissionsByGroup = @json($permissionsByGroup);
    const assignments = @json($assignments);
    const T = {
        pickRole: @json(__('schools.permissions_pick_role')),
        noData: @json(__('common.no_data')),
        opFailed: @json(__('common.operation_failed')),
    };

    const roleList = document.getElementById('role-list');
    const groupList = document.getElementById('group-list');
    const subBox = document.getElementById('sub-permissions');

    let currentRoleId = roleList.querySelector('.role-pick.active')?.dataset.roleId
        || roleList.querySelector('.role-pick')?.dataset.roleId;
    let currentGroup = groupList.querySelector('.group-pick.active')?.dataset.group
        || groupList.querySelector('.group-pick')?.dataset.group;

    function isEnabled(roleId, permissionId) {
        return (assignments[roleId] || []).includes(permissionId);
    }

    function renderSub() {
        subBox.replaceChildren();
        if (!currentRoleId || !currentGroup) {
            const p = document.createElement('p');
            p.className = 'text-muted';
            p.textContent = T.pickRole;
            subBox.appendChild(p);
            return;
        }
        const perms = permissionsByGroup[currentGroup] || [];
        if (!perms.length) {
            const p = document.createElement('p');
            p.className = 'text-muted';
            p.textContent = T.noData;
            subBox.appendChild(p);
            return;
        }
        for (const p of perms) {
            const wrap = document.createElement('div');
            wrap.className = 'form-check mb-2';
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'form-check-input perm-toggle';
            cb.dataset.permissionId = p.id;
            cb.id = 'perm-' + p.id;
            cb.checked = isEnabled(parseInt(currentRoleId), p.id);
            const lbl = document.createElement('label');
            lbl.className = 'form-check-label';
            lbl.htmlFor = cb.id;
            lbl.textContent = p.name;
            wrap.append(cb, lbl);
            subBox.appendChild(wrap);
        }
    }

    roleList.addEventListener('click', e => {
        const btn = e.target.closest('.role-pick');
        if (!btn) return;
        roleList.querySelectorAll('.role-pick').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentRoleId = btn.dataset.roleId;
        renderSub();
    });

    groupList.addEventListener('click', e => {
        const btn = e.target.closest('.group-pick');
        if (!btn) return;
        groupList.querySelectorAll('.group-pick').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentGroup = btn.dataset.group;
        renderSub();
    });

    subBox.addEventListener('change', async e => {
        const cb = e.target.closest('.perm-toggle');
        if (!cb) return;
        const permId = parseInt(cb.dataset.permissionId);
        const enabled = cb.checked;
        cb.disabled = true;
        try {
            const r = await fetch(toggleUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
                credentials: 'include',
                body: JSON.stringify({role_id: parseInt(currentRoleId), permission_id: permId, enabled}),
            });
            if (!r.ok) throw new Error(r.statusText);
            assignments[currentRoleId] = assignments[currentRoleId] || [];
            if (enabled && !assignments[currentRoleId].includes(permId)) {
                assignments[currentRoleId].push(permId);
            } else if (!enabled) {
                assignments[currentRoleId] = assignments[currentRoleId].filter(id => id !== permId);
            }
        } catch (err) {
            cb.checked = !enabled;
            alert(T.opFailed);
        } finally {
            cb.disabled = false;
        }
    });

    renderSub();
})();
</script>
@endsection
