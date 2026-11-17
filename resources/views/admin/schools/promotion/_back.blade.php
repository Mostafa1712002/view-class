{{-- Shown on the fix-it screens opened from the promotion preview; keeps the chosen years. --}}
@if(request()->filled('source') && request()->filled('destination'))
    <div class="mb-2">
        <a href="{{ route('admin.schools.promotion.preview', ['school' => $school, 'source' => request('source'), 'destination' => request('destination')]) }}" class="btn btn-sm btn-outline-primary">
            <i class="la la-arrow-right"></i> @lang('schools.promotion_back_to_preview')
        </a>
    </div>
@endif
