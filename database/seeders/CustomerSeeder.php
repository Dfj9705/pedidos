<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Supermercado La Esperanza',
            'Tienda El Ahorro',
            'Mini Market Don Jorge',
            'Distribuidora El Sol',
            'Punto Express San Rafael'
        ];

        foreach ($names as $n) {
            Customer::create([
                'name' => $n,
                'phone' => '555-' . rand(1000, 9999),
                'address' => 'Zona ' . rand(1, 10) . ', Ciudad',
                'email' => Str::slug($n) . '@correo.com',
            ]);
        }
    }
}
