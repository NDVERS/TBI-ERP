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
        // 1. Update Material 'Additive' to 'SBTJM2' with unit 'Liter'
        if (Schema::hasTable('materials')) {
            DB::table('materials')
                ->where('name', 'Additive')
                ->update([
                    'name' => 'SBTJM2',
                    'unit' => 'Liter',
                    'notes' => 'Admixture SNF+TN+HJ / Polymer (SBTJM2)',
                    'updated_at' => now(),
                ]);
        }

        // 2. Normalize and Deduplicate Suppliers
        if (Schema::hasTable('suppliers')) {
            // First, strip common suffixes
            $replacements = [
                'PT. BALQIS REZA REVOLO - SPLIT 1-2' => 'PT. BALQIS REZA REVOLO',
                'PT. BALQIS REZA REVOLO - SPLIT 2-3' => 'PT. BALQIS REZA REVOLO',
                'PT PUTRA LABUAN SULAWESI - SPLIT 1-2' => 'PT PUTRA LABUAN SULAWESI',
                'PT PUTRA LABUAN SULAWESI - SPLIT 2-3' => 'PT PUTRA LABUAN SULAWESI',
                'PT. BINTANG MANUNGGAL PERSADA - SPLIT 2-3' => 'PT. BINTANG MANUNGGAL PERSADA',
                'PT. BINTANG MANUNGGAL PERSADA - SPLIT 1-2' => 'PT. BINTANG MANUNGGAL PERSADA',
                'CV SUMBER BERKAT MATERIAL - PASIR LARIANG' => 'CV SUMBER BERKAT MATERIAL',
                'CV SUMBER BERKAT MATERIAL - SPLIT 1-2' => 'CV SUMBER BERKAT MATERIAL',
                'PT. VANGER MAKMUR MANDIRI - PCC' => 'PT. VANGER MAKMUR MANDIRI',
                'CV BAJA RAYA MAKMUR - BETONESER 6' => 'CV BAJA RAYA MAKMUR',
                'CV BAJA RAYA MAKMUR - BETONESER 8' => 'CV BAJA RAYA MAKMUR',
            ];

            foreach ($replacements as $oldName => $newName) {
                DB::table('suppliers')->where('name', $oldName)->update(['name' => $newName]);
            }

            // Deduplicate supplier records by consolidating child foreign keys to the first ID
            $suppliersByName = DB::table('suppliers')->orderBy('id')->get()->groupBy('name');

            foreach ($suppliersByName as $name => $records) {
                if ($records->count() > 1) {
                    $primaryId = $records->first()->id;
                    $duplicateIds = $records->slice(1)->pluck('id')->toArray();

                    if (Schema::hasTable('material_receives') && Schema::hasColumn('material_receives', 'supplier_id')) {
                        DB::table('material_receives')
                            ->whereIn('supplier_id', $duplicateIds)
                            ->update(['supplier_id' => $primaryId]);
                    }

                    if (Schema::hasTable('purchase_orders') && Schema::hasColumn('purchase_orders', 'supplier_id')) {
                        DB::table('purchase_orders')
                            ->whereIn('supplier_id', $duplicateIds)
                            ->update(['supplier_id' => $primaryId]);
                    }

                    DB::table('suppliers')->whereIn('id', $duplicateIds)->delete();
                }
            }

            // Add internal supplier 'TBI Plant' if not present
            $exists = DB::table('suppliers')->where('name', 'TBI Plant')->exists();
            if (! $exists) {
                DB::table('suppliers')->insert([
                    'name' => 'TBI Plant',
                    'material_type' => 'PRODUKSI INTERNAL LAB',
                    'lead_time_days' => 0,
                    'notes' => 'Internal Production / Lab',
                    'created_at' => now(),
                    'updated_at' => now(),
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
            DB::table('materials')
                ->where('name', 'SBTJM2')
                ->update([
                    'name' => 'Additive',
                    'unit' => 'Kg',
                    'updated_at' => now(),
                ]);
        }
    }
};
