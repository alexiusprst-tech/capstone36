<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;

class StockLedger
{
    public static function record(Product $product, int $before, int $after, ?User $actor, string $type, ?string $referenceType = null, ?string $referenceId = null, string $reason = ''): InventoryMovement
    {
        return InventoryMovement::create([
            'product_id' => $product->id, 'actor_id' => $actor?->id,
            'type' => $type, 'reference_type' => $referenceType, 'reference_id' => $referenceId,
            'delta' => $after - $before, 'before_stock' => $before, 'after_stock' => $after,
            'sku_snapshot' => $product->sku, 'name_snapshot' => $product->name, 'reason' => $reason,
        ]);
    }
}
