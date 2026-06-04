@csrf
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0 pr-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

@if($categories->isEmpty())
    <div class="alert alert-warning">@lang('canteen.products.no_categories')</div>
@endif

<div class="row">
    <div class="form-group mb-3 col-md-6">
        <label class="form-label">@lang('canteen.products.fields.category') <span class="text-danger">*</span></label>
        @php $cid = old('canteen_category_id', $product->canteen_category_id); @endphp
        <select name="canteen_category_id" class="custom-select" required>
            <option value="">@lang('canteen.products.choose_category')</option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}" @selected((string)$cid===(string)$c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group mb-3 col-md-6">
        <label class="form-label">@lang('canteen.products.fields.name') <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name', $product->name) }}" class="form-control" required maxlength="255">
    </div>
</div>

<div class="row">
    <div class="form-group mb-3 col-md-4">
        <label class="form-label">@lang('canteen.products.fields.price') <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price) }}" class="form-control" required>
    </div>
    <div class="form-group mb-3 col-md-4">
        <label class="form-label">@lang('canteen.products.fields.calories')</label>
        <input type="number" min="0" name="calories" value="{{ old('calories', $product->calories) }}" class="form-control">
    </div>
    <div class="form-group mb-3 col-md-4">
        <label class="form-label">@lang('canteen.products.fields.sort_order')</label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $product->sort_order ?? 0) }}" class="form-control">
    </div>
</div>

<div class="form-group mb-3">
    <label class="form-label">@lang('canteen.products.fields.image')</label>
    <input type="file" name="image" class="form-control" accept="image/*">
    @if($product->imageUrl())
        <img src="{{ $product->imageUrl() }}" alt="" class="mt-2" style="width:72px;height:72px;object-fit:cover;border-radius:.35rem;">
    @endif
</div>

<div class="form-group mb-3">
    <div class="form-check">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" id="prod-active" class="form-check-input" @checked(old('is_active', $product->is_active ?? true))>
        <label class="form-check-label" for="prod-active">@lang('canteen.products.fields.is_active')</label>
    </div>
</div>

<div class="d-flex" style="gap:.5rem;">
    <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('canteen.actions.save')</button>
    <a href="{{ route('admin.canteens.products.index', $canteen->id) }}" class="btn btn-outline-secondary">@lang('canteen.actions.cancel')</a>
</div>
