<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\JobMix;
use App\Models\Material;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Services\DocumentNumberService;
use App\Services\JobMixCalculatorService;
use App\Services\OpnameGeometryService;
use App\Services\ProcurementCalculatorService;
use App\Services\StockReconciliationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpCalculationTest extends TestCase
{
    use RefreshDatabase;

    private DocumentNumberService $docNumberService;

    private JobMixCalculatorService $jobMixService;

    private ProcurementCalculatorService $procurementService;

    private OpnameGeometryService $opnameService;

    private StockReconciliationService $reconciliationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->docNumberService = new DocumentNumberService;
        $this->jobMixService = new JobMixCalculatorService;
        $this->procurementService = new ProcurementCalculatorService;
        $this->opnameService = new OpnameGeometryService;
        $this->reconciliationService = new StockReconciliationService;
    }

    // --- 1. DocumentNumberService Tests ---

    public function test_document_number_service_generates_correct_so_sequence(): void
    {
        $customer = Customer::create([
            'name' => 'PT Test Customer',
            'default_distance_km' => 10,
        ]);

        $jobMix = JobMix::create([
            'mutu_code' => 'K300 / C',
            'semen_kg' => 320,
            'waste_factor' => 1.05,
        ]);

        $dateOct = Carbon::create(2026, 10, 15);

        // Sequence 1 on empty month
        $so1 = $this->docNumberService->generateSoNumber($dateOct);
        $this->assertEquals('SO/01/TBI/26/10/0001', $so1);

        // Save SO 1
        SalesOrder::create([
            'so_number' => $so1,
            'order_date' => $dateOct->toDateString(),
            'customer_id' => $customer->id,
            'location' => 'Samarinda',
            'job_mix_id' => $jobMix->id,
            'volume_m3' => 6.0,
            'distance_km' => 10.0,
        ]);

        // Next sequence in same month should be 0002
        $so2 = $this->docNumberService->generateSoNumber($dateOct);
        $this->assertEquals('SO/01/TBI/26/10/0002', $so2);

        // Sequence resets to 0001 in next month (November 2026)
        $dateNov = Carbon::create(2026, 11, 1);
        $soNov = $this->docNumberService->generateSoNumber($dateNov);
        $this->assertEquals('SO/01/TBI/26/11/0001', $soNov);
    }

    public function test_document_number_service_generates_correct_po_sequence(): void
    {
        $supplier = Supplier::create(['name' => 'PT Test Supplier']);
        $material = Material::create(['name' => 'Semen Test', 'unit' => 'Kg']);

        $dateOct = Carbon::create(2026, 10, 20);

        // First PO in month
        $po1 = $this->docNumberService->generatePoNumber($dateOct);
        $this->assertEquals('PO-PCR/01/TBI/26/10/0001', $po1);

        PurchaseOrder::create([
            'po_number' => $po1,
            'po_date' => $dateOct->toDateString(),
            'supplier_id' => $supplier->id,
            'material_id' => $material->id,
            'qty_ordered' => 100,
            'unit_price' => 50000,
            'subtotal' => 5000000,
            'ppn_amount' => 550000,
            'grand_total' => 5550000,
        ]);

        // Second PO in same month
        $po2 = $this->docNumberService->generatePoNumber($dateOct);
        $this->assertEquals('PO-PCR/01/TBI/26/10/0002', $po2);
    }

    // --- 2. JobMixCalculatorService Tests ---

    public function test_job_mix_calculator_matches_excel_formulas_and_results(): void
    {
        // Data from Excel TBI - ERP.xlsx (Row 7 Sales Order sheet: K300 / C, Volume 6.0 m3)
        $jobMix = JobMix::create([
            'mutu_code' => 'K300 / C',
            'semen_kg' => 320.00,
            'pasir_palu_kg' => 648.00,
            'pasir_lokal_kg' => 210.00,
            'split_1_2_kg' => 1095.00,
            'split_2_3_kg' => 0.00,
            'additive_liter' => 5.44,
            'waste_factor' => 1.0500,
        ]);

        $volume = 6.0;
        $distance = 12.0;

        $estimates = $this->jobMixService->calculateAllSoEstimates($jobMix, $volume, $distance);

        // Exact Excel matches:
        // Semen: 320 * 6 * 1.05 = 2016.00
        $this->assertEquals(2016.00, $estimates['est_semen_kg']);

        // Pasir Palu: 648 * 6 * 1.05 = 4082.40
        $this->assertEquals(4082.40, $estimates['est_pasir_palu_kg']);

        // Pasir Lokal: 210 * 6 * 1.05 = 1323.00
        $this->assertEquals(1323.00, $estimates['est_pasir_lokal_kg']);

        // Split 1/2: 1095 * 6 * 1.05 = 6898.50
        $this->assertEquals(6898.50, $estimates['est_split_1_2_kg']);

        // Split 2/3: 0
        $this->assertEquals(0.00, $estimates['est_split_2_3_kg']);

        // Additive: 5.44 * 6 * 1.05 = 34.272 => 34.27
        $this->assertEquals(34.27, $estimates['est_additive_liter']);

        // Solar: (1 / 1.2) * 12.0 = 10.00 Liter
        $this->assertEquals(10.00, $estimates['est_solar_liter']);
    }

    // --- 3. ProcurementCalculatorService Tests ---

    public function test_procurement_calculator_computes_po_amounts_accurately(): void
    {
        $qty = 250.5;
        $unitPrice = 145000.00;

        $result = $this->procurementService->calculatePoAmounts($qty, $unitPrice);

        $expectedSubtotal = round(250.5 * 145000.00, 2); // 36,322,500.00
        $expectedPpn = round($expectedSubtotal * 0.11, 2);  // 3,995,475.00
        $expectedGrandTotal = round($expectedSubtotal + $expectedPpn, 2); // 40,317,975.00

        $this->assertEquals($expectedSubtotal, $result['subtotal']);
        $this->assertEquals($expectedPpn, $result['ppn_amount']);
        $this->assertEquals($expectedGrandTotal, $result['grand_total']);
    }

    public function test_procurement_calculator_computes_vessel_shrinkage(): void
    {
        $loadVol = 1200.00;
        $unloadVol = 1176.00; // Selisih 24 m3 => 2%

        $result = $this->procurementService->calculateVesselShrinkage($loadVol, $unloadVol);

        $this->assertEquals(24.00, $result['volume_diff']);
        $this->assertEquals(2.0000, $result['shrinkage_percent']);
    }

    public function test_procurement_calculator_computes_receive_weight(): void
    {
        // Pasir Palu: Volume 14.5 m3, Density 1658 kg/m3
        $vol = 14.5;
        $density = 1658.00;

        $weight = $this->procurementService->calculateReceiveWeight($vol, $density);

        $this->assertEquals(24041.00, $weight);
    }

    // --- 4. OpnameGeometryService Tests ---

    public function test_opname_geometry_limas_terpancung(): void
    {
        // Limas terpancung:
        // pa1=10, pa2=10, la1=8, la2=8 => La = 10 * 8 = 80
        // pb1=20, pb2=20, lb1=16, lb2=16 => Lb = 20 * 16 = 320
        // t = 6
        // V = (1/3) * 6 * (80 + 320 + sqrt(80 * 320))
        // sqrt(25600) = 160
        // V = 2 * (80 + 320 + 160) = 2 * 560 = 1120.0000
        $volume = $this->opnameService->calculateLimasTerpancung(
            pa1: 10, pa2: 10, la1: 8, la2: 8,
            pb1: 20, pb2: 20, lb1: 16, lb2: 16,
            t: 6
        );

        $this->assertEquals(1120.0000, $volume);

        // Convert with density 1500 and stock bin 500
        $weight = $this->opnameService->convertVolumeToWeight($volume, 1500, 500);
        $expectedWeight = 500 + (1120 * 1500); // 1,680,500.00
        $this->assertEquals($expectedWeight, $weight);
    }

    public function test_opname_geometry_kerucut_terpancung(): void
    {
        // Kerucut terpancung:
        // Ra = 2, Rb = 4, t = 3
        // V = (1/3) * 3.14 * 3 * (2^2 + 4^2 + (2 * 4))
        // V = 3.14 * (4 + 16 + 8) = 3.14 * 28 = 87.9200
        $volume = $this->opnameService->calculateKerucutTerpancung(ra: 2, rb: 4, t: 3);

        $this->assertEquals(87.9200, $volume);
    }

    public function test_opname_geometry_generic_calculate_opname(): void
    {
        $payload = [
            'method' => 'kerucut_terpancung',
            'ra' => 2,
            'rb' => 4,
            't' => 3,
            'stock_bin_kg' => 1000,
        ];

        $density = 1600.00;

        $result = $this->opnameService->calculateOpname('kerucut_terpancung', $payload, $density);

        $this->assertEquals(87.9200, $result['calculated_volume_m3']);
        // Weight: 1000 + (87.92 * 1600) = 1000 + 140672 = 141672.00
        $this->assertEquals(141672.00, $result['calculated_weight_kg']);
    }

    // --- 5. StockReconciliationService Tests ---

    public function test_stock_reconciliation_service_determines_correct_status_and_deviations(): void
    {
        // Case 1: SESUAI (Teoritis == Fisik)
        $case1 = $this->reconciliationService->calculateReconciliation(
            initialStock: 100000,
            totalReceive: 50000,
            totalUsage: 30000,
            physicalStock: 120000 // Teoritis = 100000 + 50000 - 30000 = 120000
        );
        $this->assertEquals(120000.00, $case1['stok_teoritis']);
        $this->assertEquals(0.00, $case1['selisih']);
        $this->assertEquals(0.0000, $case1['persentase_selisih']);
        $this->assertEquals(StockReconciliationService::STATUS_SESUAI, $case1['status']);

        // Case 2: KEKURANGAN (Teoritis > Fisik)
        // Teoritis = 120000, Fisik = 114000 => Selisih 6000 (5%)
        $case2 = $this->reconciliationService->calculateReconciliation(
            initialStock: 100000,
            totalReceive: 50000,
            totalUsage: 30000,
            physicalStock: 114000
        );
        $this->assertEquals(120000.00, $case2['stok_teoritis']);
        $this->assertEquals(6000.00, $case2['selisih']);
        $this->assertEquals(5.0000, $case2['persentase_selisih']);
        $this->assertEquals(StockReconciliationService::STATUS_KEKURANGAN, $case2['status']);

        // Case 3: KELEBIHAN (Teoritis < Fisik)
        // Teoritis = 120000, Fisik = 126000 => Selisih -6000 (5%)
        $case3 = $this->reconciliationService->calculateReconciliation(
            initialStock: 100000,
            totalReceive: 50000,
            totalUsage: 30000,
            physicalStock: 126000
        );
        $this->assertEquals(120000.00, $case3['stok_teoritis']);
        $this->assertEquals(-6000.00, $case3['selisih']);
        $this->assertEquals(5.0000, $case3['persentase_selisih']);
        $this->assertEquals(StockReconciliationService::STATUS_KELEBIHAN, $case3['status']);
    }
}
