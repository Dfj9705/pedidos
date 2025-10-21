<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Relaciones con catálogos
            $table->foreignId('brand_id')
                ->nullable()
                ->constrained('brands')
                ->nullOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            // Datos básicos del producto
            $table->string('sku', 60)->unique();         // código interno o de fábrica
            $table->string('name', 180);                 // nombre del producto
            $table->text('description')->nullable();     // descripción opcional

            // Información económica
            $table->decimal('cost', 14, 4)->default(0);  // costo unitario
            $table->decimal('price', 14, 4)->default(0); // precio de venta
            $table->decimal('min_stock', 14, 4)->default(0); // mínimo en inventario

            // Estado del producto
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Índices de búsqueda rápida
            $table->index(['name', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
