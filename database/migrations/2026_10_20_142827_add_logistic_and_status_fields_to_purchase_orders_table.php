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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('material_type_input')->nullable()->after('specification');
            $table->string('unit')->default('m³')->after('qty_ordered');
            $table->string('loading_jetty')->nullable()->after('vessel_name');
            $table->string('unloading_jetty')->nullable()->after('loading_jetty');
            $table->date('est_departure_date')->nullable()->after('unloading_jetty');
            $table->date('est_arrival_date')->nullable()->after('est_departure_date');
            $table->string('status')->default('Menunggu Konfirmasi')->after('est_arrival_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'material_type_input',
                'unit',
                'loading_jetty',
                'unloading_jetty',
                'est_departure_date',
                'est_arrival_date',
                'status',
            ]);
        });
    }
};
