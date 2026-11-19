<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'contact_person', 'phone', 'email', 'address', 'city', 'profile', 'created_by'];

    protected function casts(): array
    {
        return ['profile' => 'array', 'version' => 'integer'];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function present(): array
    {
        return [...($this->profile ?? []), 'id' => $this->id, 'code' => $this->code,
            'name' => $this->name, 'contactPerson' => $this->contact_person,
            'phone' => $this->phone, 'email' => $this->email ?? '', 'address' => $this->address,
            'city' => $this->city ?? '', 'version' => $this->version, 'serverManaged' => true,
            'createdAt' => $this->created_at?->toDateString()];
    }
}
