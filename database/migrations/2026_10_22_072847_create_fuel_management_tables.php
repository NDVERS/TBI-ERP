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
        Schema::create('fuel_receives', function (Blueprint $table) {
            $table->id();
            $table->date('receive_date');
            $table->string('supplier_name', 150)->default('PT Pertamina Patra Niaga');
            $table->string('ri_do_number', 100)->nullable();
            $table->string('vehicle_no', 30)->nullable();
            $table->decimal('volume_liters', 12, 2);
            $table->decimal('initial_sounding_cm', 8, 2)->nullable();
            $table->decimal('final_sounding_cm', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('lubricant_transfers', function (Blueprint $table) {
            $table->id();
            $table->date('transfer_date');
            $table->enum('transfer_type', ['transfer_in', 'transfer_out'])->default('transfer_in');
            $table->enum('oil_type', ['engine_oil', 'hydraulic_oil'])->default('engine_oil');
            $table->decimal('liters', 10, 2);
            $table->string('source_plant', 100)->nullable();
            $table->string('destination_plant', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lubricant_transfers');
        Schema::dropIfExists('fuel_receives');
    }
};
