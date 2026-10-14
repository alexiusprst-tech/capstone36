<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpjProject extends Model
{
    protected $fillable = [
        'project_id',
        'project_name',
        'description',
        'customer_name',
        'customer_code',
        'marketing_name',
        'company_name',
        'activity_name',
        'activity_detail',
        'account_code',
        'fiscal_year',
        'funding_source',
        'category',
        'budget_ceiling',
        'start_date',
        'end_date',
        'project_value',
        'invoice_value',
        'invoice_paid',
        'pic_name',
        'pic_title',
        'pic_nip',
        'city_signature',
        'status',
        'director_approval',
        'warehouse_status',
        'warehouse_data',
        'finance_status',
        'finance_data',
        'items',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'director_approval' => 'array',
            'warehouse_data' => 'array',
            'finance_data' => 'array',
            'items' => 'array',
            'metadata' => 'array',
            'budget_ceiling' => 'float',
            'project_value' => 'float',
            'invoice_value' => 'float',
            'invoice_paid' => 'float',
        ];
    }

    /**
     * Relasi ke 15 dokumen SPJ
     */
    public function documents()
    {
        return $this->hasMany(SpjDocument::class, 'project_id', 'project_id')->orderBy('doc_order');
    }
}
