@if(request()->filled('source') && request()->filled('destination'))
    <input type="hidden" name="source" value="{{ (int) request('source') }}">
    <input type="hidden" name="destination" value="{{ (int) request('destination') }}">
@endif
