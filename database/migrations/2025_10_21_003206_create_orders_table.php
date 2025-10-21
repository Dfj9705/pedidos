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
        Schema::create('orders', function (Blueprint $table) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // quién crea/entrega
                $table->string('code', 40)->unique(); // folio/serie si lo necesitas
                $table->enum('status', ['draft', 'confirmed', 'shipped', 'delivered', 'canceled'])->default('confirmed');
                $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
                $table->dateTime('delivered_at')->nullable();
                $table->decimal('subtotal', 14, 4)->default(0);
                $table->decimal('discount_total', 14, 4)->default(0);
                $table->decimal('tax_total', 14, 4)->default(0);
                $table->decimal('grand_total', 14, 4)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['status', 'payment_status', 'delivered_at']);
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
