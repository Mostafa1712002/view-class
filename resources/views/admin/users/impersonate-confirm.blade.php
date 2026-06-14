@extends('layouts.app')
@section('title', __('users.login_as'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-body">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card mt-4" style="border-top:4px solid #c9a227;">
                <div class="card-header" style="background:linear-gradient(135deg,#1a2f4e 0%,#2c4a72 100%);color:#fff;">
                    <strong><i class="la la-eye" style="color:#c9a227;"></i> @lang('users.login_as')</strong>
                </div>
                <div class="card-body text-center py-4">
                    <i class="la la-eye" style="font-size:3rem;color:#c9a227;"></i>
                    <p class="mt-2 mb-1">@lang('users.impersonate_confirm_question')</p>
                    <h5 class="mb-4">{{ $target->name }} <small class="text-muted">({{ $target->username }})</small></h5>

                    <form action="{{ route('admin.users.impersonate.start', $target->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-primary" type="submit" style="background:#c9a227;border-color:#c9a227;color:#1a2f4e;font-weight:600;">
                            <i class="la la-sign-in-alt"></i> @lang('users.impersonate_confirm_yes')
                        </button>
                    </form>
                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">@lang('users.cancel')</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
