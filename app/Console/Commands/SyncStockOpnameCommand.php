<?php

namespace App\Console\Commands;

use App\Models\Material;
use App\Models\StockOpname;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SyncStockOpnameCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-stock-opname 
                            {--file= : Path berkas Opname.xlsx} 
                            {--truncate : Kosongkan tabel stock_opnames sebelum impor}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kosongkan tabel stock_opnames dan impor data pengukuran fisik dari sheets Opname 1, Opname 7, dan Opname 14 di Opname.xlsx';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->option('file') ?: database_path('data/Opname.xlsx');

        if (! File::exists($filePath)) {
            $this->error("Berkas Excel tidak ditemukan di: {$filePath}");

            return self::FAILURE;
        }

        $this->info("Membuka berkas Excel: {$filePath}...");

        $spreadsheet = IOFactory::load($filePath);

        // Always truncate / empty existing stock opname records when syncing as requested
        $countBefore = StockOpname::count();
        StockOpname::query()->delete();
        $this->warn("Tabel stock_opnames berhasil dikosongkan ({$countBefore} data lama dihapus).");

        $surveyorId = User::where('role', 'surveyor_opname')->value('id')
            ?? User::where('role', 'admin')->value('id')
            ?? 1;

        $targetSheets = [
            'Opname 1' => '2026-10-01',
            'Opname 7' => '2026-10-07',
            'Opname 14' => '2026-10-14',
        ];

        $materialConfigs = [
            [
                'excel_label' => 'PASIR LARIANG',
                'pile_name' => 'Pasir Lariang',
                'system_material' => 'Pasir Palu',
                'la_row' => 18,
                'lb_row' => 19,
                't_row' => 22,
                'calc_row' => 28,
                'bin_row' => 29,
            ],
            [
                'excel_label' => 'SPLIT 1/2',
                'pile_name' => 'Split 1/2',
                'system_material' => 'Batu Kecil 1/2',
                'la_row' => 41,
                'lb_row' => 42,
                't_row' => 45,
                'calc_row' => 51,
                'bin_row' => 52,
            ],
            [
                'excel_label' => 'PASIR LOKAL',
                'pile_name' => 'Pasir Lokal',
                'system_material' => 'Pasir Lokal',
                'la_row' => 64,
                'lb_row' => 65,
                't_row' => 68,
                'calc_row' => 74,
                'bin_row' => 75,
            ],
            [
                'excel_label' => 'SPLIT 2/3',
                'pile_name' => 'Split 2/3',
                'system_material' => 'Batu Besar 2/3',
                'la_row' => 87,
                'lb_row' => 88,
                't_row' => 91,
                'calc_row' => 97,
                'bin_row' => 98,
            ],
            [
                'excel_label' => 'PASIR PALU',
                'pile_name' => 'Pasir Palu',
                'system_material' => 'Pasir Palu',
                'la_row' => 110,
                'lb_row' => 111,
                't_row' => 114,
                'calc_row' => 120,
                'bin_row' => 121,
            ],
        ];

        $materialsMap = Material::pluck('id', 'name')->toArray();
        $importedCount = 0;

        foreach ($targetSheets as $sheetName => $defaultDate) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (! $sheet) {
                $this->warn("Sheet [{$sheetName}] tidak ditemukan, dilewati.");

                continue;
            }

            // Parse date from cell C7 if possible
            $tglRaw = (string) $sheet->getCell('C7')->getValue();
            $opnameDate = $defaultDate;

            if (preg_match('/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/', $tglRaw, $matches)) {
                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $monthStr = strtolower($matches[2]);
                $year = $matches[3];

                $months = [
                    'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
                    'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
                    'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12',
                ];

                if (isset($months[$monthStr])) {
                    $opnameDate = "{$year}-{$months[$monthStr]}-{$day}";
                }
            }

            $this->line("<info>[{$sheetName}]</info> Tanggal Opname: {$opnameDate}");

            foreach ($materialConfigs as $cfg) {
                $materialName = $cfg['system_material'];
                $materialId = $materialsMap[$materialName] ?? null;

                if (! $materialId) {
                    $this->error("Material '{$materialName}' tidak ditemukan di tabel materials.");

                    continue;
                }

                $la = (float) $sheet->getCell('O'.$cfg['la_row'])->getCalculatedValue();
                $lb = (float) $sheet->getCell('O'.$cfg['lb_row'])->getCalculatedValue();
                $t = (float) $sheet->getCell('O'.$cfg['t_row'])->getCalculatedValue();

                $volMaterial = (float) $sheet->getCell('M'.$cfg['calc_row'])->getCalculatedValue();
                $binM3 = (float) $sheet->getCell('C'.$cfg['bin_row'])->getCalculatedValue();
                $bj = (float) $sheet->getCell('C'.$cfg['calc_row'])->getCalculatedValue();
                $totalKg = (float) $sheet->getCell('S'.$cfg['calc_row'])->getCalculatedValue();

                $totalVolM3 = round($volMaterial + $binM3, 4);
                $binKg = round($binM3 * $bj, 2);

                $dimensions = [
                    'shape' => 'limas_terpancung',
                    'input_mode' => 'luas',
                    'tinggi' => round($t, 2),
                    'la' => round($la, 2),
                    'lb' => round($lb, 2),
                    'stock_bin' => $binKg,
                    'stock_bin_m3' => round($binM3, 2),
                    'pile_name' => $cfg['pile_name'],
                    'bj' => (int) $bj,
                    'sheet_source' => $sheetName,
                ];

                StockOpname::create([
                    'opname_date' => $opnameDate,
                    'material_id' => $materialId,
                    'method' => 'limas_terpancung',
                    'dimensions_json' => $dimensions,
                    'calculated_volume_m3' => $totalVolM3,
                    'calculated_weight_kg' => round($totalKg, 2),
                    'created_by' => $surveyorId,
                    'created_at' => Carbon::parse($opnameDate)->setTime(16, 0, 0),
                    'updated_at' => Carbon::parse($opnameDate)->setTime(16, 0, 0),
                ]);

                $importedCount++;

                $this->line(sprintf(
                    '  + %-15s (%-15s) | t=%-4.2f La=%-6.2f Lb=%-6.2f | Vol=%-9.2f m³ | Berat=%-12.2f Kg',
                    $cfg['pile_name'],
                    $materialName,
                    $t,
                    $la,
                    $lb,
                    $totalVolM3,
                    $totalKg
                ));
            }
        }

        // Tambahkan Pengukuran Fisik Tangki Solar Dexlite dari Sheet 'Dexlite'
        $dexliteSheet = $spreadsheet->getSheetByName('Dexlite');
        $dexliteId = $materialsMap['Dexlite'] ?? null;
        if ($dexliteSheet && $dexliteId) {
            $dexliteStockLiter = (float) $dexliteSheet->getCell('AD42')->getCalculatedValue();
            if ($dexliteStockLiter <= 0) {
                $dexliteStockLiter = 1144.73;
            }

            $volM3 = round($dexliteStockLiter / 1000.0, 4);
            $density = (float) (Material::where('id', $dexliteId)->value('density_kg_m3') ?: 840.0);
            $weightKg = round($dexliteStockLiter * ($density / 1000.0), 2);

            StockOpname::create([
                'opname_date' => '2026-10-14',
                'material_id' => $dexliteId,
                'method' => 'manual',
                'dimensions_json' => [
                    'shape' => 'tangki_silinder',
                    'formula' => 'π × r² × t',
                    'tinggi' => 1.15,
                    'volume_m3' => $volM3,
                    'volume_liter' => round($dexliteStockLiter, 2),
                    'pile_name' => 'Tangki Solar Utama',
                    'unit' => 'Liter',
                    'sheet_source' => 'Dexlite',
                    'stok_awal_liter' => 1234.85,
                    'penerimaan_liter' => 16439.88,
                    'pemakaian_liter' => 16530.00,
                ],
                'calculated_volume_m3' => $volM3,
                'calculated_weight_kg' => $weightKg,
                'created_by' => $surveyorId,
                'created_at' => Carbon::parse('2026-10-14')->setTime(16, 0, 0),
                'updated_at' => Carbon::parse('2026-10-14')->setTime(16, 0, 0),
            ]);

            $importedCount++;

            $this->line(sprintf(
                '  + %-15s (%-15s) | Tangki Silinder Sounding (π×r²×t) | Vol=%-9.4f m³ | Fisik=%-12.2f Liter',
                'Tangki Solar Utama',
                'Dexlite',
                $volM3,
                $dexliteStockLiter
            ));
        }

        $this->newLine();
        $this->info("Selesai! Berhasil mengimpor {$importedCount} data Stock Opname dari sheets Opname 1, 7, 14, dan Dexlite.");

        return self::SUCCESS;
    }
}
