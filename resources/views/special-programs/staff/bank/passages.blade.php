@extends('layouts.app')
@section('title', __('special_programs/bank.passages'))
@section('page-title', __('special_programs.programs.'.$program))
@section('content')
@include('special-programs.partials.program-tabs', ['program' => $program, 'active' => 'bank'])
@include('special-programs.staff.bank._subjects', ['route' => 'manage.special-programs.bank.passages'])
@if(session('success'))<div class="alert alert-success" role="alert">{{ session('success') }}</div>@endif
<div class="ds-card"><div class="card-body">
    <div class="d-flex flex-wrap mb-3">
        <a class="btn btn-primary btn-sm ml-2" href="{{ route('manage.special-programs.bank.passages.create', [$program, 'subject' => $subject->id]) }}"><x-svg-icon name="plus-lg" :size="14" /> {{ __('special_programs/bank.add_passage') }}</a>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('manage.special-programs.bank.index', [$program, 'subject' => $subject->id]) }}">{{ __('special_programs/bank.questions') }}</a>
    </div>
    <form method="GET" class="form-inline mb-3">
        <input type="hidden" name="subject" value="{{ $subject->id }}">
        <input class="form-control ml-2" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('special_programs/bank.search_passage') }}" aria-label="{{ __('special_programs/bank.search') }}">
        <button class="btn btn-outline-secondary btn-sm">{{ __('special_programs.buttons.search') }}</button>
    </form>
    @if($rows->isEmpty())
        <p class="text-muted mb-0">{{ __('special_programs/bank.empty') }}</p>
    @else
    <div class="table-responsive"><table class="table table-sm">
        <thead><tr><th scope="col">{{ __('special_programs/bank.code') }}</th><th scope="col">{{ __('special_programs/bank.passage_text') }}</th><th scope="col">{{ __('special_programs/bank.items') }}</th><th scope="col">{{ __('special_programs/bank.term') }}</th><th scope="col">{{ __('special_programs/bank.status') }}</th><th scope="col"></th></tr></thead>
        <tbody>
        @foreach($rows as $r)
            <tr>
                <td>{{ $r->passage_code }}</td>
                <td>{{ \Illuminate\Support\Str::limit($r->passage_text, 120) }}</td>
                <td>{{ $r->items }}</td>
                <td>{{ $r->term ? __('special_programs.terms.'.$r->term) : '—' }}</td>
                <td>{{ __('special_programs/bank.statuses.'.($r->is_active ? 'active' : 'inactive')) }}</td>
                <td class="text-nowrap">
                    <a class="btn btn-link btn-sm" href="{{ route('manage.special-programs.bank.passages.edit', [$program, $r->id]) }}">{{ __('special_programs.buttons.edit') }}</a>
                    <form method="POST" action="{{ route('manage.special-programs.bank.passages.destroy', [$program, $r->id]) }}" class="d-inline" onsubmit="return confirm('{{ __('special_programs/bank.delete_passage_confirm') }}')">
                        @csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">{{ __('special_programs.buttons.delete') }}</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    {{ $rows->links() }}
    @endif
</div></div>
@endsection
