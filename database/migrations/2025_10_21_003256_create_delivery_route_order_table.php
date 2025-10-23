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
        Schema::create('delivery_route_order', function (Blueprint $table) {
            $table->foreignId('delivery_route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('delivered_by')->nullable()->references('id')->on('users')->nullOnDelete();

            $table->primary(['delivery_route_id', 'order_id']);
            $table->index(['delivery_route_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_route_order');
    }
};
