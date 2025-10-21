<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = ['Coca-Cola', 'Pepsi', 'Nestlé', 'Colgate', 'Bimbo'];

        foreach ($brands as $b) {
            Brand::create(['name' => $b]);
        }
    }
}
