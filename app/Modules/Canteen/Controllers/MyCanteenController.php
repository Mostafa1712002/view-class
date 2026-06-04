<?php

namespace App\Modules\Canteen\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Canteen;
use App\Models\CanteenBalance;
use App\Models\CanteenBlockedProduct;
use App\Models\CanteenOrder;
use App\Models\CanteenProduct;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MyCanteenController extends Controller
{
    /** The IDs of children linked to the current parent. */
    private function childIds(): array
    {
        return DB::table('parent_student')->where('parent_id', auth()->id())->pluck('student_id')->all();
    }

    /** Load a child the current parent is actually linked to, or 403. */
    private function child(int $studentId): User
    {
        abort_unless(in_array($studentId, $this->childIds(), true), 403);

        return User::query()->whereKey($studentId)->firstOrFail();
    }

    private function balanceFor(User $child): ?CanteenBalance
    {
        return CanteenBalance::where('school_id', $child->school_id)->where('student_id', $child->id)->first();
    }

    public function index(): View
    {
        $children = User::query()->whereIn('id', $this->childIds())->orderBy('name')->get(['id', 'name', 'school_id']);
        $balances = CanteenBalance::whereIn('student_id', $children->pluck('id'))->get()->keyBy('student_id');

        $children->each(function ($c) use ($balances) {
            $b = $balances->get($c->id);
            $c->balance = $b ? $b->balance : '0.00';
            $c->daily_limit = $b?->daily_limit;
        });

        return view('user.canteen.index', compact('children'));
    }

    public function updateLimit(Request $request, int $studentId): RedirectResponse
    {
        $child = $this->child($studentId);
        $data = $request->validate([
            'daily_limit' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $balance = CanteenBalance::firstOrCreate(
            ['school_id' => $child->school_id, 'student_id' => $child->id],
            ['balance' => 0],
        );
        $balance->update(['daily_limit' => $data['daily_limit'] !== null && $data['daily_limit'] !== '' ? $data['daily_limit'] : null]);

        return back()->with('status', __('canteen.parent.flash.limit_updated'));
    }

    public function products(int $studentId): View
    {
        $child = $this->child($studentId);

        // Products the child could be served: active products of active canteens in their school.
        $products = CanteenProduct::query()
            ->where('is_active', true)
            ->whereHas('canteen', fn ($q) => $q->where('is_active', true)->where('school_id', $child->school_id))
            ->with('category')
            ->orderBy('name')
            ->get();

        $blocked = array_fill_keys(
            CanteenBlockedProduct::where('student_id', $child->id)->pluck('canteen_product_id')->all(),
            true
        );

        return view('user.canteen.products', compact('child', 'products', 'blocked'));
    }

    public function toggleBlock(int $studentId, int $productId): RedirectResponse
    {
        $child = $this->child($studentId);

        // The product must be one served to the child (same school, exists).
        $product = CanteenProduct::query()
            ->whereKey($productId)
            ->whereHas('canteen', fn ($q) => $q->where('school_id', $child->school_id))
            ->firstOrFail();

        $existing = CanteenBlockedProduct::where('student_id', $child->id)->where('canteen_product_id', $product->id)->first();
        if ($existing) {
            $existing->delete();
            $msg = __('canteen.parent.flash.unblocked');
        } else {
            CanteenBlockedProduct::create([
                'student_id' => $child->id,
                'canteen_product_id' => $product->id,
                'blocked_by' => auth()->id(),
            ]);
            $msg = __('canteen.parent.flash.blocked');
        }

        return back()->with('status', $msg);
    }

    public function orders(int $studentId): View
    {
        $child = $this->child($studentId);

        $orders = CanteenOrder::query()
            ->with(['canteen', 'items'])
            ->where('student_id', $child->id)
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return view('user.canteen.orders', compact('child', 'orders'));
    }
}
