<?php

namespace App\Modules\Canteen\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Canteen;
use App\Models\CanteenBalance;
use App\Models\CanteenBlockedProduct;
use App\Models\CanteenOrder;
use App\Models\CanteenProduct;
use App\Models\User;
use App\Modules\Canteen\Services\CanteenBalanceService;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class CanteenOrderController extends Controller
{
    use HasSchoolScope;

    public function __construct(private CanteenBalanceService $balances) {}

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $status = $request->get('status');

        $orders = CanteenOrder::query()
            ->with(['canteen', 'student'])
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->when($status && in_array($status, CanteenOrder::STATUSES, true), fn ($w) => $w->where('status', $status))
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return view('admin.canteens.orders.index', compact('orders', 'status'));
    }

    public function create(Request $request): View
    {
        $schoolId = $this->activeSchoolId();

        $canteens = Canteen::query()->where('is_active', true)
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->orderBy('name_ar')->get(['id', 'name_ar']);

        $selected = null;
        $products = collect();
        $students = collect();
        if ($request->filled('canteen')) {
            $selected = $canteens->firstWhere('id', (int) $request->get('canteen'));
            if ($selected) {
                $full = Canteen::find($selected->id);
                $products = $full->products()->where('is_active', true)->with('category')->orderBy('sort_order')->orderBy('name')->get();
                $students = User::query()->whereHas('roles', fn ($r) => $r->where('slug', 'student'))
                    ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
                    ->orderBy('name')->limit(1000)->get(['id', 'name']);
            }
        }

        return view('admin.canteens.orders.create', compact('canteens', 'selected', 'products', 'students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();

        $data = $request->validate([
            'canteen_id' => ['required', Rule::exists('canteens', 'id')->where(fn ($q) => $schoolId ? $q->where('school_id', $schoolId) : $q)],
            // Scope the student to the active school — never let an order target another tenant's student.
            'student_id' => ['required', Rule::exists('users', 'id')->where(fn ($q) => $schoolId ? $q->where('school_id', $schoolId) : $q)],
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $canteen = Canteen::query()
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->findOrFail($data['canteen_id']);
        if (! $canteen->is_active) {
            return back()->withInput()->with('error', __('canteen.orders.flash.canteen_inactive'));
        }

        $student = User::whereKey($data['student_id'])
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->whereHas('roles', fn ($r) => $r->where('slug', 'student'))
            ->firstOrFail();

        // A canteen only serves students of its own school.
        abort_unless((int) $canteen->school_id === (int) $student->school_id, 422);

        // Build the line items from qty>0 entries, validating each product.
        $blocked = CanteenBlockedProduct::where('student_id', $student->id)->pluck('canteen_product_id')->all();
        $blocked = array_fill_keys($blocked, true);

        $lines = [];
        $total = 0.0;
        foreach (($data['items'] ?? []) as $productId => $qty) {
            $qty = (int) $qty;
            if ($qty < 1) {
                continue;
            }
            $product = CanteenProduct::where('canteen_id', $canteen->id)->where('is_active', true)->find($productId);
            if (! $product) {
                return back()->withInput()->with('error', __('canteen.orders.flash.unavailable'));
            }
            if (isset($blocked[$product->id])) {
                return back()->withInput()->with('error', __('canteen.orders.flash.blocked', ['product' => $product->name]));
            }
            $lineTotal = round((float) $product->price * $qty, 2);
            $lines[] = ['product' => $product, 'qty' => $qty, 'line_total' => $lineTotal];
            $total += $lineTotal;
        }

        if (empty($lines)) {
            return back()->withInput()->with('error', __('canteen.orders.flash.empty'));
        }
        $total = round($total, 2);

        // Daily-limit guard: today's charged spend + this order must stay within the limit.
        $balance = CanteenBalance::where('school_id', $schoolId)->where('student_id', $student->id)->first();
        if ($balance && $balance->daily_limit !== null) {
            $spentToday = (float) CanteenOrder::where('student_id', $student->id)
                ->where('charged', true)
                ->whereDate('created_at', now()->toDateString())
                ->sum('total');
            if ($spentToday + $total > (float) $balance->daily_limit) {
                return back()->withInput()->with('error', __('canteen.orders.flash.daily_limit'));
            }
        }

        try {
            DB::transaction(function () use ($canteen, $student, $schoolId, $lines, $total, $data) {
                $order = CanteenOrder::create([
                    'school_id' => $schoolId,
                    'canteen_id' => $canteen->id,
                    'student_id' => $student->id,
                    'status' => 'new',
                    'total' => $total,
                    'charged' => false,
                    'note' => $data['note'] ?? null,
                    'placed_by' => auth()->id(),
                ]);
                foreach ($lines as $l) {
                    $order->items()->create([
                        'canteen_product_id' => $l['product']->id,
                        'product_name' => $l['product']->name,
                        'unit_price' => $l['product']->price,
                        'quantity' => $l['qty'],
                        'line_total' => $l['line_total'],
                    ]);
                }
                // Charge the balance (throws if insufficient — rolls back the whole order).
                $this->balances->apply($student->id, $schoolId, 'deduct', $total, __('canteen.orders.charge_note', ['id' => $order->id]), 'order', auth()->id());
                $order->update(['charged' => true]);
            });
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', __('canteen.orders.flash.insufficient'));
        }

        return redirect()->route('admin.canteen-orders.index')->with('status', __('canteen.orders.flash.created'));
    }

    public function show(int $id): View
    {
        $order = $this->findOrder($id);
        $order->load('items', 'canteen', 'student');

        return view('admin.canteens.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $order = $this->findOrder($id);
        $data = $request->validate(['status' => ['required', Rule::in(CanteenOrder::STATUSES)]]);
        $target = $data['status'];

        if (! $order->canTransitionTo($target)) {
            return back()->with('error', __('canteen.orders.flash.bad_transition'));
        }

        if ($target === 'cancelled') {
            return $this->cancel($order);
        }

        $order->update(['status' => $target]);

        return back()->with('status', __('canteen.orders.flash.status_updated'));
    }

    private function cancel(CanteenOrder $order): RedirectResponse
    {
        DB::transaction(function () use ($order) {
            // Refund only if the balance was actually charged — never double-refund.
            if ($order->charged) {
                $this->balances->apply($order->student_id, $order->school_id, 'add', (float) $order->total, __('canteen.orders.refund_note', ['id' => $order->id]), 'refund', auth()->id());
                $order->charged = false;
            }
            $order->status = 'cancelled';
            $order->save();
        });

        return back()->with('status', __('canteen.orders.flash.cancelled'));
    }

    private function findOrder(int $id): CanteenOrder
    {
        $schoolId = $this->activeSchoolId();

        return CanteenOrder::query()
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->whereKey($id)
            ->firstOrFail();
    }
}
