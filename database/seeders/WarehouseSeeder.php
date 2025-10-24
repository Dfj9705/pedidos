<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::insert([
            [
                'name' => 'Bodega Principal',
                'code' => 'MAIN',
                'is_route' => false,
                'latitude' => 14.6349150,
                'longitude' => -90.5068820,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Inventario en Ruta',
                'code' => 'ROUTE',
                'is_route' => true,
                'latitude' => 14.6698450,
                'longitude' => -90.5513120,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
