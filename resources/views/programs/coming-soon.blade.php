@extends('layouts.app')

@section('title', __('shell.section_programs'))
@section('body_class', 'theme-light')

@section('content')
<div class="content-body">
    <div class="card">
        <div class="card-body text-center py-5">
            <div style="font-size:3.5rem;color:var(--gold-400,#cfa046);line-height:1;">
                <i class="la la-rocket"></i>
            </div>
            <h2 class="mt-3 mb-2" style="font-weight:700;color:#0f172a;">@lang('common.coming_soon')</h2>
            <p class="text-muted mb-4">@lang('common.coming_soon_hint')</p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                <i class="la la-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
                @lang('common.back')
            </a>
        </div>
    </div>
</div>
@endsection
