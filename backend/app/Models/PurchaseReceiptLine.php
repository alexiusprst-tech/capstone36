<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReceiptLine extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['line_index' => 'integer', 'accepted_qty' => 'integer', 'rejected_qty' => 'integer'];
}
