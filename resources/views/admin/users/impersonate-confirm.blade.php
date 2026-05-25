@extends('layouts.app')
@section('title', __('users.login_as'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-body">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card mt-4">
                <div class="card-header bg-white">
                    <strong><i class="la la-user-secret"></i> @lang('users.login_as')</strong>
                </div>
                <div class="card-body text-center py-4">
                    <p class="mb-1">@lang('users.impersonate_confirm_question')</p>
                    <h5 class="mb-4">{{ $target->name }} <small class="text-muted">({{ $target->username }})</small></h5>

                    <form action="{{ route('admin.users.impersonate.start', $target->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-primary" type="submit">
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
