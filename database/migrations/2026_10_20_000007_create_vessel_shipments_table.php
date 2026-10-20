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
        Schema::create('vessel_shipments', function (Blueprint $table) {
            $table->id();
            $table->date('departure_date')->nullable();
            $table->date('arrival_date')->nullable();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->decimal('load_volume', 12, 2)->default(0);
            $table->decimal('unload_volume', 12, 2)->default(0);
            $table->decimal('volume_diff', 12, 2)->default(0);
            $table->decimal('shrinkage_percent', 8, 4)->default(0);
            $table->string('lab_result')->nullable();
            $table->string('document_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessel_shipments');
    }
};
