<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryMovementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:OPENING,ADJUSTMENT,RECEIPT',
            'product_id' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
        $query = InventoryMovement::with('actor:id,name');
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(fn ($q) => $q->where('sku_snapshot', 'like', "%{$search}%")
                ->orWhere('name_snapshot', 'like', "%{$search}%")
                ->orWhere('reference_id', 'like', "%{$search}%"));
        }
        foreach (['type', 'product_id'] as $filter) {
            if (! empty($validated[$filter])) {
                $query->where($filter, $validated[$filter]);
            }
        }
        $rows = $query->orderByDesc('id')->paginate($validated['per_page'] ?? 25);

        return response()->json(['success' => true, 'data' => $rows->items(), 'meta' => [
            'current_page' => $rows->currentPage(), 'last_page' => $rows->lastPage(),
            'per_page' => $rows->perPage(), 'total' => $rows->total(),
        ]]);
    }
}
