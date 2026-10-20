<?php

namespace Tests\Feature;

use App\Livewire\MaterialReceiveForm;
use App\Livewire\PurchaseOrderForm;
use App\Models\Material;
use App\Models\MaterialReceive;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseOrderModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_purchase_orders_page_is_restricted_by_rbac(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kasir = User::where('role', 'kasir_logistik')->first();
        $surveyor = User::where('role', 'surveyor_opname')->first();
        $operator = User::where('role', 'operator_produksi')->first();

        // 1. Guest redirected to login
        $this->get('/purchase-orders')->assertRedirect('/login');

        // 2. Admin and Kasir Logistik can access
        $this->actingAs($admin)->get('/purchase-orders')->assertOk();
        $this->actingAs($kasir)->get('/purchase-orders')->assertOk();

        // 3. Surveyor and Operator are forbidden (403)
        $this->actingAs($surveyor)->get('/purchase-orders')->assertForbidden();
        $this->actingAs($operator)->get('/purchase-orders')->assertForbidden();
    }

    public function test_reactive_dynamic_unit_switching(): void
    {
        $kasir = User::where('role', 'kasir_logistik')->first();
        $this->actingAs($kasir);

        // Semen Singa Merah -> Ton
        Livewire::test(PurchaseOrderForm::class)
            ->set('material_type', 'Semen Singa Merah')
            ->assertSet('unit', 'Ton');

        // Semen Conch -> Ton
        Livewire::test(PurchaseOrderForm::class)
            ->set('material_type', 'Semen Conch')
            ->assertSet('unit', 'Ton');

        // Dexlite -> Liter
        Livewire::test(PurchaseOrderForm::class)
            ->set('material_type', 'Dexlite')
            ->assertSet('unit', 'Liter');

        // Additive -> Kg
        Livewire::test(PurchaseOrderForm::class)
            ->set('material_type', 'Additive')
            ->assertSet('unit', 'Kg');

        // Pasir Palu -> m³
        Livewire::test(PurchaseOrderForm::class)
            ->set('material_type', 'Pasir Palu')
            ->assertSet('unit', 'm³');

        // Pasir Lariang -> m³
        Livewire::test(PurchaseOrderForm::class)
            ->set('material_type', 'Pasir Lariang')
            ->assertSet('unit', 'm³');

        // Pasir Mahakam -> m³
        Livewire::test(PurchaseOrderForm::class)
            ->set('material_type', 'Pasir Mahakam')
            ->assertSet('unit', 'm³');

        // Batu 1/2 -> m³
        Livewire::test(PurchaseOrderForm::class)
            ->set('material_type', 'Batu 1/2')
            ->assertSet('unit', 'm³');

        // Batu 2/3 -> m³
        Livewire::test(PurchaseOrderForm::class)
            ->set('material_type', 'Batu 2/3')
            ->assertSet('unit', 'm³');
    }

    public function test_pasir_lariang_and_tibo_map_to_pasir_palu_backend_account(): void
    {
        $kasir = User::where('role', 'kasir_logistik')->first();
        $supplier = Supplier::where('name', 'PT KAIROS')->first() ?? Supplier::first();
        $pasirPaluMaterial = Material::where('name', 'Pasir Palu')->firstOrFail();

        $this->actingAs($kasir);

        Livewire::test(PurchaseOrderForm::class)
            ->set('po_date', '2026-10-21')
            ->set('supplier_id', $supplier->id)
            ->set('material_type', 'Pasir Lariang')
            ->set('specification', 'Pasir Pasang Lariang Halus')
            ->set('qty_ordered', '3.500,00')
            ->set('vessel_name', 'TB. KAIROS / BG. 300 FT')
            ->set('loading_jetty', 'Jetty Palu')
            ->set('unloading_jetty', 'BPM Samarinda')
            ->set('est_departure_date', '2026-10-22')
            ->set('est_arrival_date', '2026-10-26')
            ->set('status', 'Dalam Perjalanan')
            ->call('save')
            ->assertHasNoErrors();

        $po = PurchaseOrder::where('material_type_input', 'Pasir Lariang')->latest('id')->firstOrFail();

        $this->assertEquals($pasirPaluMaterial->id, $po->material_id);
        $this->assertEquals('Pasir Lariang', $po->material_type_input);
        $this->assertEquals(3500.00, (float) $po->qty_ordered);
        $this->assertEquals('m³', $po->unit);
        $this->assertEquals('Dalam Perjalanan', $po->status);
    }

    public function test_pasir_mahakam_maps_to_pasir_lokal_backend_account(): void
    {
        $kasir = User::where('role', 'kasir_logistik')->first();
        $supplier = Supplier::where('name', 'UD MUDA BERKARYA')->first() ?? Supplier::first();
        $pasirLokalMaterial = Material::where('name', 'Pasir Lokal')->firstOrFail();

        $this->actingAs($kasir);

        Livewire::test(PurchaseOrderForm::class)
            ->set('po_date', '2026-10-21')
            ->set('supplier_id', $supplier->id)
            ->set('material_type', 'Pasir Mahakam')
            ->set('specification', 'Pasir Halus Mahakam')
            ->set('qty_ordered', '750,50')
            ->set('vessel_name', 'TB. MAHAKAM 02')
            ->set('loading_jetty', 'Jetty Anggana')
            ->set('unloading_jetty', 'BPM Samarinda')
            ->set('status', 'Sudah Dimuat')
            ->call('save')
            ->assertHasNoErrors();

        $po = PurchaseOrder::where('material_type_input', 'Pasir Mahakam')->latest('id')->firstOrFail();

        $this->assertEquals($pasirLokalMaterial->id, $po->material_id);
        $this->assertEquals('Pasir Mahakam', $po->material_type_input);
        $this->assertEquals(750.50, (float) $po->qty_ordered);
        $this->assertEquals('m³', $po->unit);
    }

    public function test_financial_columns_are_read_only_and_saved_as_zero(): void
    {
        $kasir = User::where('role', 'kasir_logistik')->first();
        $supplier = Supplier::first();

        $this->actingAs($kasir);

        Livewire::test(PurchaseOrderForm::class)
            ->set('po_date', '2026-10-21')
            ->set('supplier_id', $supplier->id)
            ->set('material_type', 'Semen Singa Merah')
            ->set('specification', 'Semen Curah OPC')
            ->set('qty_ordered', '500')
            ->set('status', 'Menunggu Konfirmasi')
            ->call('save')
            ->assertHasNoErrors();

        $po = PurchaseOrder::where('material_type_input', 'Semen Singa Merah')->latest('id')->firstOrFail();

        $this->assertEquals(0.00, (float) $po->unit_price);
        $this->assertEquals(0.00, (float) $po->subtotal);
        $this->assertEquals(0.00, (float) $po->ppn_amount);
        $this->assertEquals(0.00, (float) $po->grand_total);
        $this->assertEquals('Ton', $po->unit);
    }

    public function test_po_number_format_matches_real_structure(): void
    {
        $kasir = User::where('role', 'kasir_logistik')->first();
        $supplier = Supplier::first();

        $this->actingAs($kasir);

        Livewire::test(PurchaseOrderForm::class)
            ->set('po_date', '2026-10-21')
            ->set('supplier_id', $supplier->id)
            ->set('material_type', 'Dexlite')
            ->set('specification', 'BBM Solar Dexlite')
            ->set('qty_ordered', '1200')
            ->set('status', 'Selesai Seluruhnya')
            ->call('save')
            ->assertHasNoErrors();

        $po = PurchaseOrder::where('material_type_input', 'Dexlite')->latest('id')->firstOrFail();

        // Pattern: PO-PCR/01/TBI/yy/mm/xxxx
        $this->assertMatchesRegularExpression('/^PO-PCR\/01\/TBI\/\d{2}\/\d{2}\/\d{4}$/', $po->po_number);
    }

    public function test_search_filter_in_history_table(): void
    {
        $kasir = User::where('role', 'kasir_logistik')->first();
        $this->actingAs($kasir);

        // Baseline seeded data includes PO-PCR/01/TBI/26/10/0010
        Livewire::test(PurchaseOrderForm::class)
            ->set('search', '0010')
            ->assertSee('PO-PCR/01/TBI/26/10/0010')
            ->assertDontSee('PO-PCR/01/TBI/26/10/0020');
    }

    public function test_po_status_cannot_be_reverted_backwards(): void
    {
        $kasir = User::where('role', 'kasir_logistik')->first();
        $this->actingAs($kasir);

        $po = PurchaseOrder::where('status', 'Sudah Tiba di Pelabuhan')->first();
        $this->assertNotNull($po);

        // Coba mundurkan status ke 'Dalam Perjalanan' atau 'Sudah Dimuat'
        Livewire::test(PurchaseOrderForm::class)
            ->call('openEditModal', $po->id)
            ->set('editing_status', 'Dalam Perjalanan')
            ->call('updateStatusAndLogistics')
            ->assertHasErrors(['editing_status']);

        // Pastikan di database status tidak berubah
        $po->refresh();
        $this->assertEquals('Sudah Tiba di Pelabuhan', $po->status);
    }

    public function test_departure_date_becomes_actual_on_in_transit_status(): void
    {
        $kasir = User::where('role', 'kasir_logistik')->first();
        $this->actingAs($kasir);

        $po = PurchaseOrder::where('status', 'Sudah Dimuat')->first();
        $this->assertNotNull($po);

        Livewire::test(PurchaseOrderForm::class)
            ->call('openEditModal', $po->id)
            ->set('editing_status', 'Dalam Perjalanan')
            ->assertSet('editing_status', 'Dalam Perjalanan')
            ->call('updateStatusAndLogistics')
            ->assertHasNoErrors();

        $po->refresh();
        $this->assertEquals('Dalam Perjalanan', $po->status);
        $this->assertNotNull($po->actual_departure_date);
    }

    public function test_arrival_date_locks_departure_and_sets_actual_arrival(): void
    {
        $kasir = User::where('role', 'kasir_logistik')->first();
        $this->actingAs($kasir);

        $po = PurchaseOrder::where('status', 'Dalam Perjalanan')->first();
        $this->assertNotNull($po);

        // Berikan actual departure date awal
        $po->update(['actual_departure_date' => '2026-10-11']);

        Livewire::test(PurchaseOrderForm::class)
            ->call('openEditModal', $po->id)
            ->set('editing_status', 'Sudah Tiba di Pelabuhan')
            ->set('editing_actual_departure_date', '2026-10-25') // Coba manipulasi departure date
            ->call('updateStatusAndLogistics')
            ->assertHasNoErrors();

        $po->refresh();
        $this->assertEquals('Sudah Tiba di Pelabuhan', $po->status);
        // Departure date terkunci permanen tidak bisa dimanipulasi
        $this->assertEquals('2026-10-11', $po->actual_departure_date->format('Y-m-d'));
        // Actual arrival date terisi
        $this->assertNotNull($po->actual_arrival_date);
    }

    public function test_material_receive_can_link_to_purchase_order_and_auto_fill_details(): void
    {
        $gudang = User::where('role', 'petugas_gudang')->first() ?? User::factory()->create(['role' => 'petugas_gudang']);
        $this->actingAs($gudang);

        $po = PurchaseOrder::where('status', 'Sudah Tiba di Pelabuhan')->first();
        $this->assertNotNull($po);

        Livewire::test(MaterialReceiveForm::class)
            ->set('purchase_order_id', $po->id)
            ->assertSet('supplier_id', $po->supplier_id)
            ->assertSet('material_id', $po->material_id)
            ->assertSet('density', (string) $po->material->density_kg_m3);
    }

    public function test_first_material_receive_updates_po_status_to_penerimaan_sebagian(): void
    {
        $gudang = User::where('role', 'petugas_gudang')->first() ?? User::factory()->create(['role' => 'petugas_gudang']);
        $this->actingAs($gudang);

        $po = PurchaseOrder::where('status', 'Sudah Tiba di Pelabuhan')->first();
        $this->assertNotNull($po);

        Livewire::test(MaterialReceiveForm::class)
            ->set('purchase_order_id', $po->id)
            ->set('receive_date', '2026-10-21')
            ->set('ri_no', 'RI-TRK-001')
            ->set('vehicle_no', 'KT-9999-TB')
            ->set('volume_m3', '25.00')
            ->call('save')
            ->assertHasNoErrors();

        $po->refresh();
        $this->assertEquals('Penerimaan Sebagian', $po->status);

        $receive = MaterialReceive::where('ri_no', 'RI-TRK-001')->firstOrFail();
        $this->assertEquals($po->id, $receive->purchase_order_id);
    }

    public function test_final_material_receive_completes_po_status_to_selesai_seluruhnya(): void
    {
        $gudang = User::where('role', 'petugas_gudang')->first() ?? User::factory()->create(['role' => 'petugas_gudang']);
        $this->actingAs($gudang);

        $po = PurchaseOrder::where('status', 'Penerimaan Sebagian')->first() ?? PurchaseOrder::first();
        $po->update(['status' => 'Penerimaan Sebagian']);

        Livewire::test(MaterialReceiveForm::class)
            ->set('purchase_order_id', $po->id)
            ->set('is_final_unloading', true)
            ->set('receive_date', '2026-10-21')
            ->set('ri_no', 'RI-TRK-FINAL')
            ->set('vehicle_no', 'KT-8888-TB')
            ->set('volume_m3', '30.00')
            ->call('save')
            ->assertHasNoErrors();

        $po->refresh();
        $this->assertEquals('Selesai Seluruhnya', $po->status);

        $receive = MaterialReceive::where('ri_no', 'RI-TRK-FINAL')->firstOrFail();
        $this->assertEquals($po->id, $receive->purchase_order_id);
    }

    public function test_po_dropdown_options_exclude_sbtjm2_and_air_and_have_clean_labels(): void
    {
        $kasir = User::where('role', 'kasir_logistik')->first();
        $this->actingAs($kasir);

        $component = Livewire::test(PurchaseOrderForm::class);

        // Verifikasi langsung ke data array materialOptions
        $options = $component->viewData('materialOptions');
        $this->assertArrayNotHasKey('SBTJM2', $options);
        $this->assertArrayNotHasKey('Air', $options);
        $this->assertArrayNotHasKey('Semen', $options);
        $this->assertArrayHasKey('Semen Singa Merah', $options);
        $this->assertArrayHasKey('Semen Conch', $options);
        $this->assertEquals('Batu 1/2', $options['Batu 1/2']);
        $this->assertEquals('Batu 2/3', $options['Batu 2/3']);
        $this->assertEquals('Dexlite', $options['Dexlite']);
        $this->assertEquals('Additive', $options['Additive']);
        $this->assertEquals('Pasir Palu', $options['Pasir Palu']);
        $this->assertEquals('Pasir Lariang', $options['Pasir Lariang']);
        $this->assertEquals('Pasir Tibo', $options['Pasir Tibo']);
        $this->assertEquals('Pasir Lokal', $options['Pasir Lokal']);
        $this->assertEquals('Pasir Mahakam', $options['Pasir Mahakam']);

        // Tidak boleh ada opsi internal SBTJM2 atau pasokan plant Air di HTML
        $component->assertDontSee('SBTJM2');
        $component->assertDontSee('Air Produksi');

        // Harus memiliki opsi spesifik Semen Singa Merah dan Semen Conch
        $component->assertSee('Semen Singa Merah');
        $component->assertSee('Semen Conch');

        // Label ringkas
        $component->assertSee('Batu 1/2');
        $component->assertSee('Batu 2/3');
        $component->assertSee('Dexlite');
        $component->assertSee('Additive');
    }

    public function test_semen_singa_merah_and_conch_map_to_semen_master_account(): void
    {
        $kasir = User::where('role', 'kasir_logistik')->first();
        $supplier = Supplier::first();
        $semenMaster = Material::where('name', 'Semen')->firstOrFail();

        $this->actingAs($kasir);

        // 1. Uji Semen Singa Merah
        Livewire::test(PurchaseOrderForm::class)
            ->set('po_date', '2026-10-21')
            ->set('supplier_id', $supplier->id)
            ->set('material_type', 'Semen Singa Merah')
            ->set('qty_ordered', '1000')
            ->assertSet('unit', 'Ton')
            ->call('save')
            ->assertHasNoErrors();

        $poSingaMerah = PurchaseOrder::where('material_type_input', 'Semen Singa Merah')->latest('id')->firstOrFail();
        $this->assertEquals($semenMaster->id, $poSingaMerah->material_id);
        $this->assertEquals('Ton', $poSingaMerah->unit);

        // 2. Uji Semen Conch
        Livewire::test(PurchaseOrderForm::class)
            ->set('po_date', '2026-10-21')
            ->set('supplier_id', $supplier->id)
            ->set('material_type', 'Semen Conch')
            ->set('qty_ordered', '800')
            ->assertSet('unit', 'Ton')
            ->call('save')
            ->assertHasNoErrors();

        $poConch = PurchaseOrder::where('material_type_input', 'Semen Conch')->latest('id')->firstOrFail();
        $this->assertEquals($semenMaster->id, $poConch->material_id);
        $this->assertEquals('Ton', $poConch->unit);
    }
}
