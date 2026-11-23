<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InvoiceDelivery;
use App\Models\SalesOrder;
use App\Services\InvoiceDeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceDeliveryController extends Controller
{
    public function __construct(private InvoiceDeliveryService $deliveries) {}

    public function show(SalesOrder $order): JsonResponse
    {
        return response()->json(['data' => $this->deliveries->summary($order)]);
    }

    public function store(Request $request, SalesOrder $order): JsonResponse
    {
        $token = bin2hex(random_bytes(32));
        $attempt = DB::transaction(function () use ($order, $request, $token) {
            $locked = SalesOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();

            return $this->deliveries->reserve($locked, $request->user()->id, $token);
        }, 5);
        $result = $attempt ? $this->deliveries->deliver($attempt, $token)
            : ['status' => 409, 'message' => 'Pengiriman email invoice sedang berlangsung.'];

        return response()->json(['data' => $this->deliveries->summary($order->fresh()), 'message' => $result['message']], $result['status']);
    }

    public function destroy(SalesOrder $order): JsonResponse
    {
        DB::transaction(function () use ($order) {
            SalesOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            InvoiceDelivery::where('sales_order_id', $order->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
        }, 5);

        return response()->json(['data' => $this->deliveries->summary($order->fresh()), 'message' => 'Akses tautan invoice telah dicabut.']);
    }
}
