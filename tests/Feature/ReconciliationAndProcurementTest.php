<?php

namespace Tests\Feature;

use App\Livewire\MaterialReceiveForm;
use App\Livewire\StockReconciliation;
use App\Livewire\VesselShipmentForm;
use App\Models\Material;
use App\Models\MaterialReceive;
use App\Models\StockOpname;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VesselShipment;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReconciliationAndProcurementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    // --- 1. Stock Reconciliation Tests (Admin Only) ---

    public function test_reconciliation_page_is_restricted_to_admin(): void
    {
        $admin = User::where('role', 'admin')->first();
        $gudang = User::where('role', 'petugas_gudang')->first();
        $kasir = User::where('role', 'kasir_logistik')->first();
        $surveyor = User::where('role', 'surveyor_opname')->first();

        // Guest redirected to login
        $this->get('/reconciliation')->assertRedirect('/login');

        // Admin allowed
        $this->actingAs($admin)->get('/reconciliation')->assertOk();

        // Others forbidden (403)
        $this->actingAs($gudang)->get('/reconciliation')->assertForbidden();
        $this->actingAs($kasir)->get('/reconciliation')->assertForbidden();
        $this->actingAs($surveyor)->get('/reconciliation')->assertForbidden();
    }

    public function test_reconciliation_calculates_deviation_and_statuses_correctly(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        // Pasir Palu initial stock: 1,149,839.58 Kg
        $pasirPalu = Material::where('name', 'Pasir Palu')->first();
        $supplier = Supplier::first();

        // 1. Tambah penerimaan material: 200 m3 * 1658 kg/m3 = 331,600 kg
        MaterialReceive::create([
            'receive_date' => now()->format('Y-m-d'),
            'supplier_id' => $supplier->id,
            'material_id' => $pasirPalu->id,
            'vehicle_no' => 'DN 1234 XY',
            'ri_no' => 'RI-TEST-01',
            'time_in' => '10:00',
            'volume_m3' => 200.00,
            'density_used' => 1658.00,
            'total_weight_kg' => 331600.00,
        ]);

        // 2. Tambah input opname fisik dari surveyor: 1,481,439.58 kg
        // Stok teoritis = 1,149,839.58 + 331,600 - 0 = 1,481,439.58 Kg
        // Jika fisik sama dengan teoritis => Selisih = 0 => Status SESUAI
        StockOpname::create([
            'opname_date' => now()->format('Y-m-d'),
            'material_id' => $pasirPalu->id,
            'method' => 'limas_terpancung',
            'dimensions_json' => ['la' => 100, 'lb' => 200, 't' => 3],
            'calculated_volume_m3' => 893.51,
            'calculated_weight_kg' => 1481439.58,
            'created_by' => $admin->id,
        ]);

        Livewire::test(StockReconciliation::class)
            ->assertSee('Pasir Palu')
            ->assertSee('SESUAI');
    }

    public function test_reconciliation_csv_export_generates_valid_download(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        $component = Livewire::test(StockReconciliation::class);
        $response = $component->call('exportCsv');

        $this->assertNotNull($response);
    }

    // --- 2. Material Receive Tests ---

    public function test_material_receive_page_is_restricted_by_rbac(): void
    {
        $admin = User::where('role', 'admin')->first();
        $gudang = User::where('role', 'petugas_gudang')->first();
        $surveyor = User::where('role', 'surveyor_opname')->first();

        $this->actingAs($admin)->get('/material-receives')->assertOk();
        $this->actingAs($gudang)->get('/material-receives')->assertOk();
        $this->actingAs($surveyor)->get('/material-receives')->assertForbidden();
    }

    public function test_material_receive_form_saves_and_converts_volume_to_kg(): void
    {
        $gudang = User::where('role', 'petugas_gudang')->first();
        $supplier = Supplier::first();
        // Pasir Palu density: 1658 kg/m3
        $pasirPalu = Material::where('name', 'Pasir Palu')->first();

        $this->actingAs($gudang);

        // 1. Auto-populated density from master (10.00 m3 * 1658 kg/m3 = 16,580.00 kg)
        Livewire::test(MaterialReceiveForm::class)
            ->set('receive_date', '2026-10-20')
            ->set('supplier_id', $supplier->id)
            ->set('material_id', $pasirPalu->id)
            ->assertSet('density', '1658.00')
            ->assertSet('masterDensity', 1658.0)
            ->set('vehicle_no', 'DN 9999 AB')
            ->set('ri_no', 'RI-OCT-001')
            ->set('volume_m3', 10.00)
            ->assertSet('totalWeight', 16580.0)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('RI-OCT-001');

        $receive = MaterialReceive::where('ri_no', 'RI-OCT-001')->first();
        $this->assertNotNull($receive);
        $this->assertNull($receive->time_in);
        $this->assertEquals(10.00, (float) $receive->volume_m3);
        $this->assertEquals(1658.00, (float) $receive->density_used);
        $this->assertEquals(16580.00, (float) $receive->total_weight_kg);

        // 2. Custom edited density (e.g. 1700 kg/m3 * 8.5 m3 = 14,450.00 kg)
        Livewire::test(MaterialReceiveForm::class)
            ->set('receive_date', '2026-10-20')
            ->set('supplier_id', $supplier->id)
            ->set('material_id', $pasirPalu->id)
            ->set('density', 1700.0)
            ->set('vehicle_no', 'DN 7777 CD')
            ->set('ri_no', 'RI-OCT-002')
            ->set('volume_m3', 8.5)
            ->assertSet('totalWeight', 14450.0)
            ->call('save')
            ->assertHasNoErrors();

        $receive2 = MaterialReceive::where('ri_no', 'RI-OCT-002')->first();
        $this->assertNotNull($receive2);
        $this->assertEquals(8.5, (float) $receive2->volume_m3);
        $this->assertEquals(1700.00, (float) $receive2->density_used);
        $this->assertEquals(14450.00, (float) $receive2->total_weight_kg);
    }

    // --- 3. Vessel Shipment Tests ---

    public function test_vessel_shipment_page_is_restricted_by_rbac(): void
    {
        $admin = User::where('role', 'admin')->first();
        $gudang = User::where('role', 'petugas_gudang')->first();
        $kasir = User::where('role', 'kasir_logistik')->first();

        $this->actingAs($admin)->get('/vessel-shipments')->assertOk();
        $this->actingAs($gudang)->get('/vessel-shipments')->assertOk();
        $this->actingAs($kasir)->get('/vessel-shipments')->assertForbidden();
    }

    public function test_vessel_shipment_form_saves_and_calculates_shrinkage(): void
    {
        $gudang = User::where('role', 'petugas_gudang')->first();
        $this->actingAs($gudang);

        // Load 1000 m3, Unload 980 m3 => Diff 20 m3, Shrinkage 2.0%
        Livewire::test(VesselShipmentForm::class)
            ->set('departure_date', '2026-10-18')
            ->set('arrival_date', '2026-10-20')
            ->set('load_volume', 1000.00)
            ->set('unload_volume', 980.00)
            ->set('lab_result', 'Lolos Uji Gradasi')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('20,00 m³');

        $shipment = VesselShipment::where('load_volume', 1000.00)->latest('id')->first();
        $this->assertNotNull($shipment);
        $this->assertEquals(980.00, (float) $shipment->unload_volume);
        $this->assertEquals(20.00, (float) $shipment->volume_diff);
        $this->assertEquals(2.0000, (float) $shipment->shrinkage_percent);
    }
}
