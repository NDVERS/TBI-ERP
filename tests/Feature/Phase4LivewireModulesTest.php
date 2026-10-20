<?php

namespace Tests\Feature;

use App\Livewire\SalesOrderForm;
use App\Livewire\StockOpnameForm;
use App\Models\Customer;
use App\Models\JobMix;
use App\Models\Material;
use App\Models\SalesOrder;
use App\Models\StockOpname;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Phase4LivewireModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_sales_orders_page_is_restricted_by_rbac(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kasir = User::where('role', 'kasir_logistik')->first();
        $surveyor = User::where('role', 'surveyor_opname')->first();
        $operator = User::where('role', 'operator_produksi')->first();

        // Guest is redirected to login
        $this->get('/sales-orders')->assertRedirect('/login');

        // Admin & Kasir can access
        $this->actingAs($admin)->get('/sales-orders')->assertOk();
        $this->actingAs($kasir)->get('/sales-orders')->assertOk();

        // Surveyor & Operator are forbidden (403)
        $this->actingAs($surveyor)->get('/sales-orders')->assertForbidden();
        $this->actingAs($operator)->get('/sales-orders')->assertForbidden();
    }

    public function test_sales_order_form_submits_and_calculates_server_estimates_accurately(): void
    {
        $kasir = User::where('role', 'kasir_logistik')->first();
        $customer = Customer::first();
        $jobMix = JobMix::where('mutu_code', 'K300 / C')->first();

        $this->actingAs($kasir);

        Livewire::test(SalesOrderForm::class)
            ->set('order_date', '2026-10-20')
            ->set('customer_id', $customer->id)
            ->set('location', 'Proyek Jembatan Palu IV')
            ->set('job_mix_id', $jobMix->id)
            ->set('volume_m3', 6.0)
            ->set('distance_km', 12.0)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('SO/01/TBI/26/10/0001');

        // Check Sales Order record in DB
        $so = SalesOrder::where('so_number', 'SO/01/TBI/26/10/0001')->first();
        $this->assertNotNull($so);
        $this->assertEquals(6.0, (float) $so->volume_m3);
        $this->assertEquals(12.0, (float) $so->distance_km);

        // Verify calculated secret estimates match Excel row 7:
        // Semen: 320 * 6.0 * 1.05 = 2016.00
        $this->assertEquals(2016.00, (float) $so->est_semen_kg);
        // Pasir Palu: 648 * 6.0 * 1.05 = 4082.40
        $this->assertEquals(4082.40, (float) $so->est_pasir_palu_kg);
        // Pasir Lokal: 210 * 6.0 * 1.05 = 1323.00
        $this->assertEquals(1323.00, (float) $so->est_pasir_lokal_kg);
        // Split 1/2: 1095 * 6.0 * 1.05 = 6898.50
        $this->assertEquals(6898.50, (float) $so->est_split_1_2_kg);
        // Solar: (1 / 1.2) * 12.0 = 10.00 Liter
        $this->assertEquals(10.00, (float) $so->est_solar_liter);
    }

    public function test_kasir_logistik_cannot_see_secret_material_estimates_in_sales_order_table(): void
    {
        $kasir = User::where('role', 'kasir_logistik')->first();
        $admin = User::where('role', 'admin')->first();
        $customer = Customer::first();
        $jobMix = JobMix::where('mutu_code', 'K300 / C')->first();

        // Create a sales order
        SalesOrder::create([
            'so_number' => 'SO/01/TBI/26/10/0001',
            'order_date' => '2026-10-20',
            'customer_id' => $customer->id,
            'location' => 'Palu Barat',
            'job_mix_id' => $jobMix->id,
            'volume_m3' => 6.0,
            'distance_km' => 12.0,
            'est_semen_kg' => 2016.00,
            'est_solar_liter' => 10.00,
        ]);

        // Kasir cannot see "Estimasi Kebutuhan (Admin)" or the calculated kg
        $this->actingAs($kasir);
        Livewire::test(SalesOrderForm::class)
            ->assertDontSee('Estimasi Kebutuhan (Admin)')
            ->assertDontSee('2016.00 Kg');

        // Admin can see the estimate column
        $this->actingAs($admin);
        Livewire::test(SalesOrderForm::class)
            ->assertSee('Estimasi Kebutuhan (Admin)');
    }

    public function test_stock_opname_page_is_restricted_by_rbac(): void
    {
        $surveyor = User::where('role', 'surveyor_opname')->first();
        $admin = User::where('role', 'admin')->first();
        $kasir = User::where('role', 'kasir_logistik')->first();
        $gudang = User::where('role', 'petugas_gudang')->first();

        // Guest redirected to login
        $this->get('/stock-opnames')->assertRedirect('/login');

        // Surveyor & Admin can access
        $this->actingAs($surveyor)->get('/stock-opnames')->assertOk();
        $this->actingAs($admin)->get('/stock-opnames')->assertOk();

        // Kasir & Gudang forbidden
        $this->actingAs($kasir)->get('/stock-opnames')->assertForbidden();
        $this->actingAs($gudang)->get('/stock-opnames')->assertForbidden();
    }

    public function test_surveyor_tablet_saves_limas_terpancung_opname_without_exposing_volume_or_weight(): void
    {
        $surveyor = User::where('role', 'surveyor_opname')->first();
        // Pasir Palu density: 1658 kg/m3
        $pasirPalu = Material::where('name', 'Pasir Palu')->first();

        $this->actingAs($surveyor);

        // Limas Terpancung: La=100 m2, Lb=400 m2, t=3 m -> V = 1/3 * 3 * (100 + 400 + sqrt(40000)) = 700 m3
        // Weight: 700 * 1658 = 1,160,600 kg
        Livewire::test(StockOpnameForm::class)
            ->set('opname_date', '2026-10-20')
            ->set('material_id', $pasirPalu->id)
            ->set('shape_type', 'limas_terpancung')
            ->set('tinggi', 3.0)
            ->set('la', 100.0)
            ->set('lb', 400.0)
            ->set('stock_bin', 0)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Data Opname Berhasil Disimpan & Dikirim ke Server')
            // Strict Zero Formula Exposure: Surveyor MUST NOT see 700.0000 m³ or 1.160.600 Kg in their view
            ->assertDontSee('700,0000 m³')
            ->assertDontSee('1.160.600');

        // Verify it was correctly stored in database for Admin review
        $opname = StockOpname::where('material_id', $pasirPalu->id)->latest('id')->first();
        $this->assertNotNull($opname);
        $this->assertEquals(700.0, (float) $opname->calculated_volume_m3);
        $this->assertEquals(1160600.0, (float) $opname->calculated_weight_kg);
        $this->assertEquals($surveyor->id, $opname->created_by);
    }

    public function test_surveyor_tablet_saves_kerucut_terpancung_opname(): void
    {
        $surveyor = User::where('role', 'surveyor_opname')->first();
        // Split 2/3 density: 1536 kg/m3
        $split23 = Material::where('name', 'Batu Besar 2/3')->first();

        $this->actingAs($surveyor);

        // Kerucut Terpancung: Ra=2, Rb=5, t=3, PI=3.14
        // V = 1/3 * 3.14 * 3 * (4 + 25 + 10) = 3.14 * 39 = 122.46 m3
        // Weight: 122.46 * 1536 = 188,098.56 kg
        Livewire::test(StockOpnameForm::class)
            ->set('opname_date', '2026-10-20')
            ->set('material_id', $split23->id)
            ->set('shape_type', 'kerucut_terpancung')
            ->set('tinggi', 3.0)
            ->set('ra', 2.0)
            ->set('rb', 5.0)
            ->set('stock_bin', 0)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Data Opname Berhasil Disimpan & Dikirim ke Server');

        $opname = StockOpname::where('material_id', $split23->id)->latest('id')->first();
        $this->assertNotNull($opname);
        $this->assertEquals(122.46, (float) $opname->calculated_volume_m3);
        $this->assertEquals(188098.56, (float) $opname->calculated_weight_kg);
    }

    public function test_admin_can_view_calculated_volume_and_tonnage_in_stock_opname_table(): void
    {
        $admin = User::where('role', 'admin')->first();
        $pasirPalu = Material::where('name', 'Pasir Palu')->first();

        // Create an existing opname record
        StockOpname::create([
            'opname_date' => '2026-10-20',
            'material_id' => $pasirPalu->id,
            'method' => 'limas_terpancung',
            'dimensions_json' => ['shape' => 'limas_terpancung', 'tinggi' => 3, 'la' => 100, 'lb' => 400],
            'calculated_volume_m3' => 700.0,
            'calculated_weight_kg' => 1160600.0,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        // Admin CAN see "Hasil Hitung Server (Admin)" and the volume/weight
        Livewire::test(StockOpnameForm::class)
            ->assertSee('Hasil Hitung Server (Admin)')
            ->assertSee('700,0000')
            ->assertSee('1.160.600');
    }
}
