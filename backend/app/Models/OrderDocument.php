<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDocument extends Model
{
    protected $fillable = [
        'order_id',
        'order_number',
        'template_id',
        'doc_type',
        'doc_number',
        'title',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'status',
        'generated_by',
        'metadata',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'file_size' => 'integer',
        ];
    }

    /**
     * Relasi ke template yang digunakan.
     */
    public function template()
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    /**
     * Relasi ke user pembuat.
     */
    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Dapatkan path absolut file hasil di storage.
     */
    public function getFullPathAttribute(): string
    {
        return storage_path('app/'.$this->file_path);
    }
}
