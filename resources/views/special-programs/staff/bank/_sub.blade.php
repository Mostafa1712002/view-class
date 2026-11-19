{{-- one passage sub-question block; $n index, $s data, $skillHtml options --}}
<div class="border rounded p-3 mb-3">
    @if(! empty($s['id']))<input type="hidden" name="subs[{{ $n }}][id]" value="{{ $s['id'] }}">@endif
    <div class="form-group"><label for="sub{{ $n }}_body">{{ __('special_programs/bank.stem') }} ({{ is_numeric($n) ? $n + 1 : '' }})</label>
        <textarea class="form-control" id="sub{{ $n }}_body" name="subs[{{ $n }}][body_ar]" rows="2" maxlength="5000">{{ $s['body_ar'] ?? '' }}</textarea></div>
    @if($program === 'nafis')
    <div class="form-group"><label for="sub{{ $n }}_skill">{{ __('special_programs/bank.skill') }}</label>
        <select class="form-control" id="sub{{ $n }}_skill" name="subs[{{ $n }}][sp_skill_id]"><option value="">{{ __('special_programs/bank.choose') }}</option>{!! $skillHtml !!}</select></div>
    @endif
    @for($i = 0; $i < 4; $i++)
        <div class="form-row align-items-center mb-2">
            <div class="col-auto"><div class="custom-control custom-radio"><input type="radio" class="custom-control-input" id="sub{{ $n }}_c{{ $i }}" name="subs[{{ $n }}][correct_index]" value="{{ $i }}" @checked((string) ($s['correct_index'] ?? 0) === (string) $i)><label class="custom-control-label" for="sub{{ $n }}_c{{ $i }}">{{ __('special_programs/bank.correct') }}</label></div></div>
            <div class="col"><input class="form-control" name="subs[{{ $n }}][options_ar][{{ $i }}]" maxlength="1000" value="{{ $s['options_ar'][$i] ?? '' }}" aria-label="{{ __('special_programs/bank.option_n', ['n' => $i + 1]) }}" placeholder="{{ __('special_programs/bank.option_n', ['n' => $i + 1]) }}"></div>
        </div>
    @endfor
</div>
