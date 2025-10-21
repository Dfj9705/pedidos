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
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Inventario en Ruta',
                'code' => 'ROUTE',
                'is_route' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
