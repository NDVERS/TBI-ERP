<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with real operational data from TBI - ERP.xlsx.
     */
    public function run(): void
    {
        $now = now();

        // 1. Seed Users (5 roles for RBAC testing)
        $users = [
            [
                'name' => 'Admin TBI',
                'email' => 'admin@tbi.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
            [
                'name' => 'Kasir Logistik',
                'email' => 'kasir@tbi.com',
                'password' => Hash::make('password'),
                'role' => 'kasir_logistik',
            ],
            [
                'name' => 'Petugas Gudang',
                'email' => 'gudang@tbi.com',
                'password' => Hash::make('password'),
                'role' => 'petugas_gudang',
            ],
            [
                'name' => 'Operator Batching Plant',
                'email' => 'operator@tbi.com',
                'password' => Hash::make('password'),
                'role' => 'operator_produksi',
            ],
            [
                'name' => 'Surveyor Lapangan',
                'email' => 'surveyor@tbi.com',
                'password' => Hash::make('password'),
                'role' => 'surveyor_opname',
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                array_merge($user, [
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }

        // 2. Seed 10 Material Utama (Sheet: Data Master)
        $materials = [
            [
                'name' => 'Semen',
                'unit' => 'Kg',
                'density_kg_m3' => 1000.00,
                'initial_stock_kg' => 33739750.00,
                'notes' => 'Semen Portland / OPC',
            ],
            [
                'name' => 'Pasir Palu',
                'unit' => 'Kg',
                'density_kg_m3' => 1658.00,
                'initial_stock_kg' => 1149839.58,
                'notes' => 'Pasir Palu (River & M-Pasir)',
            ],
            [
                'name' => 'Pasir Lariang',
                'unit' => 'Kg',
                'density_kg_m3' => 1701.00,
                'initial_stock_kg' => 6613.39,
                'notes' => 'Pasir Lariang',
            ],
            [
                'name' => 'Pasir Lokal',
                'unit' => 'Kg',
                'density_kg_m3' => 1379.00,
                'initial_stock_kg' => 58287.57,
                'notes' => 'Pasir Lokal Halus',
            ],
            [
                'name' => 'Pasir Tibo',
                'unit' => 'Kg',
                'density_kg_m3' => 1705.00,
                'initial_stock_kg' => 0.00,
                'notes' => 'Pasir Tibo',
            ],
            [
                'name' => 'Batu Besar 2/3',
                'unit' => 'Kg',
                'density_kg_m3' => 1536.00,
                'initial_stock_kg' => 1560754.18,
                'notes' => 'Split 2/3 (20-30 mm)',
            ],
            [
                'name' => 'Batu Kecil 1/2',
                'unit' => 'Kg',
                'density_kg_m3' => 1517.00,
                'initial_stock_kg' => 2397612.43,
                'notes' => 'Split 1/2 (10-20 mm) SPLIT 1/2 1528',
            ],
            [
                'name' => 'Air',
                'unit' => 'Kg',
                'density_kg_m3' => 1000.00,
                'initial_stock_kg' => 5000000.00,
                'notes' => 'Air Produksi Batching Plant',
            ],
            [
                'name' => 'Additive',
                'unit' => 'Kg',
                'density_kg_m3' => 1172.00,
                'initial_stock_kg' => 4243.75,
                'notes' => 'Admixture SNF+TN+HJ / Polymer',
            ],
            [
                'name' => 'Dexlite',
                'unit' => 'Liter',
                'density_kg_m3' => 840.00,
                'initial_stock_kg' => 1234.50,
                'notes' => 'BBM Solar Dexlite untuk Armada & Genset',
            ],
        ];

        foreach ($materials as $material) {
            DB::table('materials')->updateOrInsert(
                ['name' => $material['name']],
                array_merge($material, [
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }

        // 3. Seed 28 Suppliers (Sheet: Data Master)
        $suppliers = [
            ['name' => 'WAN SHANG( CONTAINER DARI CHINA)', 'material_type' => 'ADMIXTURE SNF+TN+HJ', 'lead_time_days' => 45, 'notes' => 'Import Polymer'],
            ['name' => 'PT. BALQIS REZA REVOLO', 'material_type' => 'M-PASIR', 'lead_time_days' => 15, 'notes' => null],
            ['name' => 'PT. BALQIS REZA REVOLO - SPLIT 1-2', 'material_type' => 'SPLIT 1-2', 'lead_time_days' => 15, 'notes' => null],
            ['name' => 'PT. BALQIS REZA REVOLO - SPLIT 2-3', 'material_type' => 'SPLIT 2-3', 'lead_time_days' => 15, 'notes' => null],
            ['name' => 'PT KAIROS', 'material_type' => 'PASIR LARIANG', 'lead_time_days' => 15, 'notes' => null],
            ['name' => 'PT PUTRA LABUAN SULAWESI', 'material_type' => 'Palu pasir (river pasir mix M-pasir)', 'lead_time_days' => 15, 'notes' => null],
            ['name' => 'PT PUTRA LABUAN SULAWESI - SPLIT 1-2', 'material_type' => 'SPLIT 1-2', 'lead_time_days' => 15, 'notes' => null],
            ['name' => 'PT PUTRA LABUAN SULAWESI - SPLIT 2-3', 'material_type' => 'SPLIT 2-3', 'lead_time_days' => 15, 'notes' => null],
            ['name' => 'PT BANGUN MANUNGGAL', 'material_type' => 'BATU KAPUR', 'lead_time_days' => 25, 'notes' => null],
            ['name' => 'PT. BINTANG MANUNGGAL PERSADA', 'material_type' => 'SPLIT 1-2', 'lead_time_days' => 15, 'notes' => null],
            ['name' => 'PT. BINTANG MANUNGGAL PERSADA - SPLIT 2-3', 'material_type' => 'SPLIT 2-3', 'lead_time_days' => 15, 'notes' => null],
            ['name' => 'CV SUMBER BERKAT MATERIAL', 'material_type' => 'SPLIT 1-2', 'lead_time_days' => 2, 'notes' => null],
            ['name' => 'CV SUMBER BERKAT MATERIAL - PASIR LARIANG', 'material_type' => 'PASIR LARIANG', 'lead_time_days' => 2, 'notes' => null],
            ['name' => 'PT BORNEO PRIMA MATERIAL', 'material_type' => 'PASIR TIBO', 'lead_time_days' => 2, 'notes' => null],
            ['name' => 'UD MUDA BERKARYA', 'material_type' => 'PASIR MAHAKAM', 'lead_time_days' => 1, 'notes' => null],
            ['name' => 'UD NURHAYATI', 'material_type' => 'PASIR MAHAKAM', 'lead_time_days' => 1, 'notes' => null],
            ['name' => 'PT KIKO ENERGY', 'material_type' => 'BATU KAPUR', 'lead_time_days' => 3, 'notes' => null],
            ['name' => 'PT. ANANDHITA BARA BORNEO', 'material_type' => 'SEMEN OPC CONCH', 'lead_time_days' => 3, 'notes' => null],
            ['name' => 'PT. VANGER MAKMUR MANDIRI', 'material_type' => 'SEMEN OPC SINGA MERAH', 'lead_time_days' => 2, 'notes' => null],
            ['name' => 'CV SUKSES JAYA ABADI', 'material_type' => 'SEMEN OPC SINGA MERAH', 'lead_time_days' => 2, 'notes' => null],
            ['name' => 'PT. VANGER MAKMUR MANDIRI - PCC', 'material_type' => 'SEMEN PCC SINGA MERAH', 'lead_time_days' => 2, 'notes' => null],
            ['name' => 'PT. DEWADARU KARYAUTAMA JAYA', 'material_type' => 'SEMEN PCC MERAH PUTIH', 'lead_time_days' => 2, 'notes' => null],
            ['name' => 'PT WWRC INDONESIA', 'material_type' => 'ADMIXTURE POLYMER', 'lead_time_days' => 20, 'notes' => null],
            ['name' => 'PT ANNABELLA ENERGY INDONESIA', 'material_type' => 'PASIR SILIKA', 'lead_time_days' => 3, 'notes' => null],
            ['name' => 'PT. KEMASINDO NIAGA SENTOSA', 'material_type' => 'PACKAGING', 'lead_time_days' => 40, 'notes' => null],
            ['name' => 'CV BAJA RAYA MAKMUR - BETONESER 6', 'material_type' => 'BETONESER 6', 'lead_time_days' => 2, 'notes' => null],
            ['name' => 'CV BAJA RAYA MAKMUR - BETONESER 8', 'material_type' => 'BETONESER 8', 'lead_time_days' => 2, 'notes' => null],
            ['name' => 'PT. SOBUTE GLOBAL INDONESIA', 'material_type' => 'ADMIXTURE', 'lead_time_days' => 7, 'notes' => null],
        ];

        foreach ($suppliers as $supplier) {
            DB::table('suppliers')->updateOrInsert(
                ['name' => $supplier['name']],
                array_merge($supplier, [
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }

        // 4. Seed Customers (Sheet: Sales Order & Produksi)
        $customers = [
            ['name' => 'PT Recta Construction', 'address' => 'Jl Samanhudi Samarinda', 'default_distance_km' => 15.00],
            ['name' => 'SAHRUL', 'address' => 'Jl Poros Samarinda Bontang', 'default_distance_km' => 20.00],
            ['name' => 'SUYANTO', 'address' => 'Jl AW Syachranie', 'default_distance_km' => 10.00],
            ['name' => 'Bpk Eko', 'address' => 'Unmul Samarinda', 'default_distance_km' => 12.00],
            ['name' => 'CHI CHI JAYA', 'address' => 'Pemakaman Khusnul Khatimah', 'default_distance_km' => 8.00],
            ['name' => 'Bpk Daniel Limotto', 'address' => 'Loa Bakung', 'default_distance_km' => 10.00],
            ['name' => 'PT Modding Jaya Marine', 'address' => 'Tanjung Laung', 'default_distance_km' => 25.00],
            ['name' => 'Yayasan Dharma Bakti', 'address' => 'Jl Poros Samarinda Bontang', 'default_distance_km' => 18.00],
            ['name' => 'Cv Marine Teknik', 'address' => 'Tanjung Laung', 'default_distance_km' => 25.00],
            ['name' => 'PT Kukar Mandiri Shipyard', 'address' => 'Tanjung Laung', 'default_distance_km' => 25.00],
            ['name' => 'Bpk Eric Tedjo', 'address' => 'Mugirejo Samarinda', 'default_distance_km' => 14.00],
            ['name' => 'Pak Puguh', 'address' => 'Jl Bung Tomo', 'default_distance_km' => 7.00],
            ['name' => 'Bpk M Fasikhul Lisan', 'address' => 'Sungai Karang Mumus', 'default_distance_km' => 11.00],
            ['name' => 'CV CILEBAS LESTARI TEKNINDO', 'address' => 'JL Peti Kemas Loa Bakung', 'default_distance_km' => 12.00],
            ['name' => 'Bpk Suntoro', 'address' => 'Loa Janan', 'default_distance_km' => 16.00],
            ['name' => 'Bpk Andi', 'address' => 'Jl AWS Samarinda', 'default_distance_km' => 10.00],
            ['name' => 'PT Masabaru Guna Persada', 'address' => 'JL Peti Kemas Loa Bakung', 'default_distance_km' => 12.00],
            ['name' => 'Bpk Abdul Rahman', 'address' => 'Jl Teuku Umar', 'default_distance_km' => 9.00],
            ['name' => 'UD Milono', 'address' => 'Jl Ringroad 2', 'default_distance_km' => 13.00],
            ['name' => 'Bpk Hendra Susanto', 'address' => 'Polder Samarinda', 'default_distance_km' => 8.00],
            ['name' => 'Bpk Fendi', 'address' => 'Jl Gerilya Samarinda', 'default_distance_km' => 9.00],
        ];

        foreach ($customers as $customer) {
            DB::table('customers')->updateOrInsert(
                ['name' => $customer['name']],
                array_merge($customer, [
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }

        // 5. Seed 24 Master Resep Job Mix (K100 s/d K350, B0 & FC35NCPC dari Sheet Data Master)
        $jobMixes = [
            [
                'mutu_code' => 'K100 / C',
                'semen_kg' => 187.00,
                'pasir_palu_kg' => 506.00,
                'pasir_lokal_kg' => 492.00,
                'split_1_2_kg' => 1060.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 3.18,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K100 / R',
                'semen_kg' => 187.00,
                'pasir_palu_kg' => 506.00,
                'pasir_lokal_kg' => 492.00,
                'split_1_2_kg' => 424.00,
                'split_2_3_kg' => 636.00,
                'additive_liter' => 3.18,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K125 / C',
                'semen_kg' => 202.00,
                'pasir_palu_kg' => 496.00,
                'pasir_lokal_kg' => 484.00,
                'split_1_2_kg' => 1065.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 3.43,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K125 / R',
                'semen_kg' => 202.00,
                'pasir_palu_kg' => 496.00,
                'pasir_lokal_kg' => 484.00,
                'split_1_2_kg' => 426.00,
                'split_2_3_kg' => 639.00,
                'additive_liter' => 3.43,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K150 / C',
                'semen_kg' => 216.00,
                'pasir_palu_kg' => 488.00,
                'pasir_lokal_kg' => 476.00,
                'split_1_2_kg' => 1070.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 3.67,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K150 / R',
                'semen_kg' => 216.00,
                'pasir_palu_kg' => 488.00,
                'pasir_lokal_kg' => 476.00,
                'split_1_2_kg' => 428.00,
                'split_2_3_kg' => 642.00,
                'additive_liter' => 3.67,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K175 / C',
                'semen_kg' => 230.00,
                'pasir_palu_kg' => 575.00,
                'pasir_lokal_kg' => 374.00,
                'split_1_2_kg' => 1076.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 3.91,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K175 / R',
                'semen_kg' => 230.00,
                'pasir_palu_kg' => 563.00,
                'pasir_lokal_kg' => 366.00,
                'split_1_2_kg' => 439.00,
                'split_2_3_kg' => 658.00,
                'additive_liter' => 3.91,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K200 / C',
                'semen_kg' => 244.00,
                'pasir_palu_kg' => 566.00,
                'pasir_lokal_kg' => 367.00,
                'split_1_2_kg' => 1080.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 4.15,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K200 / R',
                'semen_kg' => 244.00,
                'pasir_palu_kg' => 554.00,
                'pasir_lokal_kg' => 360.00,
                'split_1_2_kg' => 440.00,
                'split_2_3_kg' => 660.00,
                'additive_liter' => 4.15,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K225 / C',
                'semen_kg' => 259.00,
                'pasir_palu_kg' => 315.00,
                'pasir_lokal_kg' => 315.00,
                'split_1_2_kg' => 1086.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 4.40,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K225 / R',
                'semen_kg' => 259.00,
                'pasir_palu_kg' => 309.00,
                'pasir_lokal_kg' => 309.00,
                'split_1_2_kg' => 440.00,
                'split_2_3_kg' => 663.00,
                'additive_liter' => 4.40,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K250 / C',
                'semen_kg' => 280.00,
                'pasir_palu_kg' => 677.00,
                'pasir_lokal_kg' => 220.00,
                'split_1_2_kg' => 1090.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 4.76,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K250 / R',
                'semen_kg' => 280.00,
                'pasir_palu_kg' => 662.00,
                'pasir_lokal_kg' => 215.00,
                'split_1_2_kg' => 444.00,
                'split_2_3_kg' => 666.00,
                'additive_liter' => 4.76,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K275 / C',
                'semen_kg' => 300.00,
                'pasir_palu_kg' => 660.00,
                'pasir_lokal_kg' => 215.00,
                'split_1_2_kg' => 1092.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 5.10,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K275 / R',
                'semen_kg' => 300.00,
                'pasir_palu_kg' => 645.00,
                'pasir_lokal_kg' => 210.00,
                'split_1_2_kg' => 445.00,
                'split_2_3_kg' => 668.00,
                'additive_liter' => 5.10,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K300 / C',
                'semen_kg' => 320.00,
                'pasir_palu_kg' => 648.00,
                'pasir_lokal_kg' => 210.00,
                'split_1_2_kg' => 1095.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 5.44,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K300 / R',
                'semen_kg' => 320.00,
                'pasir_palu_kg' => 633.00,
                'pasir_lokal_kg' => 206.00,
                'split_1_2_kg' => 446.00,
                'split_2_3_kg' => 669.00,
                'additive_liter' => 5.44,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K325 / C',
                'semen_kg' => 335.00,
                'pasir_palu_kg' => 655.00,
                'pasir_lokal_kg' => 185.00,
                'split_1_2_kg' => 1098.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 5.70,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K325 / R',
                'semen_kg' => 335.00,
                'pasir_palu_kg' => 640.00,
                'pasir_lokal_kg' => 180.00,
                'split_1_2_kg' => 447.00,
                'split_2_3_kg' => 670.00,
                'additive_liter' => 5.70,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K350 / C',
                'semen_kg' => 350.00,
                'pasir_palu_kg' => 667.00,
                'pasir_lokal_kg' => 162.00,
                'split_1_2_kg' => 1100.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 5.95,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K350 / R',
                'semen_kg' => 350.00,
                'pasir_palu_kg' => 651.00,
                'pasir_lokal_kg' => 159.00,
                'split_1_2_kg' => 448.00,
                'split_2_3_kg' => 671.00,
                'additive_liter' => 5.95,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'B0',
                'semen_kg' => 150.00,
                'pasir_palu_kg' => 520.00,
                'pasir_lokal_kg' => 510.00,
                'split_1_2_kg' => 1050.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 2.50,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'FC35NCPC',
                'semen_kg' => 380.00,
                'pasir_palu_kg' => 650.00,
                'pasir_lokal_kg' => 130.00,
                'split_1_2_kg' => 1110.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 6.46,
                'waste_factor' => 1.0500,
            ],
        ];

        foreach ($jobMixes as $jobMix) {
            DB::table('job_mixes')->updateOrInsert(
                ['mutu_code' => $jobMix['mutu_code']],
                array_merge($jobMix, [
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }
    }
}
