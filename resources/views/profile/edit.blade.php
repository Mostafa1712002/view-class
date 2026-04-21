@extends('layouts.app')

@section('title', __('profile.edit_title'))

@section('content')
<div class="content-wrapper">
    <div class="content-body">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">@lang('profile.edit_title')</h4>
                    </div>
                    <div class="card-body">
                        @if(session('status'))
                            <div class="alert alert-success">{{ session('status') }}</div>
                        @endif

                        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="mb-4">
                            @csrf
                            @method('PATCH')

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">@lang('profile.name_ar')</label>
                                    <input type="text" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror" value="{{ old('name_ar', $user->name_ar ?? $user->name) }}">
                                    @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">@lang('profile.name_en')</label>
                                    <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror" value="{{ old('name_en', $user->name_en) }}">
                                    @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">@lang('profile.phone')</label>
                                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}">
                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">@lang('profile.email')</label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}">
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">@lang('profile.save_changes')</button>
                        </form>

                        <hr>

                        <form action="{{ route('profile.change-password') }}" method="POST" class="mb-4">
                            @csrf
                            @method('PATCH')
                            <h5>@lang('profile.change_password')</h5>

                            <div class="mb-3">
                                <label class="form-label">@lang('profile.current_password')</label>
                                <input type="password" name="currentPassword" class="form-control @error('currentPassword') is-invalid @enderror" required>
                                @error('currentPassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">@lang('profile.new_password')</label>
                                <input type="password" name="newPassword" class="form-control @error('newPassword') is-invalid @enderror" minlength="8" required>
                                @error('newPassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">@lang('profile.confirm_new_password')</label>
                                <input type="password" name="newPassword_confirmation" class="form-control" minlength="8" required>
                            </div>

                            <button type="submit" class="btn btn-outline-primary">@lang('profile.save_password')</button>
                        </form>

                        <hr>

                        <form action="{{ route('profile.avatar') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('POST')
                            <h5>@lang('profile.upload_avatar')</h5>
                            <div class="mb-3">
                                <input type="file" name="avatar" accept="image/*" class="form-control @error('avatar') is-invalid @enderror" required>
                                @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-outline-primary">@lang('profile.upload_avatar')</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
