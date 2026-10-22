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
        Schema::create('fleets', function (Blueprint $table) {
            $table->id();
            $table->string('unit_code', 50)->unique(); // TM 01, TM 02, ..., Wheel Loader, Lain-lain
            $table->string('name', 100);
            $table->enum('type', ['truck_mixer', 'wheel_loader', 'others'])->default('truck_mixer');
            $table->string('license_plate', 30)->nullable();
            $table->string('model_specification', 150)->nullable(); // e.g. "Hino 500 (7 m³)", "Bucket 1,8 m³"
            $table->enum('status', ['ready', 'maintenance', 'standby'])->default('ready');
            $table->decimal('current_hm', 10, 2)->default(0);
            $table->decimal('current_km', 10, 2)->nullable();
            $table->decimal('last_service_hm', 10, 2)->default(0);
            $table->date('last_service_date')->nullable();
            $table->decimal('service_interval_hm', 8, 2)->default(500.0);
            $table->integer('service_interval_days')->nullable(); // 30 hari untuk Wheel Loader
            $table->timestamps();
        });

        Schema::create('fleet_daily_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fleet_id')->constrained('fleets')->cascadeOnDelete();
            $table->date('log_date');
            $table->decimal('start_hm', 10, 2)->default(0);
            $table->decimal('end_hm', 10, 2)->default(0);
            $table->decimal('total_hm', 8, 2)->default(0);
            $table->decimal('start_km', 10, 2)->nullable();
            $table->decimal('end_km', 10, 2)->nullable();
            $table->decimal('total_km', 8, 2)->nullable();
            $table->decimal('fuel_liters', 10, 2)->default(0);
            $table->decimal('engine_oil_liters', 8, 2)->default(0);
            $table->decimal('hydraulic_oil_liters', 8, 2)->default(0);
            $table->string('driver_operator_name', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('fleet_maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fleet_id')->constrained('fleets')->cascadeOnDelete();
            $table->date('service_date');
            $table->string('service_type', 50)->default('periodic_500h');
            $table->decimal('hm_at_service', 10, 2);
            $table->string('mechanic_name', 100)->nullable();
            $table->text('description')->nullable();
            $table->text('parts_replaced')->nullable();
            $table->decimal('cost', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_maintenance_records');
        Schema::dropIfExists('fleet_daily_logs');
        Schema::dropIfExists('fleets');
    }
};
