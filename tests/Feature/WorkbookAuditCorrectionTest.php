<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\StockOpname;
use App\Models\User;
use App\Services\OpnameGeometryService;
use App\Services\ProcurementCalculatorService;
use App\Services\StockReconciliationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkbookAuditCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * 1. Audit Poin 2.1: Evaluasi status fisik Semen dan Dexlite dari opname manual
     * Di Excel, formula hanya membaca Drone (kolom I) sehingga Semen Silo dan Dexlite Tangki selalu '-'
     * Di Laravel, status harus terhitung valid menggunakan opname fisik manual (COALESCE).
     */
    public function test_semen_and_dexlite_manual_physical_opnames_are_evaluated_accurately(): void
    {
        $admin = User::where('role', 'admin')->first();
        $service = app(StockReconciliationService::class);

        $semen = Material::where('name', 'Semen')->first();
        $this->assertNotNull($semen);

        // Bersihkan opname lama untuk testing
        StockOpname::where('material_id', $semen->id)->delete();

        // 1. Uji Semen dengan Opname Manual Silo (metode manual/kerucut)
        $semenTeoritis = $service->reconcileMaterial($semen, now()->year, now()->month)['stok_teoritis'];
        $this->assertGreaterThan(0, $semenTeoritis);

        // Input opname manual sounding silo dengan selisih defisit 10% (fisik < teoritis, melebihi toleransi 0.5%)
        $semenFisik = round($semenTeoritis * 0.90, 2);
        StockOpname::create([
            'opname_date' => now()->toDateString(),
            'material_id' => $semen->id,
            'method' => 'manual',
            'dimensions_json' => ['sounding_m' => 12.5, 'silo' => 'Silo 1 & Silo 2'],
            'calculated_volume_m3' => round($semenFisik / 1250, 4),
            'calculated_weight_kg' => $semenFisik,
            'created_by' => $admin->id,
        ]);

        $semenRecon = $service->reconcileMaterial($semen, now()->year, now()->month);
        $this->assertTrue($semenRecon['has_opname']);
        $this->assertEquals($semenFisik, $semenRecon['stok_fisik']);
        $this->assertNotEquals('-', $semenRecon['status']);
        $this->assertEquals(StockReconciliationService::STATUS_KEKURANGAN, $semenRecon['status']);

        // 2. Uji Dexlite dengan Opname Manual Tangki Solar (metode manual sounding dalam liter)
        $dexlite = Material::where('name', 'Dexlite')->first();
        $this->assertNotNull($dexlite);

        StockOpname::where('material_id', $dexlite->id)->delete();

        $dexliteTeoritis = $service->reconcileMaterial($dexlite, now()->year, now()->month)['stok_teoritis'];
        $this->assertGreaterThan(0, $dexliteTeoritis);

        // Input opname manual tangki dexlite dalam satuan Liter (misal 1.5 m3 = 1500 Liter)
        StockOpname::create([
            'opname_date' => now()->toDateString(),
            'material_id' => $dexlite->id,
            'method' => 'manual',
            'dimensions_json' => ['dipstick_cm' => 85, 'tangki' => 'Tangki Solar Utama'],
            'calculated_volume_m3' => 1.50,
            'calculated_weight_kg' => 1260.00, // 1.5 m3 * 840 kg/m3
            'created_by' => $admin->id,
        ]);

        $dexliteRecon = $service->reconcileMaterial($dexlite, now()->year, now()->month);
        $this->assertTrue($dexliteRecon['has_opname']);
        // Stok fisik Dexlite dievaluasi dalam Liter: 1.5 m3 * 1000 = 1500 Liter
        $this->assertEquals(1500.00, $dexliteRecon['stok_fisik']);
        $this->assertNotEquals('-', $dexliteRecon['status']);
    }

    /**
     * 2. Audit Poin 2.2: Koreksi logika terbalik Excel (KEKURANGAN vs KELEBIHAN)
     * Excel: IF(G < I, "KEKURANGAN", "KELEBIHAN") -> Fisik < Teoritis malah dicap KELEBIHAN
     * Sistem ERP:
     * - Fisik < Teoritis -> KEKURANGAN (Defisit/Losses)
     * - Fisik > Teoritis -> KELEBIHAN (Surplus)
     * - |Fisik - Teoritis| <= 0.5% -> SESUAI
     */
    public function test_deviation_status_logic_corrects_excel_inverted_bug(): void
    {
        $service = app(StockReconciliationService::class);

        // Kasus 1: Fisik jauh lebih kecil dari teoritis (Defisit/Kehilangan riil)
        // Teoritis = 1000, Fisik = 800 => Selisih = 200 (20% deviasi) => KEKURANGAN
        $shortage = $service->calculateReconciliation(1000, 0, 0, 800);
        $this->assertEquals(StockReconciliationService::STATUS_KEKURANGAN, $shortage['status']);
        $this->assertEquals(200.00, $shortage['selisih']);
        $this->assertEquals(-200.00, $shortage['deviasi']);

        // Kasus 2: Fisik lebih besar dari teoritis (Surplus riil)
        // Teoritis = 1000, Fisik = 1200 => Selisih = -200 (-20% deviasi) => KELEBIHAN
        $surplus = $service->calculateReconciliation(1000, 0, 0, 1200);
        $this->assertEquals(StockReconciliationService::STATUS_KELEBIHAN, $surplus['status']);
        $this->assertEquals(-200.00, $surplus['selisih']);
        $this->assertEquals(200.00, $surplus['deviasi']);

        // Kasus 3: Dalam toleransi 0.5% (misal Teoritis = 1000, Fisik = 1003 => 0.3% selisih) => SESUAI
        $match = $service->calculateReconciliation(1000, 0, 0, 1003);
        $this->assertEquals(StockReconciliationService::STATUS_SESUAI, $match['status']);
    }

    /**
     * 3. Audit Poin 2.3: Stok akhir teoritis aman terhadap pemakaian nol/null
     * Di Excel: IF(OR(D9="",E9="",F9=""), "", D9+E9-F9) -> mengembalikan kosong jika pemakaian kosong
     * Di ERP: stokAwal + penerimaan - COALESCE(pemakaian, 0)
     */
    public function test_theoretical_stock_calculation_is_safe_against_null_usage(): void
    {
        $service = app(StockReconciliationService::class);

        // Jika pemakaian 0 / string kosong, stok teoritis tetap terhitung D + E
        $res = $service->calculateReconciliation(50000, 20000, 0, 70000);
        $this->assertEquals(70000.00, $res['stok_teoritis']);
        $this->assertEquals(StockReconciliationService::STATUS_SESUAI, $res['status']);

        // Uji pada material langsung tanpa pemakaian
        $material = Material::where('name', 'Batu Besar 2/3')->first();
        $this->assertNotNull($material);

        $recon = $service->reconcileMaterial($material, 2030, 1); // Periode masa depan tanpa transaksi
        $this->assertEquals((float) $material->initial_stock_kg, $recon['stok_teoritis']);
        $this->assertNotNull($recon['stok_teoritis']);
    }

    /**
     * 4. Audit Poin 3.1 & 3.2: Akurasi konversi volume ke kilogram menggunakan density dari Master Data
     * - Batu Split 1/2: menggunakan density master 1517 kg/m3 (bukan hardcoded 1528)
     * - Dexlite: konversi menggunakan density 840 kg/m3 (bukan stok awal Additive E12)
     */
    public function test_material_receive_density_conversions_use_correct_master_density(): void
    {
        $calculator = app(ProcurementCalculatorService::class);

        $split12 = Material::where('name', 'Batu Kecil 1/2')->first();
        $this->assertEquals(1517.00, (float) $split12->density_kg_m3);

        // Hitung 10 m3 Batu Split 1/2 dengan density master
        $splitWeight = $calculator->calculateReceiveWeight(10.0, $split12->density_kg_m3);
        $this->assertEquals(15170.00, $splitWeight);

        // Dexlite density master 840 kg/m3 (bukan 4243.75 dari stok awal Additive)
        $dexlite = Material::where('name', 'Dexlite')->first();
        $this->assertEquals(840.00, (float) $dexlite->density_kg_m3);

        $dexliteWeight = $calculator->calculateReceiveWeight(5.0, $dexlite->density_kg_m3);
        $this->assertEquals(4200.00, $dexliteWeight);
    }

    /**
     * 5. Audit Poin 3.3: Konversi Opname Drone Additive mengalikan volume dengan density (V * D), bukan membaginya
     */
    public function test_opname_additive_drone_multiplies_volume_by_density(): void
    {
        $geometryService = app(OpnameGeometryService::class);
        $additive = Material::where('name', 'Additive')->first();
        $this->assertEquals(1172.00, (float) $additive->density_kg_m3);

        // Misal drone mengukur volume timbunan/IBC 2.0 m3
        $calc = $geometryService->calculateOpname('drone', ['volume_m3' => 2.0], $additive->density_kg_m3);

        $this->assertEquals(2.0, $calc['calculated_volume_m3']);
        // 2.0 m3 * 1172 kg/m3 = 2344.00 kg (DIKALIKAN, BUKAN DIBAGI 2 / 1172)
        $this->assertEquals(2344.00, $calc['calculated_weight_kg']);
    }
}
