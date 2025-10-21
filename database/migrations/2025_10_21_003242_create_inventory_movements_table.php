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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->enum('type', ['in', 'out', 'transfer', 'adjustment']);
            $table->foreignId('origin_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('target_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete(); // salida por pedido
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('moved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['type', 'moved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
