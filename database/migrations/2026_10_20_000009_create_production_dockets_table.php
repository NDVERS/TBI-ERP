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
        Schema::create('production_dockets', function (Blueprint $table) {
            $table->id();
            $table->string('docket_no')->unique();
            $table->date('production_date');
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->string('truck_no');
            $table->string('customer_name');
            $table->text('address')->nullable();
            $table->string('mutu_code');
            $table->time('time_load');
            $table->decimal('volume_m3', 10, 2);
            $table->decimal('daily_cumulative_m3', 10, 2)->default(0);
            $table->string('load_time_diff')->nullable();

            // Pemakaian Target (_tar) & Aktual (_act) (DECIMAL(12,2))
            $table->decimal('silo1_tar', 12, 2)->default(0);
            $table->decimal('silo1_act', 12, 2)->default(0);
            $table->decimal('silo2_tar', 12, 2)->default(0);
            $table->decimal('silo2_act', 12, 2)->default(0);
            $table->decimal('pasir_palu_tar', 12, 2)->default(0);
            $table->decimal('pasir_palu_act', 12, 2)->default(0);
            $table->decimal('pasir_lokal_tar', 12, 2)->default(0);
            $table->decimal('pasir_lokal_act', 12, 2)->default(0);
            $table->decimal('split_1_2_tar', 12, 2)->default(0);
            $table->decimal('split_1_2_act', 12, 2)->default(0);
            $table->decimal('split_2_3_tar', 12, 2)->default(0);
            $table->decimal('split_2_3_act', 12, 2)->default(0);
            $table->decimal('water_tar', 12, 2)->default(0);
            $table->decimal('water_act', 12, 2)->default(0);
            $table->decimal('admixture_tar', 12, 2)->default(0);
            $table->decimal('admixture_act', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_dockets');
    }
};
