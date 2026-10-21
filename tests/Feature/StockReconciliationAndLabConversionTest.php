<?php

namespace Tests\Feature;

use App\Livewire\AdditiveBreakdownModal;
use App\Livewire\MaterialReceiveForm;
use App\Livewire\StockReconciliation;
use App\Models\AdditivePowder;
use App\Models\LabConversion;
use App\Models\Material;
use App\Models\MaterialReceive;
use App\Models\StockOpname;
use App\Models\Supplier;
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

        $initialSnf1 = (float) AdditivePowder::where('code', 'SNF_001')->value('stock_kg');

        $service = app(StockReconciliationService::class);

        $initialAdditiveTeoritis = $service->reconcileMaterial($additive, now()->year, now()->month)['stok_teoritis'];
        $initialSbtjm2Teoritis = $service->reconcileMaterial($sbtjm2, now()->year, now()->month)['stok_teoritis'];

        // Eksekusi konversi lab melalui modal form MaterialReceiveForm dengan 4 serbuk kimia
        Livewire::test(MaterialReceiveForm::class)
            ->call('openConversionModal')
            ->assertSet('showConversionModal', true)
            ->set('conv_date', now()->format('Y-m-d'))
            ->set('conv_sbtjm2_liter', 250.00)
            ->set('conv_snf1_kg', 100.00)
            ->set('conv_snf2_kg', 100.00)
            ->set('conv_sodium_glukonate_kg', 40.00)
            ->set('conv_maltodextrine_kg', 10.00)
            ->set('conv_water_kg', 150.00)
            ->set('conv_notes', 'Batch Lab Testing Racikan #01')
            ->call('saveConversion')
            ->assertHasNoErrors()
            ->assertSet('showConversionModal', false);

        // Verifikasi record LabConversion terbentuk dengan rincian serbuk
        $conversion = LabConversion::where('notes', 'Batch Lab Testing Racikan #01')->first();
        $this->assertNotNull($conversion);
        $this->assertEquals(250.00, (float) $conversion->additive_used_kg);
        $this->assertEquals(100.00, (float) $conversion->snf1_kg);
        $this->assertEquals(100.00, (float) $conversion->snf2_kg);
        $this->assertEquals(40.00, (float) $conversion->sodium_glukonate_kg);
        $this->assertEquals(10.00, (float) $conversion->maltodextrine_kg);
        $this->assertEquals(250.00, (float) $conversion->sbtjm2_produced_liter);
        $this->assertNotNull($conversion->material_receive_id);

        // Verifikasi sisa stok powder di additive_powders berkurang
        $afterSnf1 = (float) AdditivePowder::where('code', 'SNF_001')->value('stock_kg');
        $this->assertEquals($initialSnf1 - 100.00, $afterSnf1);

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
    }

    public function test_material_receives_excludes_sbtjm2_and_air_from_dropdown(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        Livewire::test(MaterialReceiveForm::class)
            ->assertViewHas('materials', function ($materials) {
                return ! $materials->contains('name', 'SBTJM2') &&
                       ! $materials->contains('name', 'Air');
            });
    }

    public function test_semen_input_in_tons_is_calculated_and_stored_in_kg(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $semen = Material::where('name', 'Semen')->first();
        $supplier = Supplier::first();

        Livewire::test(MaterialReceiveForm::class)
            ->set('receive_date', now()->format('Y-m-d'))
            ->set('ri_no', 'RI-SEMEN-TON-01')
            ->set('vehicle_no', 'DN 9999 SM')
            ->set('supplier_id', $supplier->id)
            ->set('material_id', $semen->id)
            ->assertSet('isSemen', true)
            ->set('volume_m3', 25.50) // 25.50 Ton
            ->call('save')
            ->assertHasNoErrors();

        $saved = MaterialReceive::where('ri_no', 'RI-SEMEN-TON-01')->first();
        $this->assertNotNull($saved);
        // 25.50 Ton = 25,500 Kg
        $this->assertEquals(25500.00, (float) $saved->total_weight_kg);
    }

    public function test_additive_breakdown_modal_opens_and_renders_powders(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        Livewire::test(AdditiveBreakdownModal::class)
            ->assertSet('isOpen', false)
            ->dispatch('open-additive-modal')
            ->assertSet('isOpen', true)
            ->assertSee('Sodium Napthalane (SNF-C) 001')
            ->assertSee('Sodium Glukonate')
            ->assertSee('Maltodextrine')
            ->call('close')
            ->assertSet('isOpen', false);
    }
}
