<?php

namespace Tests\Feature;

use App\Livewire\ExecutiveDashboard;
use App\Models\Customer;
use App\Models\JobMix;
use App\Models\Material;
use App\Models\ProductionDocket;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExecutiveDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_dashboard_route_is_restricted_by_rbac(): void
    {
        $admin = User::where('role', 'admin')->first();
        $surveyor = User::where('role', 'surveyor_opname')->first();
        $operator = User::where('role', 'operator_produksi')->first();

        // Guest redirected to login
        $this->get('/dashboard')->assertRedirect('/login');

        // Admin allowed
        $this->actingAs($admin)->get('/dashboard')->assertOk();

        // Surveyor & Operator forbidden (403)
        $this->actingAs($surveyor)->get('/dashboard')->assertForbidden();
        $this->actingAs($operator)->get('/dashboard')->assertForbidden();
    }

    public function test_executive_dashboard_renders_kpis_and_tables_accurately(): void
    {
        $admin = User::where('role', 'admin')->first();
        $customer = Customer::first();
        $jobMix = JobMix::where('mutu_code', 'K300 / C')->first();
        $supplier = Supplier::first();
        $pasirPalu = Material::where('name', 'Pasir Palu')->first();

        // 1. Buat Sales Order untuk besok (H+1)
        SalesOrder::create([
            'so_number' => 'SO/01/TBI/26/10/0999',
            'order_date' => now()->addDay()->format('Y-m-d'),
            'customer_id' => $customer->id,
            'location' => 'Palu Grand Mall',
            'job_mix_id' => $jobMix->id,
            'volume_m3' => 24.50,
            'distance_km' => 15.00,
            'est_semen_kg' => 8232.00,
            'est_solar_liter' => 12.50,
        ]);

        // 2. Buat Produksi Docket untuk hari ini
        ProductionDocket::create([
            'docket_no' => 'DCK-TODAY-001',
            'production_date' => now()->format('Y-m-d'),
            'time_load' => '08:30',
            'truck_no' => 'TM-01',
            'customer_name' => 'PT WIKA',
            'address' => 'Proyek Jembatan',
            'mutu_code' => 'K300',
            'volume_m3' => 42.50,
            'created_by' => $admin->id,
        ]);

        // 3. Buat Purchase Order aktif terdekat
        PurchaseOrder::create([
            'po_number' => 'PO-PCR/01/TBI/26/10/9991',
            'po_date' => now()->format('Y-m-d'),
            'supplier_id' => $supplier->id,
            'material_id' => $pasirPalu->id,
            'material_type_input' => 'Pasir Palu',
            'qty_ordered' => 2500.00,
            'unit' => 'm³',
            'unit_price' => 0.00,
            'subtotal' => 0.00,
            'ppn_percent' => 11.00,
            'ppn_amount' => 0.00,
            'grand_total' => 0.00,
            'vessel_name' => 'TB. SEJAHTERA / BG. 300 FT',
            'est_arrival_date' => now()->addDays(2)->format('Y-m-d'),
            'status' => 'Dalam Perjalanan',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(ExecutiveDashboard::class)
            ->assertSee('Dashboard Eksekutif Real-Time')
            // Card 1: Kumulatif Cor Hari Ini
            ->assertSee('Kumulatif Cor Hari Ini')
            ->assertSee('42,50')
            ->assertSee('tiket docket diterbitkan hari ini')
            ->assertViewHas('todayDocketCount', 1)
            ->assertViewHas('todayProductionVolume', 42.50)
            // Card 2: PO Logistik Terdekat
            ->assertSee('PO Logistik Terdekat')
            ->assertSee('PO-PCR/01/TBI/26/10/9991')
            ->assertSee('TB. SEJAHTERA / BG. 300 FT')
            // Card 3 & 4
            ->assertSee('Kepatuhan Stok Opname')
            ->assertSee('Estimasi BBM Armada')
            // Tabel Jadwal Cor Besok (H+1)
            ->assertSee('Jadwal Cor Besok (H+1)')
            ->assertSee('Palu Grand Mall')
            ->assertSee('24,50 m³')
            // Top Mutu & Quick Stock Summary
            ->assertSee('Top Mutu Beton Dipesan')
            ->assertSee('Ringkasan Status Stok & Deviasi Material Utama');
    }

    public function test_tomorrow_sales_orders_only_includes_h_plus_one(): void
    {
        $admin = User::where('role', 'admin')->first();
        $customer = Customer::first();
        $jobMix = JobMix::first();

        // SO kemarin (H-1)
        SalesOrder::create([
            'so_number' => 'SO-YESTERDAY',
            'order_date' => now()->subDay()->format('Y-m-d'),
            'customer_id' => $customer->id,
            'job_mix_id' => $jobMix->id,
            'location' => 'Lokasi Kemarin',
            'volume_m3' => 10.00,
        ]);

        // SO hari ini (H)
        SalesOrder::create([
            'so_number' => 'SO-TODAY',
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $customer->id,
            'job_mix_id' => $jobMix->id,
            'location' => 'Lokasi Hari Ini',
            'volume_m3' => 15.00,
        ]);

        // SO besok (H+1)
        SalesOrder::create([
            'so_number' => 'SO-TOMORROW-TARGET',
            'order_date' => now()->addDay()->format('Y-m-d'),
            'customer_id' => $customer->id,
            'job_mix_id' => $jobMix->id,
            'location' => 'Lokasi Besok',
            'volume_m3' => 30.00,
        ]);

        // SO lusa (H+2)
        SalesOrder::create([
            'so_number' => 'SO-DAY-AFTER-TOMORROW',
            'order_date' => now()->addDays(2)->format('Y-m-d'),
            'customer_id' => $customer->id,
            'job_mix_id' => $jobMix->id,
            'location' => 'Lokasi Lusa',
            'volume_m3' => 20.00,
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(ExecutiveDashboard::class);

        // Hanya SO besok yang harus ada di tabel H+1
        $tomorrowOrders = $component->viewData('tomorrowSalesOrders');
        $this->assertTrue($tomorrowOrders->contains('so_number', 'SO-TOMORROW-TARGET'));
        $this->assertFalse($tomorrowOrders->contains('so_number', 'SO-YESTERDAY'));
        $this->assertFalse($tomorrowOrders->contains('so_number', 'SO-TODAY'));
        $this->assertFalse($tomorrowOrders->contains('so_number', 'SO-DAY-AFTER-TOMORROW'));
    }

    public function test_cumulative_production_today_calculates_from_production_dockets(): void
    {
        $admin = User::where('role', 'admin')->first();
        $customer = Customer::first();
        $jobMix = JobMix::first();

        // 2 docket hari ini: 15 m³ + 25 m³ = 40 m³
        ProductionDocket::create([
            'docket_no' => 'DCK-TODAY-A',
            'production_date' => now()->format('Y-m-d'),
            'time_load' => '08:00',
            'truck_no' => 'TM-01',
            'customer_name' => 'PT WIKA',
            'address' => 'Proyek A',
            'mutu_code' => 'K300',
            'volume_m3' => 15.00,
            'created_by' => $admin->id,
        ]);

        ProductionDocket::create([
            'docket_no' => 'DCK-TODAY-B',
            'production_date' => now()->format('Y-m-d'),
            'time_load' => '09:00',
            'truck_no' => 'TM-02',
            'customer_name' => 'PT PP',
            'address' => 'Proyek B',
            'mutu_code' => 'K350',
            'volume_m3' => 25.00,
            'created_by' => $admin->id,
        ]);

        // 1 docket kemarin: 50 m³ (tidak boleh ikut terhitung pada metrik hari ini)
        ProductionDocket::create([
            'docket_no' => 'DCK-YESTERDAY',
            'production_date' => now()->subDay()->format('Y-m-d'),
            'time_load' => '10:00',
            'truck_no' => 'TM-03',
            'customer_name' => 'PT ADHI',
            'address' => 'Proyek C',
            'mutu_code' => 'K250',
            'volume_m3' => 50.00,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(ExecutiveDashboard::class);

        $this->assertEquals(40.00, (float) $component->viewData('todayProductionVolume'));
        $this->assertEquals(2, $component->viewData('todayDocketCount'));
    }

    public function test_nearest_pos_orders_by_nearest_arrival_date_and_excludes_completed(): void
    {
        $admin = User::where('role', 'admin')->first();
        $supplier = Supplier::first();
        $pasirPalu = Material::where('name', 'Pasir Palu')->first();

        // PO A: tiba 5 hari lagi
        $poFar = PurchaseOrder::create([
            'po_number' => 'PO-FAR',
            'po_date' => now()->format('Y-m-d'),
            'supplier_id' => $supplier->id,
            'material_id' => $pasirPalu->id,
            'qty_ordered' => 1000.00,
            'unit' => 'm³',
            'unit_price' => 0.00,
            'subtotal' => 0.00,
            'ppn_percent' => 11.00,
            'ppn_amount' => 0.00,
            'grand_total' => 0.00,
            'est_arrival_date' => now()->addDays(5)->format('Y-m-d'),
            'status' => 'Dalam Perjalanan',
            'created_by' => $admin->id,
        ]);

        // PO B: tiba 2 hari lagi
        $poNear = PurchaseOrder::create([
            'po_number' => 'PO-NEAR',
            'po_date' => now()->format('Y-m-d'),
            'supplier_id' => $supplier->id,
            'material_id' => $pasirPalu->id,
            'qty_ordered' => 1000.00,
            'unit' => 'm³',
            'unit_price' => 0.00,
            'subtotal' => 0.00,
            'ppn_percent' => 11.00,
            'ppn_amount' => 0.00,
            'grand_total' => 0.00,
            'est_arrival_date' => now()->addDays(2)->format('Y-m-d'),
            'status' => 'Dalam Perjalanan',
            'created_by' => $admin->id,
        ]);

        // PO C: tiba 1 hari lagi tapi sudah 'Selesai Seluruhnya'
        $poCompleted = PurchaseOrder::create([
            'po_number' => 'PO-COMPLETED',
            'po_date' => now()->format('Y-m-d'),
            'supplier_id' => $supplier->id,
            'material_id' => $pasirPalu->id,
            'qty_ordered' => 1000.00,
            'unit' => 'm³',
            'unit_price' => 0.00,
            'subtotal' => 0.00,
            'ppn_percent' => 11.00,
            'ppn_amount' => 0.00,
            'grand_total' => 0.00,
            'est_arrival_date' => now()->addDay()->format('Y-m-d'),
            'status' => 'Selesai Seluruhnya',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(ExecutiveDashboard::class);
        $nearestPOs = $component->viewData('nearestPOs');

        // PO Selesai Seluruhnya tidak boleh muncul
        $this->assertFalse($nearestPOs->contains('id', $poCompleted->id));

        // PO Near (2 hari lagi) harus muncul lebih dulu dari PO Far (5 hari lagi)
        $nearIndex = $nearestPOs->search(fn ($item) => $item->id === $poNear->id);
        $farIndex = $nearestPOs->search(fn ($item) => $item->id === $poFar->id);

        $this->assertNotFalse($nearIndex);
        $this->assertNotFalse($farIndex);
        $this->assertLessThan($farIndex, $nearIndex);
    }
}
