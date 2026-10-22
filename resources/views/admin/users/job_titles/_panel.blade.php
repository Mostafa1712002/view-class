@php
    $isRtl   = app()->getLocale() === 'ar';
    $total   = $jobTitles->count();
    $global  = $jobTitles->whereNull('school_id')->count();
    $school  = $jobTitles->whereNotNull('school_id')->count();
    $active  = $jobTitles->where('is_active', true)->count();
@endphp
    @if(session('status'))
        <div class="jt-alert"><x-svg-icon name="check-circle" /><span>{{ session('status') }}</span></div>
    @endif
    @if($errors->any())
        <div class="jt-alert err">
            <x-svg-icon name="exclamation-triangle" />
            <div>
                @foreach($errors->all() as $e)
                    <div>{{ $e }}</div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- KPI strip --}}
    <div class="jt-kpis">
        <div class="jt-kpi">
            <div class="ico"><x-svg-icon name="tags" /></div>
            <div>
                <div class="num">{{ $total }}</div>
                <div class="lbl">@lang('users.job_titles')</div>
            </div>
        </div>
        <div class="jt-kpi">
            <div class="ico ico-green"><x-svg-icon name="check-circle" /></div>
            <div>
                <div class="num muted">{{ $active }}</div>
                <div class="lbl">@lang('users.jt_active')</div>
            </div>
        </div>
        <div class="jt-kpi">
            <div class="ico ico-blue"><x-svg-icon name="globe" /></div>
            <div>
                <div class="num muted">{{ $global }}</div>
                <div class="lbl">@lang('users.jt_global')</div>
            </div>
        </div>
        <div class="jt-kpi">
            <div class="ico ico-violet"><x-svg-icon name="building" /></div>
            <div>
                <div class="num muted">{{ $school }}</div>
                <div class="lbl">@lang('users.jt_school')</div>
            </div>
        </div>
    </div>

    <div class="row jt-surface">
        {{-- Table column --}}
        <div class="col-lg-8 col-12 mb-3 mb-lg-0">
            <div class="card">
                <div class="card-header">
                    <h5><x-svg-icon name="list-ul" /> @lang('users.job_titles')</h5>
                    <span class="count-pill">{{ $total }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table jt-table">
                            <thead>
                                <tr>
                                    <th>@lang('users.name')</th>
                                    <th>@lang('users.jt_slug')</th>
                                    <th>الدور</th>
                                    <th class="text-center">المستخدمون</th>
                                    <th>@lang('users.jt_active')</th>
                                    <th class="text-{{ $isRtl ? 'start' : 'end' }}">@lang('users.actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($jobTitles as $jt)
                                <tr>
                                    <td data-label="@lang('users.name')">
                                        <div class="jt-name-primary">{{ $isRtl ? $jt->name_ar : $jt->name_en }}</div>
                                        <div class="jt-name-secondary">{{ $isRtl ? $jt->name_en : $jt->name_ar }}</div>
                                        @if($jt->description)
                                            <div style="font-size:.77rem;color:#94a3b8;margin-top:.15rem;">{{ \Illuminate\Support\Str::limit($jt->description, 55) }}</div>
                                        @endif
                                    </td>
                                    <td data-label="@lang('users.jt_slug')">
                                        <span class="jt-slug-code">{{ $jt->slug }}</span>
                                    </td>
                                    <td data-label="الدور">
                                        @if($jt->role)
                                            <span class="jt-pill" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;">{{ $jt->role->name }}</span>
                                        @else
                                            <span style="color:#94a3b8;font-size:.8rem;">—</span>
                                        @endif
                                    </td>
                                    <td data-label="المستخدمون" class="text-center">
                                        <span class="jt-sort">{{ $jt->users_count ?? 0 }}</span>
                                    </td>
                                    <td data-label="@lang('users.jt_active')">
                                        @if($jt->is_active)
                                            <span class="jt-pill active"><span class="dot"></span>@lang('users.jt_active')</span>
                                        @else
                                            <span class="jt-pill inactive"><span class="dot"></span>—</span>
                                        @endif
                                        @if($jt->school_id === null)
                                            <span class="jt-pill scope-global">@lang('users.jt_global')</span>
                                        @else
                                            <span class="jt-pill scope-school">@lang('users.jt_school')</span>
                                        @endif
                                        @if($jt->permissions->isNotEmpty())
                                            <span class="jt-pill" style="background:#fef3c7;color:#92400e;border-color:#fde68a;font-size:.72rem;">
                                                {{ $jt->permissions->count() }} صلاحية
                                            </span>
                                        @endif
                                    </td>
                                    <td data-label="@lang('users.actions')" class="actions-cell text-{{ $isRtl ? 'start' : 'end' }}">
                                        <a href="{{ route('admin.users.job-titles.permissions.index', $jt->id) }}"
                                           class="jt-action-btn" title="إدارة الصلاحيات"
                                           style="color:#C9A227;text-decoration:none;font-size:.82rem;width:auto;padding:0 .6rem;gap:.3rem;white-space:nowrap;margin-bottom:.3rem;">
                                            <x-svg-icon name="shield-shaded" /> الصلاحيات
                                        </a>
                                        @if($jt->school_id !== null || auth()->user()->isSuperAdmin())
                                            <form action="{{ route('admin.users.job-titles.destroy', $jt->id) }}" method="POST" class="d-inline" onsubmit="return confirm('@lang('users.jt_delete_confirm')');">
                                                @csrf @method('DELETE')
                                                <button class="jt-action-btn" title="@lang('users.delete')"><x-svg-icon name="trash" /></button>
                                            </form>
                                        @else
                                            <button class="jt-action-btn is-disabled" disabled title="@lang('users.jt_global')"><x-svg-icon name="lock" /></button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="jt-empty">
                                            <x-svg-icon name="inbox" />
                                            <div class="lbl">@lang('users.no_results')</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Create form column --}}
        <div class="col-lg-4 col-12">
            <div class="card jt-form-card">
                <div class="card-header">
                    <h5><x-svg-icon name="plus-circle" /> @lang('users.jt_create_form')</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.users.job-titles.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">@lang('users.jt_slug') <span class="req">*</span>
                                <span class="hint">a-z 0-9 _ -</span>
                            </label>
                            <input type="text" name="slug" class="form-control" required pattern="[a-z0-9_-]+"
                                   placeholder="e.g. coordinator" value="{{ old('slug') }}" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">@lang('users.name_ar') <span class="req">*</span></label>
                            <input type="text" name="name_ar" class="form-control" required
                                   placeholder="@lang('users.name_ar')" value="{{ old('name_ar') }}" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">@lang('users.name') (EN) <span class="req">*</span></label>
                            <input type="text" name="name_en" class="form-control" required
                                   placeholder="@lang('users.name')" value="{{ old('name_en') }}" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">@lang('users.jt_sort_order')</label>
                            <input type="number" name="sort_order" class="form-control" min="0"
                                   value="{{ old('sort_order', 0) }}" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الوصف <span class="hint">(اختياري)</span></label>
                            <textarea name="description" class="form-control" rows="2"
                                      placeholder="وصف مختصر للمسمى الوظيفي"
                                      style="font-size:.88rem;">{{ old('description') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الدور الرئيسي <span class="hint">(اختياري)</span></label>
                            <select name="role_id" class="form-control">
                                <option value="">— بدون دور مرتبط —</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="jt-toggle">
                            <div>
                                <span class="lbl">@lang('users.jt_active')</span>
                                <small class="hint d-block">{{ $isRtl ? 'إظهار في قوائم اختيار الموظفين' : 'Show in staff selectors' }}</small>
                            </div>
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0" />
                                <input type="checkbox" id="jt-active" name="is_active" value="1" checked class="form-check-input" />
                            </div>
                        </div>

                        <button class="btn-gold w-100" type="submit">
                            <x-svg-icon name="save" /> @lang('users.save')
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
