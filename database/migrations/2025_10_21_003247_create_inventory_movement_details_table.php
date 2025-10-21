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
        Schema::create('inventory_movement_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_movement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('qty', 14, 4);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->timestamps();
            $table->unique(['inventory_movement_id', 'product_id'], 'inv_mov_prod_uq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movement_details');
    }
};
