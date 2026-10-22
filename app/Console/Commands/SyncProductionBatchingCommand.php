<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\ProductionDocket;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SyncProductionBatchingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-production-batching {--file= : Path to Opname.xlsx}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi realisasi pemakaian material batching plant dari sheet Pemakaian Opname.xlsx ke tabel production_dockets';

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

        $this->info("Membaca berkas Excel: {$filePath}...");

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);

        $sheet = $spreadsheet->getSheetByName('Pemakaian');
        if (! $sheet) {
            $this->error("Sheet 'Pemakaian' tidak ditemukan di dalam berkas Excel.");

            return self::FAILURE;
        }

        $defaultUserId = User::where('role', 'operator_produksi')->value('id')
            ?? User::where('role', 'admin')->value('id')
            ?? 1;

        $updatedCount = 0;
        $insertedCount = 0;
        $skippedCount = 0;

        $totalRows = 616; // Baris 7 sampai 616

        $this->output->progressStart(610);

        DB::beginTransaction();

        try {
            for ($r = 7; $r <= $totalRows; $r++) {
                $docketNo = trim((string) $sheet->getCell("C{$r}")->getValue());
                if (empty($docketNo)) {
                    continue;
                }

                // Ambil data material aktual
                $semen = (float) $sheet->getCell("K{$r}")->getValue();
                $pasirPalu = (float) $sheet->getCell("L{$r}")->getValue();
                $pasirLariang = (float) $sheet->getCell("M{$r}")->getValue();
                $pasirTibo = (float) $sheet->getCell("N{$r}")->getValue();
                $totalPasirPalu = $pasirPalu + $pasirLariang + $pasirTibo;

                $pasirLokal = (float) $sheet->getCell("O{$r}")->getValue();
                $split23 = (float) $sheet->getCell("P{$r}")->getValue();
                $split12 = (float) $sheet->getCell("Q{$r}")->getValue();
                $water = (float) $sheet->getCell("R{$r}")->getValue();
                $admixture = (float) $sheet->getCell("S{$r}")->getValue();

                $rawDate = $sheet->getCell("A{$r}")->getValue();
                $prodDate = is_numeric($rawDate)
                    ? Date::excelToDateTimeObject((float) $rawDate)->format('Y-m-d')
                    : (string) $rawDate;

                $customerName = trim((string) $sheet->getCell("B{$r}")->getValue());
                $mutuCode = trim((string) $sheet->getCell("D{$r}")->getValue());
                $address = trim((string) $sheet->getCell("E{$r}")->getValue());
                $volume = (float) $sheet->getCell("F{$r}")->getValue();

                // Cek apakah docket sudah ada di database
                $existing = DB::table('production_dockets')->where('docket_no', $docketNo)->first();

                if ($existing) {
                    // Update pemakaian material aktual
                    DB::table('production_dockets')->where('id', $existing->id)->update([
                        'silo1_act' => $semen,
                        'pasir_palu_act' => $totalPasirPalu,
                        'pasir_lokal_act' => $pasirLokal,
                        'split_1_2_act' => $split12,
                        'split_2_3_act' => $split23,
                        'water_act' => $water,
                        'admixture_act' => $admixture,
                        'updated_at' => now(),
                    ]);
                    $updatedCount++;
                } else {
                    // Pastikan customer terdaftar
                    if (! empty($customerName)) {
                        Customer::firstOrCreate(
                            ['name' => $customerName],
                            [
                                'address' => $address ?: 'Samarinda, Kalimantan Timur',
                                'default_distance_km' => 15.00,
                            ]
                        );
                    }

                    // Insert docket baru
                    DB::table('production_dockets')->insert([
                        'docket_no' => $docketNo,
                        'production_date' => $prodDate,
                        'time_load' => '08:00:00',
                        'truck_no' => 'TM-01',
                        'customer_name' => $customerName,
                        'address' => $address,
                        'mutu_code' => $mutuCode ?: 'K250',
                        'volume_m3' => $volume > 0 ? $volume : 6.00,
                        'daily_cumulative_m3' => $volume > 0 ? $volume : 6.00,
                        'load_time_diff' => null,
                        'delivery_status' => ProductionDocket::STATUS_COMPLETED,
                        'silo1_tar' => 0.00,
                        'silo1_act' => $semen,
                        'silo2_tar' => 0.00,
                        'silo2_act' => 0.00,
                        'pasir_palu_tar' => 0.00,
                        'pasir_palu_act' => $totalPasirPalu,
                        'pasir_lokal_tar' => 0.00,
                        'pasir_lokal_act' => $pasirLokal,
                        'split_1_2_tar' => 0.00,
                        'split_1_2_act' => $split12,
                        'split_2_3_tar' => 0.00,
                        'split_2_3_act' => $split23,
                        'water_tar' => 0.00,
                        'water_act' => $water,
                        'admixture_tar' => 0.00,
                        'admixture_act' => $admixture,
                        'created_by' => $defaultUserId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $insertedCount++;
                }

                $this->output->progressAdvance();
            }

            DB::commit();
            $this->output->progressFinish();

            $this->info('Sinkronisasi Selesai!');
            $this->table(
                ['Status', 'Jumlah Record'],
                [
                    ['Docket Diperbarui (Updated)', $updatedCount],
                    ['Docket Baru Ditambahkan (Inserted)', $insertedCount],
                    ['Total Diproses', $updatedCount + $insertedCount],
                ]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->output->progressFinish();
            $this->error("Terjadi kesalahan saat sinkronisasi: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
