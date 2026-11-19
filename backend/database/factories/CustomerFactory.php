<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return ['code' => 'CS-'.Str::ulid(), 'name' => 'Customer Uji',
            'contact_person' => 'PIC Uji', 'phone' => '081234567890',
            'email' => 'customer@example.test', 'address' => 'Jalan Pengujian 10',
            'city' => 'JAKARTA TIMUR', 'profile' => ['orgFullName' => 'Customer Uji', 'status' => 'Aktif']];
    }
}
