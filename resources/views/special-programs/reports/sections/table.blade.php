{{-- $section = ['title','headers','rows','links'], $key, $program. Optional $exportable (default true). --}}
<div class="card mb-3"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
        <h3 class="h5 mb-0">{{ $section['title'] }}</h3>
        @if($exportable ?? true)
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('manage.special-programs.reports.export', [$program, 'section' => $key] + request()->query()) }}"><x-svg-icon name="download" :size="14" /> {{ __('special_programs/reports.excel') }}</a>
        @endif
    </div>
    @if(empty($section['rows']))
        <p class="text-muted mb-0">{{ __('special_programs/reports.no_rows') }}</p>
    @else
    <div class="table-responsive"><table class="table table-sm">
        <thead><tr>@foreach($section['headers'] as $h)<th scope="col">{{ $h }}</th>@endforeach</tr></thead>
        <tbody>
        @foreach($section['rows'] as $i => $row)
            <tr>@foreach($row as $j => $cell)
                <td>@if($j === 1 && isset($section['links'][$i]))<a href="{{ $section['links'][$i] }}">{{ $cell }}</a>@else{{ $cell }}@endif</td>
            @endforeach</tr>
        @endforeach
        </tbody>
    </table></div>
    @endif
</div></div>
