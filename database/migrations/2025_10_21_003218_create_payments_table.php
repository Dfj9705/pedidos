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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->dateTime('paid_at');
            $table->enum('method', ['cash', 'card', 'transfer', 'other'])->default('cash');
            $table->decimal('amount', 14, 4);
            $table->string('reference', 120)->nullable(); // no. de boleta, etc.
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['paid_at', 'method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
