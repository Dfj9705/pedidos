<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Supermercado La Esperanza',
                'address' => '6a Avenida 7-59, Zona 1, Ciudad de Guatemala',
                'latitude' => 14.6407200,
                'longitude' => -90.5132700,
            ],
            [
                'name' => 'Tienda El Ahorro',
                'address' => '18 Calle 24-45, Zona 10, Ciudad de Guatemala',
                'latitude' => 14.5824300,
                'longitude' => -90.5129400,
            ],
            [
                'name' => 'Mini Market Don Jorge',
                'address' => '4a Avenida 15-20, Zona 3, Quetzaltenango',
                'latitude' => 14.8452200,
                'longitude' => -91.5174700,
            ],
            [
                'name' => 'Distribuidora El Sol',
                'address' => '2a Calle A 13-45, Zona 1, Mixco',
                'latitude' => 14.6335600,
                'longitude' => -90.6071800,
            ],
            [
                'name' => 'Punto Express San Rafael',
                'address' => '5a Avenida 6-30, San Rafael Las Flores, Santa Rosa',
                'latitude' => 14.4815200,
                'longitude' => -90.1638500,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create([
                'name' => $customer['name'],
                'phone' => '555-' . rand(1000, 9999),
                'address' => $customer['address'],
                'email' => Str::slug($customer['name']) . '@correo.com',
                'latitude' => $customer['latitude'],
                'longitude' => $customer['longitude'],
            ]);
        }
    }
}
