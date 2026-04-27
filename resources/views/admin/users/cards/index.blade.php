@extends('layouts.app')
@section('title', __('users.cards'))
@section('content')
<div class="content-header"><h2 class="mb-2">@lang('users.cards')</h2></div>
<div class="content-body">
    <p class="text-muted">@lang('users.cards_help')</p>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'students' ? 'active' : '' }}" href="?tab=students">@lang('users.tab_students_parents')</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'staff' ? 'active' : '' }}" href="?tab=staff">@lang('users.tab_staff')</a>
        </li>
    </ul>

    <div class="card"><div class="card-body">
        <form action="{{ route('admin.users.cards.generate') }}" method="POST" target="_blank">
            @csrf
            <input type="hidden" name="tab" value="{{ $tab }}" />
            <div class="row">
                <div class="form-group col-md-3"><label>@lang('users.filter_search')</label>
                    <input type="text" name="q" class="form-control" /></div>
                @if($tab === 'students')
                    <div class="form-group col-md-3"><label>@lang('users.filter_grade')</label>
                        <select name="section_id" class="form-control">
                            <option value="">@lang('users.all')</option>
                            @foreach($sections as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3"><label>@lang('users.filter_class')</label>
                        <select name="class_room_id" class="form-control">
                            <option value="">@lang('users.all')</option>
                            @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3 d-flex align-items-end">
                        <div class="form-check">
                            <input type="checkbox" id="ip" name="include_parents" value="1" class="form-check-input" />
                            <label for="ip" class="form-check-label">@lang('users.include_parents')</label>
                        </div>
                    </div>
                @endif
            </div>
            <button class="btn btn-primary"><i class="la la-print"></i> @lang('users.generate_pdf')</button>
        </form>
    </div></div>
</div>
@endsection
