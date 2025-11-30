@extends('layouts.admin')

@section('title', 'معلومات النظام')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.maintenance.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="la la-arrow-right me-1"></i>العودة
            </a>
            <h1 class="h3 mb-0">معلومات النظام</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="la la-server me-1"></i>معلومات الخادم</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th style="width: 200px;">إصدار PHP</th>
                            <td><code>{{ $info['php_version'] }}</code></td>
                        </tr>
                        <tr>
                            <th>إصدار Laravel</th>
                            <td><code>{{ $info['laravel_version'] }}</code></td>
                        </tr>
                        <tr>
                            <th>برنامج الخادم</th>
                            <td><code>{{ $info['server_software'] }}</code></td>
                        </tr>
                        <tr>
                            <th>البيئة</th>
                            <td>
                                <span class="badge bg-{{ $info['environment'] === 'production' ? 'danger' : 'warning' }}">
                                    {{ $info['environment'] }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>وضع التصحيح</th>
                            <td>
                                <span class="badge bg-{{ $info['debug_mode'] === 'مفعل' ? 'warning' : 'success' }}">
                                    {{ $info['debug_mode'] }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="la la-cog me-1"></i>إعدادات PHP</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th style="width: 200px;">الحد الأقصى للذاكرة</th>
                            <td><code>{{ $info['memory_limit'] }}</code></td>
                        </tr>
                        <tr>
                            <th>الحد الأقصى لوقت التنفيذ</th>
                            <td><code>{{ $info['max_execution_time'] }} ثانية</code></td>
                        </tr>
                        <tr>
                            <th>الحد الأقصى لحجم الرفع</th>
                            <td><code>{{ $info['upload_max_filesize'] }}</code></td>
                        </tr>
                        <tr>
                            <th>الحد الأقصى لـ POST</th>
                            <td><code>{{ $info['post_max_size'] }}</code></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="la la-globe me-1"></i>الإعدادات الإقليمية</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th style="width: 200px;">المنطقة الزمنية</th>
                            <td><code>{{ $info['timezone'] }}</code></td>
                        </tr>
                        <tr>
                            <th>اللغة الافتراضية</th>
                            <td><code>{{ $info['locale'] }}</code></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="la la-plug me-1"></i>الامتدادات المثبتة</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @php
                            $requiredExtensions = ['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'gd', 'curl'];
                        @endphp
                        @foreach($requiredExtensions as $ext)
                        <div class="col-6 mb-2">
                            @if(extension_loaded($ext))
                                <span class="text-success"><i class="la la-check-circle me-1"></i>{{ $ext }}</span>
                            @else
                                <span class="text-danger"><i class="la la-times-circle me-1"></i>{{ $ext }}</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
