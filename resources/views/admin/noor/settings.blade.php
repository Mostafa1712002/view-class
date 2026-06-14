@extends('layouts.app')
@section('title', __('noor.settings.page_title'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('noor.settings.page_title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.noor.form') }}">@lang('noor.breadcrumb')</a></li>
            <li class="breadcrumb-item active">@lang('noor.settings.page_title')</li>
        </ol>
    </div>
</div>

<div class="content-body">

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 pr-3">
                @foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Status notice --}}
    <div class="alert alert-warning">
        <i class="la la-info-circle"></i>
        <strong>@lang('noor.settings.not_activated_title')</strong><br>
        @lang('noor.settings.not_activated_body')
    </div>

    <div class="row">
        {{-- Active method selection --}}
        <div class="col-lg-7 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">@lang('noor.settings.card_connection')</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.noor.settings.save') }}" id="noor-settings-form">
                        @csrf

                        {{-- Active method --}}
                        <div class="form-group mb-3">
                            <label class="form-label">@lang('noor.settings.active_method_label')</label>
                            <select name="active_method" id="active_method" class="form-control">
                                <option value="excel" @selected($settings['method'] === 'excel')>@lang('noor.settings.method_excel')</option>
                                <option value="api" @selected($settings['method'] === 'api')>@lang('noor.settings.method_api')</option>
                                <option value="credential" @selected($settings['method'] === 'credential')>@lang('noor.settings.method_credential')</option>
                            </select>
                            <small class="text-muted">@lang('noor.settings.active_method_hint')</small>
                        </div>

                        {{-- Method 2: API settings --}}
                        <div id="section-api" class="{{ $settings['method'] === 'api' ? '' : 'd-none' }}">
                            <hr>
                            <h6 class="mb-3">@lang('noor.settings.section_api')</h6>

                            <div class="form-group mb-3">
                                <label class="form-label" for="api_base_url">@lang('noor.settings.api_base_url')</label>
                                <input type="url" name="api_base_url" id="api_base_url" class="form-control"
                                    value="{{ old('api_base_url', $settings['api_base_url']) }}"
                                    placeholder="https://api.noor.sa/v1">
                                <small class="text-muted">@lang('noor.settings.api_base_url_hint')</small>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label" for="api_token">@lang('noor.settings.api_token')</label>
                                <input type="password" name="api_token" id="api_token" class="form-control"
                                    placeholder="{{ $settings['has_api_token'] ? __('noor.settings.secret_saved') : __('noor.settings.enter_token') }}">
                                @if($settings['has_api_token'])
                                    <small class="text-success"><i class="la la-lock"></i> @lang('noor.settings.secret_saved')</small>
                                @endif
                            </div>
                        </div>

                        {{-- Method 3: Credential settings --}}
                        <div id="section-credential" class="{{ $settings['method'] === 'credential' ? '' : 'd-none' }}">
                            <hr>
                            <h6 class="mb-3">@lang('noor.settings.section_credential')</h6>
                            <div class="alert alert-danger" style="font-size:0.88rem;">
                                <i class="la la-shield-alt"></i>
                                @lang('noor.settings.credential_security_note')
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label" for="admin_username">@lang('noor.settings.admin_username')</label>
                                <input type="text" name="admin_username" id="admin_username" class="form-control"
                                    value="{{ old('admin_username', $settings['admin_username']) }}"
                                    autocomplete="off">
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label" for="admin_password">@lang('noor.settings.admin_password')</label>
                                <input type="password" name="admin_password" id="admin_password" class="form-control"
                                    placeholder="{{ $settings['has_admin_pass'] ? __('noor.settings.secret_saved') : __('noor.settings.enter_password') }}"
                                    autocomplete="new-password">
                                @if($settings['has_admin_pass'])
                                    <small class="text-success"><i class="la la-lock"></i> @lang('noor.settings.secret_saved')</small>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="la la-save"></i> @lang('noor.settings.save')
                            </button>
                            <button type="button" id="btn-test-conn" class="btn btn-outline-secondary">
                                <i class="la la-plug"></i> @lang('noor.settings.test_connection')
                            </button>
                        </div>
                    </form>

                    <div id="test-conn-result" class="alert mt-3 d-none"></div>
                </div>
            </div>
        </div>

        {{-- Info panel --}}
        <div class="col-lg-5 mb-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">@lang('noor.settings.info_title')</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0 pr-3" style="line-height:2;">
                        <li><strong>@lang('noor.settings.method_excel')</strong> — @lang('noor.settings.info_excel')</li>
                        <li><strong>@lang('noor.settings.method_api')</strong> — @lang('noor.settings.info_api')</li>
                        <li><strong>@lang('noor.settings.method_credential')</strong> — @lang('noor.settings.info_credential')</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Sync log --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="la la-history"></i> @lang('noor.settings.sync_log_title')</h5>
        </div>
        <div class="card-body p-0">
            @if (count($history))
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>@lang('noor.settings.col_method')</th>
                                <th>@lang('noor.settings.col_status')</th>
                                <th>@lang('noor.settings.col_total')</th>
                                <th>@lang('noor.settings.col_imported')</th>
                                <th>@lang('noor.settings.col_failed')</th>
                                <th>@lang('noor.settings.col_note')</th>
                                <th>@lang('noor.settings.col_date')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $h)
                                <tr>
                                    <td>{{ $h->id }}</td>
                                    <td>{{ $h->method }}</td>
                                    <td>
                                        <span class="badge {{ $h->status === 'completed' ? 'badge-success' : ($h->status === 'failed' ? 'badge-danger' : 'badge-secondary') }}">
                                            {{ $h->status }}
                                        </span>
                                    </td>
                                    <td>{{ $h->total_records }}</td>
                                    <td class="text-success">{{ $h->imported_count }}</td>
                                    <td class="text-danger">{{ $h->failed_count }}</td>
                                    <td>{{ $h->note }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($h->created_at)->format('Y/m/d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted text-center p-3 mb-0">@lang('noor.settings.sync_log_empty')</p>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('active_method')?.addEventListener('change', function() {
    document.getElementById('section-api').classList.toggle('d-none', this.value !== 'api');
    document.getElementById('section-credential').classList.toggle('d-none', this.value !== 'credential');
});

document.getElementById('btn-test-conn')?.addEventListener('click', function() {
    const result = document.getElementById('test-conn-result');
    fetch('{{ route('admin.noor.settings.test') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(r => r.json())
    .then(data => {
        result.className = 'alert mt-3 ' + (data.success ? 'alert-info' : 'alert-danger');
        result.textContent = data.message || (data.error && data.error.message) || 'Unknown response';
        result.classList.remove('d-none');
    })
    .catch(() => {
        result.className = 'alert mt-3 alert-danger';
        result.textContent = 'Request failed';
        result.classList.remove('d-none');
    });
});
</script>
@endpush
@endsection
