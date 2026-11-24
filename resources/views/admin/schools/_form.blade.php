@php
    $isEdit = isset($school);
    $val = fn($k, $d=null) => old($k, $isEdit ? ($school->{$k} ?? $d) : $d);
@endphp
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name_ar" class="form-label">@lang('schools.name_ar') <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('name_ar') is-invalid @enderror" id="name_ar" name="name_ar" value="{{ $val('name_ar') }}" required>
        @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="name_en" class="form-label">@lang('schools.name_en') <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('name_en') is-invalid @enderror" id="name_en" name="name_en" value="{{ $val('name_en') }}" required>
        @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="branch_id" class="form-label">@lang('schools.branch')</label>
        <select class="form-control select2" id="branch_id" name="branch_id">
            <option value="">—</option>
            @foreach(($branches ?? []) as $b)
                <option value="{{ $b->id }}" @selected($val('branch_id') == $b->id)>{{ app()->getLocale() === 'en' ? ($b->name_en ?: $b->name_ar) : ($b->name_ar ?: $b->name_en) }}</option>
            @endforeach
        </select>
        @if(($branches ?? collect())->isEmpty())
            <small class="text-muted">
                @lang('schools.no_branches_yet')
                <a href="{{ route('admin.school-branches.create') }}">@lang('schools.add_branch_now')</a>
            </small>
        @endif
    </div>
    <div class="col-md-4 mb-3">
        <label for="sort_order" class="form-label">@lang('schools.sort_order')</label>
        <input type="number" min="0" class="form-control" id="sort_order" name="sort_order" value="{{ $val('sort_order') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="educational_track" class="form-label">@lang('schools.educational_track')</label>
        <select class="form-control select2" id="educational_track" name="educational_track">
            @foreach(['national','international','general','k12'] as $t)
                <option value="{{ $t }}" @selected($val('educational_track','national') === $t)>@lang('schools.track_' . $t)</option>
            @endforeach
        </select>
        <small class="text-muted">@lang('schools.track_predefined_hint')</small>
    </div>

    <div class="col-md-6 mb-3">
        <label for="stage" class="form-label">@lang('schools.stage') <span class="text-danger">*</span></label>
        <select class="form-control select2 @error('stage') is-invalid @enderror" id="stage" name="stage" required>
            <option value="">—</option>
            @foreach(['primary','intermediate','secondary'] as $s)
                <option value="{{ $s }}" @selected($val('stage') === $s)>@lang('schools.stage_' . $s)</option>
            @endforeach
        </select>
        @error('stage')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="city" class="form-label">@lang('schools.city') <span class="text-danger">*</span></label>
        <select class="form-control select2 @error('city') is-invalid @enderror" id="city" name="city" required>
            <option value="">—</option>
            @foreach(($cities ?? []) as $key => $labels)
                <option value="{{ $key }}" @selected($val('city') === $key)>{{ app()->getLocale() === 'en' ? $labels['en'] : $labels['ar'] }}</option>
            @endforeach
        </select>
        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="student_gender" class="form-label">@lang('schools.student_gender') <span class="text-danger">*</span></label>
        <select class="form-control select2 @error('student_gender') is-invalid @enderror" id="student_gender" name="student_gender" required>
            <option value="">—</option>
            <option value="boys"  @selected($val('student_gender') === 'boys')>@lang('schools.gender_boys')</option>
            <option value="girls" @selected($val('student_gender') === 'girls')>@lang('schools.gender_girls')</option>
            {{-- «مختلط» is no longer offered; kept only so a legacy mixed school can be re-saved unchanged. --}}
            @if($val('student_gender') === 'mixed')
                <option value="mixed" selected>@lang('schools.gender_mixed')</option>
            @endif
        </select>
        @error('student_gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="timezone" class="form-label">@lang('schools.timezone') <span class="text-danger">*</span></label>
        <select class="form-control select2 @error('timezone') is-invalid @enderror" id="timezone" name="timezone" required>
            @foreach(($timezones ?? []) as $tz => $label)
                <option value="{{ $tz }}" @selected($val('timezone','Asia/Riyadh') === $tz)>{{ $label }}</option>
            @endforeach
        </select>
        @error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="default_language" class="form-label">@lang('schools.default_language') <span class="text-danger">*</span></label>
        <select class="form-control select2" id="default_language" name="default_language" required>
            <option value="ar" @selected($val('default_language','ar') === 'ar')>@lang('schools.lang_ar')</option>
            <option value="en" @selected($val('default_language','ar') === 'en')>@lang('schools.lang_en')</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label for="educational_company_id" class="form-label">@lang('schools.educational_company')</label>
        <select class="form-control select2" id="educational_company_id" name="educational_company_id">
            <option value="">—</option>
            @foreach($companies as $c)
                <option value="{{ $c->id }}" @selected($val('educational_company_id') == $c->id)>{{ $c->name_ar ?: $c->name_en }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label for="compound_id" class="form-label">@lang('schools.educational_complex')</label>
        <select class="form-control select2" id="compound_id" name="compound_id">
            <option value="">—</option>
            @foreach(($complexes ?? []) as $cx)
                <option value="{{ $cx->id }}" @selected(($selectedComplexId ?? old('compound_id')) == $cx->id)>{{ $cx->name_ar ?: $cx->name_en }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label for="manager_user_id" class="form-label">@lang('schools.manager_user_id')</label>
        <select class="form-control select2 @error('manager_user_id') is-invalid @enderror" id="manager_user_id" name="manager_user_id">
            <option value="">—</option>
            @foreach(($managerCandidates ?? collect()) as $person)
                <option value="{{ $person->id }}" @selected($val('manager_user_id') == $person->id)>{{ $person->name }}@if($person->school) — {{ $person->school->name_ar ?: $person->school->name }}@endif</option>
            @endforeach
        </select>
        @error('manager_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">@lang('schools.manager_user_id_hint')</small>
    </div>

    @php
        $linkedAgents = $isEdit ? $school->agents()->get() : collect();
        $agentIds = array_map('intval', array_filter((array) old('agent_user_ids', $linkedAgents->pluck('id')->all())));
        $printIds = array_map('intval', array_filter((array) old('print_agent_ids', $linkedAgents->where('pivot.print_on_certificate', true)->pluck('id')->all())));
    @endphp
    <div class="col-md-6 mb-3">
        <label for="agent_user_ids" class="form-label">@lang('schools.agent_user_ids')</label>
        <select class="form-control select2 @error('agent_user_ids.*') is-invalid @enderror" id="agent_user_ids" name="agent_user_ids[]" multiple>
            @foreach(($agentCandidates ?? collect()) as $person)
                <option value="{{ $person->id }}" @selected(in_array($person->id, $agentIds, true))>{{ $person->name }}@if($person->school) — {{ $person->school->name_ar ?: $person->school->name }}@endif</option>
            @endforeach
        </select>
        @error('agent_user_ids.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">@lang('schools.agent_user_ids_hint')</small>
    </div>
    <div class="col-md-6 mb-3">
        <label for="print_agent_ids" class="form-label">@lang('schools.print_agent_ids')</label>
        {{-- Empty marker so «no agent printed» survives a submit with nothing selected. --}}
        <input type="hidden" name="print_agent_ids[]" value="">
        <select class="form-control select2" id="print_agent_ids" name="print_agent_ids[]" multiple>
            @foreach(($agentCandidates ?? collect()) as $person)
                @if(in_array($person->id, $agentIds, true))
                    <option value="{{ $person->id }}" @selected(in_array($person->id, $printIds, true))>{{ $person->name }}</option>
                @endif
            @endforeach
        </select>
        <small class="text-muted">@lang('schools.print_agent_ids_hint')</small>
    </div>

    <div class="col-md-6 mb-3">
        <label for="phone" class="form-label">@lang('common.phone')</label>
        <input type="text" class="form-control" id="phone" name="phone" value="{{ $val('phone') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label for="fax" class="form-label">@lang('schools.fax')</label>
        <input type="text" class="form-control" id="fax" name="fax" value="{{ $val('fax') }}">
    </div>

    <div class="col-md-6 mb-3">
        <label for="email" class="form-label">@lang('common.email')</label>
        <input type="email" class="form-control" id="email" name="email" value="{{ $val('email') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label for="website" class="form-label">@lang('schools.website')</label>
        <input type="url" class="form-control" id="website" name="website" value="{{ $val('website') }}">
    </div>

    <div class="col-12 mb-3">
        <label for="address" class="form-label">@lang('common.address')</label>
        <textarea class="form-control" id="address" name="address" rows="2">{{ $val('address') }}</textarea>
    </div>

    <div class="col-md-6 mb-3">
        <label for="logo" class="form-label">@lang('schools.logo')</label>
        @if($isEdit && $school->logo)
            <div class="mb-2"><img src="{{ asset('storage/'.$school->logo) }}" style="max-height:60px;"></div>
        @endif
        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
        <small class="text-muted">@lang('schools.logo_hint')</small>
    </div>

    <div class="col-md-6 mb-3">
        <label for="stamp" class="form-label">@lang('schools.stamp')</label>
        @if($isEdit && $school->stamp)
            <div class="mb-2"><img src="{{ asset('storage/'.$school->stamp) }}" style="max-height:80px;" alt="@lang('schools.stamp')"></div>
        @endif
        <input type="file" class="form-control @error('stamp') is-invalid @enderror" id="stamp" name="stamp" accept="image/*">
        @error('stamp')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">@lang('schools.stamp_hint')</small>
    </div>

    <div class="col-12">
        <h5 class="mt-3 mb-2">@lang('schools.social_accounts')</h5>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Facebook</label>
        <input type="url" class="form-control" name="facebook" value="{{ $val('facebook') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Twitter</label>
        <input type="url" class="form-control" name="twitter" value="{{ $val('twitter') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Instagram</label>
        <input type="url" class="form-control" name="instagram" value="{{ $val('instagram') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">LinkedIn</label>
        <input type="url" class="form-control" name="linkedin" value="{{ $val('linkedin') }}">
    </div>

    @if($isEdit)
        <div class="col-md-6 mb-3">
            <div class="form-check mt-4">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" @checked($val('is_active', true))>
                <label class="form-check-label" for="is_active">@lang('schools.is_active')</label>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
(function () {
    if (!window.jQuery) return;
    jQuery(function ($) {
        var $company = $('#educational_company_id');
        var $complex = $('#compound_id');
        if (!$company.length || !$complex.length) return;

        var byCompanyUrl = @json(route('admin.educational-complexes.by-company'));
        var preselectedComplexId = @json($selectedComplexId ?? old('compound_id'));

        // Cascades from the company select (design.md: complex options depend on
        // the chosen company). preselect keeps the school's current complex
        // selected the first time this runs (edit form).
        function loadComplexes(companyId, preselect) {
            $complex.empty().append($('<option>', { value: '', text: '—' }));
            if (!companyId) {
                $complex.trigger('change');
                return;
            }

            $.getJSON(byCompanyUrl, { company_id: companyId }).done(function (list) {
                (list || []).forEach(function (item) {
                    $complex.append($('<option>', {
                        value: item.id,
                        text: item.name,
                        selected: preselect && String(preselect) === String(item.id),
                    }));
                });
                $complex.trigger('change');
            });
        }

        if ($company.val()) {
            loadComplexes($company.val(), preselectedComplexId);
        }

        $company.on('change', function () {
            loadComplexes($company.val(), null);
        });
    });
})();
</script>
@endpush

@push('scripts')
<script>
    // Keep «printed on the certificate» limited to the linked agents; a newly linked agent prints by default.
    $(function () {
        var $agents = $('#agent_user_ids'), $print = $('#print_agent_ids');
        $agents.on('select2:select', function (e) {
            if (!$print.find('option[value="' + e.params.data.id + '"]').length) {
                $print.append(new Option(e.params.data.text.split(' — ')[0], e.params.data.id, true, true));
            }
            $print.trigger('change');
        }).on('select2:unselect', function (e) {
            $print.find('option[value="' + e.params.data.id + '"]').remove();
            $print.trigger('change');
        });
    });
</script>
@endpush
