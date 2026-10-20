<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('materials')) {
            $now = now();

            // 1. Ensure Additive (Kg) exists as raw material
            $additiveExists = DB::table('materials')->where('name', 'Additive')->exists();
            if (! $additiveExists) {
                DB::table('materials')->insert([
                    'name' => 'Additive',
                    'unit' => 'Kg',
                    'density_kg_m3' => 1172.00,
                    'initial_stock_kg' => 4243.75,
                    'notes' => 'Bahan Kimia Mentah Admixture (Admixture Raw Material)',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // 2. Ensure SBTJM2 (Liter) exists as internal lab processed material
            $sbtjm2Exists = DB::table('materials')->where('name', 'SBTJM2')->exists();
            if (! $sbtjm2Exists) {
                DB::table('materials')->insert([
                    'name' => 'SBTJM2',
                    'unit' => 'Liter',
                    'density_kg_m3' => 1172.00,
                    'initial_stock_kg' => 4243.75,
                    'notes' => 'Admixture Siap Pakai Produksi Lab TBI (SBTJM2)',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('materials')->where('name', 'SBTJM2')->update([
                    'unit' => 'Liter',
                    'notes' => 'Admixture Siap Pakai Produksi Lab TBI (SBTJM2)',
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('materials')) {
            DB::table('materials')->where('name', 'Additive')->delete();
        }
    }
};
