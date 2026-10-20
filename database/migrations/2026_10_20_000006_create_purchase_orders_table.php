<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->date('po_date');
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('vessel_name')->nullable();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->string('specification')->nullable();
            $table->decimal('qty_ordered', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('ppn_amount', 15, 2);
            $table->decimal('grand_total', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
