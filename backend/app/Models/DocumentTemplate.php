<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    protected $fillable = [
        'name',
        'code',
        'category',
        'description',
        'file_path',
        'blank_file_path',
        'file_name',
        'file_size',
        'mime_type',
        'type',
        'available_variables',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'available_variables' => 'array',
            'is_active' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    /**
     * Relasi ke dokumen order yang telah dibuat menggunakan template ini.
     */
    public function orderDocuments()
    {
        return $this->hasMany(OrderDocument::class, 'template_id');
    }

    /**
     * Dapatkan path absolut file master template di storage.
     */
    public function getMasterFullPathAttribute(): string
    {
        return storage_path('app/'.$this->file_path);
    }

    /**
     * Dapatkan path absolut file blank template di storage.
     */
    public function getBlankFullPathAttribute(): string
    {
        return storage_path('app/'.($this->blank_file_path ?: $this->file_path));
    }
}
