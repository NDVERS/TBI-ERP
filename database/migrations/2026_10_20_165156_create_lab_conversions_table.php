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
        Schema::create('lab_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('conversion_no', 50)->unique();
            $table->date('conversion_date');
            $table->decimal('additive_used_kg', 10, 2);
            $table->decimal('sbtjm2_produced_liter', 10, 2);
            $table->string('notes')->nullable();
            $table->foreignId('material_receive_id')->nullable()->constrained('material_receives')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_conversions');
    }
};
