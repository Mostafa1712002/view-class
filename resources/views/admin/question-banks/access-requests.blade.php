@extends('layouts.app')

@section('title', __('question_banks.access_requests_title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('question_banks.access_requests_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.index') }}">@lang('question_banks.page_title')</a></li>
                <li class="breadcrumb-item active">@lang('question_banks.access_requests_title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-body table-responsive">
            @if($requests->isEmpty())
                <p class="text-muted mb-0">@lang('question_banks.access_requests_empty')</p>
            @else
            <table class="table table-hover">
                <thead><tr>
                    <th>@lang('question_banks.col_school')</th>
                    <th>@lang('question_banks.access_req_bank')</th>
                    <th>@lang('question_banks.access_req_by')</th>
                    <th>@lang('question_banks.access_req_status')</th>
                    <th></th>
                </tr></thead>
                <tbody>
                @foreach($requests as $req)
                    <tr>
                        <td>{{ optional($req->school)->name ?? '—' }}</td>
                        <td>{{ optional($req->bank)->name_ar ?? '—' }}</td>
                        <td>{{ optional($req->requester)->name ?? optional($req->requester)->username }}<br>
                            <small class="text-muted">{{ $req->created_at?->format('Y-m-d H:i') }}</small></td>
                        <td>
                            @php $badge = ['pending'=>'warning','approved'=>'success','rejected'=>'danger'][$req->status] ?? 'secondary'; @endphp
                            <span class="badge badge-{{ $badge }}">@lang('question_banks.access_status_'.$req->status)</span>
                        </td>
                        <td>
                            @if($req->status === 'pending')
                                <form method="POST" action="{{ route('admin.question-banks.access-requests.decide', $req->id) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="decision" value="approved">
                                    <button class="btn btn-sm btn-success"><i class="la la-check"></i> @lang('question_banks.access_approve')</button>
                                </form>
                                <form method="POST" action="{{ route('admin.question-banks.access-requests.decide', $req->id) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="decision" value="rejected">
                                    <button class="btn btn-sm btn-outline-danger"><i class="la la-times"></i> @lang('question_banks.access_reject')</button>
                                </form>
                            @else
                                <small class="text-muted">{{ $req->decided_at?->format('Y-m-d H:i') }}</small>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $requests->links() }}
            @endif
        </div>
    </div>
</div>
@endsection
