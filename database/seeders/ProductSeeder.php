<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();
        $brands = Brand::all();

        $items = [
            ['Coca-Cola 600ml', 6.00, 8.00],
            ['Pepsi 600ml', 5.50, 7.50],
            ['Galletas Chiky', 2.00, 3.50],
            ['Leche Dos Pinos 1L', 8.00, 10.00],
            ['Pan Blanco Bimbo', 7.00, 9.50],
        ];

        foreach ($items as $i) {
            Product::create([
                'sku' => Str::slug($i[0]) . '-' . Str::random(4),
                'name' => $i[0],
                'description' => $i[0],
                'cost' => $i[1],
                'price' => $i[2],
                'category_id' => $categories->random()->id ?? null,
                'brand_id' => $brands->random()->id ?? null,
                'is_active' => true,
            ]);
        }
    }
}
