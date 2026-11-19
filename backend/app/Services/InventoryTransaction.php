<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InventoryTransaction
{
    /** All inventory writers take this lock before any order or product locks. */
    public static function run(callable $callback): mixed
    {
        return DB::transaction(function () use ($callback) {
            DB::table('inventory_locks')->where('id', 1)->lockForUpdate()->firstOrFail();

            return $callback();
        }, 5);
    }
}
