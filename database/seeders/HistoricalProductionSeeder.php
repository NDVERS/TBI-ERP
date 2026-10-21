<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class HistoricalProductionSeeder extends Seeder
{
    /**
     * Run the database seeds for historical production dockets.
     */
    public function run(): void
    {
        $jsonPath = database_path('data/historical_production_dockets.json');
        if (! File::exists($jsonPath)) {
            $this->command?->warn("File $jsonPath tidak ditemukan.");

            return;
        }

        $records = json_decode(File::get($jsonPath), true);
        if (empty($records) || ! is_array($records)) {
            $this->command?->warn("Data di $jsonPath kosong.");

            return;
        }

        $defaultUserId = User::where('role', 'operator_produksi')->value('id')
            ?? User::where('role', 'admin')->value('id')
            ?? 1;

        $now = now();

        // 1. Pastikan seluruh customer unik dari riwayat produksi terdaftar
        $uniqueCustomers = [];
        foreach ($records as $r) {
            $cName = trim($r['customer_name'] ?? '');
            if (! empty($cName) && ! isset($uniqueCustomers[$cName])) {
                $uniqueCustomers[$cName] = trim($r['address'] ?? '');
            }
        }

        foreach ($uniqueCustomers as $name => $address) {
            Customer::firstOrCreate(
                ['name' => $name],
                [
                    'address' => $address ?: 'Samarinda, Kalimantan Timur',
                    'default_distance_km' => 15.00,
                ]
            );
        }

        // 2. Insert dockets in chunks without overwriting existing entries that have actual batch materials
        $chunks = array_chunk($records, 100);

        foreach ($chunks as $chunk) {
            $insertBatch = [];

            foreach ($chunk as $docketData) {
                $docketNo = $docketData['docket_no'];

                // Cek apakah docket sudah ada di database
                $existing = DB::table('production_dockets')->where('docket_no', $docketNo)->first();

                if ($existing) {
                    // Jangan timpa jika data yang sudah ada memiliki pemakaian material aktual
                    continue;
                }

                $insertBatch[] = [
                    'docket_no' => $docketNo,
                    'production_date' => $docketData['production_date'],
                    'time_load' => $docketData['time_load'],
                    'truck_no' => $docketData['truck_no'],
                    'customer_name' => $docketData['customer_name'],
                    'address' => $docketData['address'],
                    'mutu_code' => $docketData['mutu_code'],
                    'volume_m3' => $docketData['volume_m3'],
                    'daily_cumulative_m3' => $docketData['daily_cumulative_m3'],
                    'load_time_diff' => $docketData['load_time_diff'],
                    // Nilai komposisi material kosong (0.00) sesuai permintaan
                    'silo1_tar' => 0.00,
                    'silo1_act' => 0.00,
                    'silo2_tar' => 0.00,
                    'silo2_act' => 0.00,
                    'pasir_palu_tar' => 0.00,
                    'pasir_palu_act' => 0.00,
                    'pasir_lokal_tar' => 0.00,
                    'pasir_lokal_act' => 0.00,
                    'split_1_2_tar' => 0.00,
                    'split_1_2_act' => 0.00,
                    'split_2_3_tar' => 0.00,
                    'split_2_3_act' => 0.00,
                    'water_tar' => 0.00,
                    'water_act' => 0.00,
                    'admixture_tar' => 0.00,
                    'admixture_act' => 0.00,
                    'created_by' => $defaultUserId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($insertBatch)) {
                DB::table('production_dockets')->insert($insertBatch);
            }
        }
    }
}
