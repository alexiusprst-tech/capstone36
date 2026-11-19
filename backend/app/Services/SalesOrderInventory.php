<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SalesOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesOrderInventory
{
    /** Caller holds the inventory mutex. Rebuild drafts without taking submitted stock. */
    public function reallocateDrafts(?int $preferredOrderId = null): void
    {
        $orders = SalesOrder::where('status', 'Draft')->orderBy('id')->lockForUpdate()->get();
        // Release draft allocations first so the preferred order genuinely receives priority.
        foreach ($orders as $order) {
            $this->release($order);
        }
        $orders = $orders->sortBy(fn ($order) => [$order->id === $preferredOrderId ? 0 : 1, $order->id]);
        foreach ($orders as $order) {
            $this->allocate($order);
        }
    }

    /** Caller holds the order lock in a transaction. Product locks serialize competing orders. */
    public function allocate(SalesOrder $order): void
    {
        $needed = collect($order->items)->groupBy('sku')->map(fn ($items) => $items->sum('qty'));
        $oldIds = DB::table('sales_order_allocations')->where('sales_order_id', $order->id)->pluck('product_id');
        $products = Product::whereIn('sku', $needed->keys())->orWhereIn('id', $oldIds)
            ->orderBy('id')->lockForUpdate()->get();
        $other = DB::table('sales_order_allocations')->whereIn('product_id', $products->pluck('id'))
            ->where('sales_order_id', '!=', $order->id)->groupBy('product_id')
            ->selectRaw('product_id, SUM(qty) as allocated')->pluck('allocated', 'product_id');
        DB::table('sales_order_allocations')->where('sales_order_id', $order->id)->delete();
        foreach ($products as $product) {
            $qty = min((int) ($needed[$product->sku] ?? 0), max(0, $product->stock - ($other[$product->id] ?? 0)));
            if ($qty > 0) {
                DB::table('sales_order_allocations')->insert([
                    'sales_order_id' => $order->id, 'product_id' => $product->id, 'qty' => $qty,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function release(SalesOrder $order): void
    {
        $ids = DB::table('sales_order_allocations')->where('sales_order_id', $order->id)->pluck('product_id');
        Product::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
        DB::table('sales_order_allocations')->where('sales_order_id', $order->id)->delete();
    }

    public function assertReady(SalesOrder $order): void
    {
        $this->allocate($order);
        $summary = $this->summaries(collect([$order]))[$order->id];
        $missing = collect($summary['items'])->where('catalogFound', false)->values();
        if ($missing->isNotEmpty()) {
            $names = $missing->first()['name'];
            if ($missing->count() > 1) {
                $names .= ' dan '.($missing->count() - 1).' lainnya';
            }
            throw ValidationException::withMessages(['items' => "Barang belum terdaftar: {$names}. Pilih ulang di Edit Order."]);
        }
        if ($summary['status'] !== 'Siap Diajukan') {
            $shortages = collect($summary['items'])->where('shortage', '>', 0)
                ->map(fn ($item) => $item['name'].' ('.trim("{$item['shortage']} {$item['unit']}").')')->implode(', ');
            throw ValidationException::withMessages(['items' => "Stok kurang: {$shortages}."]);
        }
    }

    /** Batch reads keep the internal orders list independent of the number of line items. */
    public function summaries(Collection $orders): array
    {
        $skus = $orders->flatMap(fn ($order) => array_column($order->items, 'sku'))->unique();
        $products = Product::whereIn('sku', $skus)->get()->keyBy('sku');
        $allocations = DB::table('sales_order_allocations')->whereIn('product_id', $products->pluck('id'))->get();
        $totals = $allocations->groupBy('product_id')->map(fn ($rows) => $rows->sum('qty'));
        $result = [];
        foreach ($orders as $order) {
            $own = $allocations->where('sales_order_id', $order->id)->keyBy('product_id');
            $items = [];
            foreach (collect($order->items)->groupBy('sku') as $sku => $lines) {
                $product = $products->get($sku);
                $required = $lines->sum('qty');
                $allocated = $product ? (int) ($own[$product->id]->qty ?? 0) : 0;
                $free = $product ? max(0, $product->stock - ($totals[$product->id] ?? 0)) : 0;
                $usable = $product ? min($allocated, max(0, $product->stock - (($totals[$product->id] ?? 0) - $allocated))) : 0;
                $line = $lines->first();
                $items[] = ['sku' => $sku, 'name' => ($line['name'] ?? '') ?: ($product?->name ?? 'Barang dalam pesanan'),
                    'unit' => ($line['unit'] ?? '') ?: ($product?->unit ?? ''), 'catalogFound' => $product !== null,
                    'required' => $required, 'allocated' => $usable,
                    'available' => $free, 'shortage' => max(0, $required - $usable)];
            }
            $result[$order->id] = ['status' => $items !== [] && collect($items)->sum('shortage') === 0 ? 'Siap Diajukan' : 'Menunggu Stok', 'items' => $items];
        }

        return $result;
    }
}
