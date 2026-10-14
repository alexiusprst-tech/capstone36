<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpjDocument extends Model
{
    protected $fillable = [
        'project_id',
        'doc_order',
        'doc_key',
        'doc_name',
        'is_external',
        'status',
        'is_data_complete',
        'missing_requirements',
        'doc_number',
        'doc_date',
        'file_path',
        'file_name',
        'file_type',
        'version',
        'form_data',
    ];

    protected function casts(): array
    {
        return [
            'is_external' => 'boolean',
            'is_data_complete' => 'boolean',
            'missing_requirements' => 'array',
            'form_data' => 'array',
            'doc_order' => 'integer',
            'version' => 'integer',
        ];
    }

    /**
     * Relasi ke project SPJ
     */
    public function project()
    {
        return $this->belongsTo(SpjProject::class, 'project_id', 'project_id');
    }

    /**
     * Dapatkan path absolut file hasil
     */
    public function getFullPathAttribute(): ?string
    {
        return $this->file_path ? storage_path('app/'.$this->file_path) : null;
    }
}
