@extends('layouts.admin')

@section('title', 'جدول ' . $child->name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('parent.child', $child) }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-right me-1"></i>العودة
            </a>
            <h1 class="h3 mb-0">جدول {{ $child->name }}</h1>
            @if($class)
                <small class="text-muted">{{ $class->name }}</small>
            @endif
        </div>
    </div>

    @if($schedule && $periods->count() > 0)
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-center">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 100px;">الحصة</th>
                                @foreach($days as $dayKey => $dayName)
                                    <th>{{ $dayName }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 1; $i <= 8; $i++)
                                <tr>
                                    <td class="align-middle bg-light">
                                        <strong>{{ $i }}</strong>
                                    </td>
                                    @foreach($days as $dayKey => $dayName)
                                        @php
                                            $period = $periods->get($dayKey)?->firstWhere('period_number', $i);
                                        @endphp
                                        <td class="align-middle {{ $period ? 'bg-light' : '' }}">
                                            @if($period)
                                                <div class="p-2">
                                                    <strong class="text-primary">{{ $period->subject->name ?? '-' }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $period->teacher->name ?? '-' }}</small>
                                                    <br>
                                                    <small class="text-secondary">
                                                        {{ $period->start_time?->format('H:i') }} - {{ $period->end_time?->format('H:i') }}
                                                    </small>
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-calendar3 display-1 text-muted"></i>
                <p class="mt-3 text-muted">لا يوجد جدول دراسي متاح</p>
            </div>
        </div>
    @endif
</div>
@endsection
