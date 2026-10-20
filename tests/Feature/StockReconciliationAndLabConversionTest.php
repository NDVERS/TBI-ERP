<?php

namespace Tests\Feature;

use App\Livewire\MaterialReceiveForm;
use App\Livewire\StockReconciliation;
use App\Models\LabConversion;
use App\Models\Material;
use App\Models\MaterialReceive;
use App\Models\StockOpname;
use App\Models\User;
use App\Services\StockReconciliationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockReconciliationAndLabConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_reconciliation_displays_belum_diopname_when_no_physical_opname_exists(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        // Pastikan tidak ada opname fisik untuk Pasir Palu bulan ini
        $pasirPalu = Material::where('name', 'Pasir Palu')->first();
        StockOpname::where('material_id', $pasirPalu->id)->delete();

        $service = app(StockReconciliationService::class);
        $result = $service->reconcileMaterial($pasirPalu, now()->year, now()->month);

        $this->assertFalse($result['has_opname']);
        $this->assertEquals(StockReconciliationService::STATUS_BELUM_DIOPNAME, $result['status']);
        $this->assertNull($result['stok_fisik']);
        $this->assertNull($result['selisih']);
        $this->assertNull($result['deviasi']);
        $this->assertNull($result['persentase_selisih']);

        // Uji render pada Livewire component StockReconciliation
        Livewire::test(StockReconciliation::class)
            ->assertSee('BELUM DIOPNAME')
            ->assertSee('Belum Diopname');
    }

    public function test_reconciliation_displays_sesuai_only_when_valid_physical_opname_exists(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $pasirPalu = Material::where('name', 'Pasir Palu')->first();
        $service = app(StockReconciliationService::class);
        $currentTeoritis = $service->reconcileMaterial($pasirPalu, now()->year, now()->month)['stok_teoritis'];

        // Input opname fisik yang sama persis dengan stok teoritis
        StockOpname::create([
            'opname_date' => now()->format('Y-m-d'),
            'material_id' => $pasirPalu->id,
            'method' => 'limas_terpancung',
            'dimensions_json' => ['la' => 100, 'lb' => 100, 't' => 1],
            'calculated_volume_m3' => 100.0,
            'calculated_weight_kg' => $currentTeoritis,
            'created_by' => $admin->id,
        ]);

        $result = $service->reconcileMaterial($pasirPalu, now()->year, now()->month);

        $this->assertTrue($result['has_opname']);
        $this->assertEquals(StockReconciliationService::STATUS_SESUAI, $result['status']);
        $this->assertEquals($currentTeoritis, $result['stok_fisik']);
        $this->assertEquals(0.0, $result['selisih']);
        $this->assertEquals(0.0, $result['persentase_selisih']);

        Livewire::test(StockReconciliation::class)
            ->assertSee('SESUAI');
    }

    public function test_lab_conversion_deducts_additive_and_adds_sbtjm2_in_reconciliation(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $additive = Material::where('name', 'Additive')->first();
        $sbtjm2 = Material::where('name', 'SBTJM2')->first();

        $service = app(StockReconciliationService::class);

        $initialAdditiveTeoritis = $service->reconcileMaterial($additive, now()->year, now()->month)['stok_teoritis'];
        $initialSbtjm2Teoritis = $service->reconcileMaterial($sbtjm2, now()->year, now()->month)['stok_teoritis'];

        // Eksekusi konversi lab melalui modal form MaterialReceiveForm
        Livewire::test(MaterialReceiveForm::class)
            ->call('openConversionModal')
            ->assertSet('showConversionModal', true)
            ->set('conv_date', now()->format('Y-m-d'))
            ->set('conv_additive_kg', 250.00)
            ->set('conv_sbtjm2_liter', 250.00)
            ->set('conv_notes', 'Batch Lab Testing Racikan #01')
            ->call('saveConversion')
            ->assertHasNoErrors()
            ->assertSet('showConversionModal', false);

        // Verifikasi record LabConversion terbentuk
        $conversion = LabConversion::where('notes', 'Batch Lab Testing Racikan #01')->first();
        $this->assertNotNull($conversion);
        $this->assertEquals(250.00, (float) $conversion->additive_used_kg);
        $this->assertEquals(250.00, (float) $conversion->sbtjm2_produced_liter);
        $this->assertNotNull($conversion->material_receive_id);

        // Verifikasi record MaterialReceive internal untuk SBTJM2 terbentuk
        $receive = MaterialReceive::find($conversion->material_receive_id);
        $this->assertNotNull($receive);
        $this->assertEquals('INTERNAL', $receive->vehicle_no);
        $this->assertEquals($sbtjm2->id, $receive->material_id);
        $this->assertEquals(250.00, (float) $receive->total_weight_kg);

        // Verifikasi mutasi pada StockReconciliationService
        $afterAdditive = $service->reconcileMaterial($additive, now()->year, now()->month);
        $afterSbtjm2 = $service->reconcileMaterial($sbtjm2, now()->year, now()->month);

        // Pemakaian Additive bertambah 250 Kg, stok teoritis Additive berkurang 250 Kg
        $this->assertEquals($initialAdditiveTeoritis - 250.00, $afterAdditive['stok_teoritis']);
        $this->assertEquals(250.00, $afterAdditive['pemakaian']);

        // Penerimaan SBTJM2 bertambah 250 Liter, stok teoritis SBTJM2 bertambah 250 Liter
        $this->assertEquals($initialSbtjm2Teoritis + 250.00, $afterSbtjm2['stok_teoritis']);
        $this->assertEquals($initialSbtjm2Teoritis + 250.00, $afterSbtjm2['stok_teoritis']);
    }
}
