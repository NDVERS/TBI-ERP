<?php

namespace Tests\Feature;

use App\Livewire\ProductionDocketForm;
use App\Models\Customer;
use App\Models\JobMix;
use App\Models\Material;
use App\Models\ProductionDocket;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\DocumentNumberService;
use App\Services\StockReconciliationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionDocketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_is_redirected_from_production_dockets_page(): void
    {
        $response = $this->get('/production-dockets');
        $response->assertRedirect('/login');
    }

    public function test_unauthorized_role_cannot_access_production_dockets(): void
    {
        $surveyor = User::factory()->create(['role' => 'surveyor_opname']);

        $response = $this->actingAs($surveyor)->get('/production-dockets');
        $response->assertStatus(403);
    }

    public function test_operator_produksi_and_admin_can_access_production_dockets(): void
    {
        $operator = User::factory()->create(['role' => 'operator_produksi']);
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($operator)->get('/production-dockets')->assertStatus(200);
        $this->actingAs($admin)->get('/production-dockets')->assertStatus(200);
    }

    public function test_docket_number_starts_at_sp01_7600_and_increments(): void
    {
        $docService = app(DocumentNumberService::class);

        // Awal mula tanpa data docket
        $this->assertEquals('SP01_7600', $docService->generateDocketNumber());

        $operator = User::factory()->create(['role' => 'operator_produksi']);

        // Buat docket pertama SP01_7600
        ProductionDocket::create([
            'docket_no' => 'SP01_7600',
            'production_date' => Carbon::now()->toDateString(),
            'truck_no' => 'TM-01',
            'customer_name' => 'PT Recta Construction',
            'address' => 'Jl Samanhudi Samarinda',
            'mutu_code' => 'K250 / C',
            'time_load' => '08:00',
            'volume_m3' => 7.00,
            'daily_cumulative_m3' => 7.00,
            'silo1_tar' => 0,
            'silo1_act' => 1960.00,
            'created_by' => $operator->id,
        ]);

        // Docket berikutnya harus otomatis bertambah menjadi SP01_7601
        $this->assertEquals('SP01_7601', $docService->generateDocketNumber());
    }

    public function test_customer_selection_auto_fills_project_address(): void
    {
        $operator = User::factory()->create(['role' => 'operator_produksi']);
        $customer = Customer::first();

        Livewire::actingAs($operator)
            ->test(ProductionDocketForm::class)
            ->set('customer_name', $customer->name)
            ->assertSet('address', strtoupper($customer->address));
    }

    public function test_switching_customer_updates_address_smoothly_without_reverting(): void
    {
        $operator = User::factory()->create(['role' => 'operator_produksi']);

        $customerA = Customer::where('name', 'like', '%Abdul Rahman%')->first() ?? Customer::first();
        $customerB = Customer::where('name', 'like', '%Recta%')->first() ?? Customer::where('id', '!=', $customerA->id)->first();

        // 1. Pilih Customer A via customer_id
        $component = Livewire::actingAs($operator)
            ->test(ProductionDocketForm::class)
            ->set('customer_id', $customerA->id)
            ->assertSet('customer_id', $customerA->id)
            ->assertSet('customer_name', strtoupper($customerA->name))
            ->assertSet('address', strtoupper($customerA->address));

        // 2. Berpindah ke Customer B (misal PT RECTA CONSTRUCTION)
        $component->set('customer_id', $customerB->id)
            ->assertSet('customer_id', $customerB->id)
            ->assertSet('customer_name', strtoupper($customerB->name))
            ->assertSet('address', strtoupper($customerB->address));

        // 3. Berpindah kembali ke Customer A (tidak membal ke B atau default)
        $component->set('customer_id', $customerA->id)
            ->assertSet('customer_id', $customerA->id)
            ->assertSet('customer_name', strtoupper($customerA->name))
            ->assertSet('address', strtoupper($customerA->address));

        // 4. Pengujian langsung via customer_name (backward-compat)
        $component->set('customer_name', $customerB->name)
            ->assertSet('customer_id', $customerB->id)
            ->assertSet('customer_name', strtoupper($customerB->name))
            ->assertSet('address', strtoupper($customerB->address));
    }

    public function test_saving_production_docket_stores_to_database_and_records_activity_log(): void
    {
        $operator = User::factory()->create([
            'name' => 'Operator Andi',
            'role' => 'operator_produksi',
        ]);
        $customer = Customer::first();

        Livewire::actingAs($operator)
            ->test(ProductionDocketForm::class)
            ->set('customer_name', $customer->name)
            ->set('mutu_code', 'K250 / C')
            ->set('truck_no', 'TM-05')
            ->set('volume_m3', '7.00')
            ->set('silo1_act', '1960.00')
            ->set('pasir_palu_act', '4634.00')
            ->set('pasir_lokal_act', '1505.00')
            ->set('split_1_2_act', '7630.00')
            ->set('split_2_3_act', '0.00')
            ->set('water_act', '1400.00')
            ->set('admixture_act', '33.32')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('production_dockets', [
            'docket_no' => 'SP01_7600',
            'truck_no' => 'TM-05',
            'customer_name' => strtoupper($customer->name),
            'mutu_code' => 'K250 / C',
            'volume_m3' => 7.00,
            'silo1_act' => 1960.00,
            'created_by' => $operator->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $operator->id,
            'action' => 'CREATE_PRODUCTION_DOCKET',
        ]);
    }

    public function test_saving_production_docket_reduces_theoretical_stock(): void
    {
        $reconciliationService = app(StockReconciliationService::class);
        $semen = Material::where('name', 'Semen')->first();

        // Cek stok teoritis sebelum input produksi
        $beforeRecon = $reconciliationService->reconcileMaterial($semen);
        $this->assertEquals(34884030.00, $beforeRecon['stok_teoritis']);

        $operator = User::factory()->create(['role' => 'operator_produksi']);

        // Input docket produksi dengan pemakaian semen aktual 4.000 kg
        Livewire::actingAs($operator)
            ->test(ProductionDocketForm::class)
            ->set('customer_name', 'PT Recta Construction')
            ->set('mutu_code', 'K300 / C')
            ->set('truck_no', 'TM-08')
            ->set('volume_m3', '6.00')
            ->set('silo1_act', '4000.00')
            ->set('pasir_palu_act', '3888.00')
            ->set('pasir_lokal_act', '1260.00')
            ->set('split_1_2_act', '6570.00')
            ->set('split_2_3_act', '0.00')
            ->set('water_act', '1200.00')
            ->set('admixture_act', '32.64')
            ->call('save')
            ->assertHasNoErrors();

        // Cek stok teoritis setelah input produksi: 34.884.030 - 4.000 = 34.880.030
        $afterRecon = $reconciliationService->reconcileMaterial($semen);
        $this->assertEquals(34880030.00, $afterRecon['stok_teoritis']);
        $this->assertEquals(4000.00, $afterRecon['pemakaian']);
    }

    public function test_comma_is_normalized_to_decimal_point_on_numeric_inputs(): void
    {
        $operator = User::factory()->create(['role' => 'operator_produksi']);

        Livewire::actingAs($operator)
            ->test(ProductionDocketForm::class)
            ->set('volume_m3', '7,50')
            ->set('silo1_act', '2100,75')
            ->assertSet('volume_m3', '7.50')
            ->assertSet('silo1_act', '2100.75');
    }

    public function test_index_page_displays_stok_akhir_teoritis_column(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Stok Akhir Teoritis');
        // Angka Semen teoritis 34.884.030,00
        $response->assertSee('34.884.030,00');
    }

    public function test_truck_no_auto_prefixes_tm_and_formats_uppercase(): void
    {
        $operator = User::factory()->create(['role' => 'operator_produksi']);
        $customer = Customer::first();

        // Operator hanya memasukkan angka "08"
        Livewire::actingAs($operator)
            ->test(ProductionDocketForm::class)
            ->set('customer_name', $customer->name)
            ->set('mutu_code', 'K250 / C')
            ->set('truck_no', '08')
            ->set('volume_m3', '5.00')
            ->set('silo1_act', '1400.00')
            ->set('pasir_palu_act', '3310.00')
            ->set('pasir_lokal_act', '1075.00')
            ->set('split_1_2_act', '5450.00')
            ->set('split_2_3_act', '0.00')
            ->set('water_act', '1000.00')
            ->set('admixture_act', '23.80')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('production_dockets', [
            'truck_no' => 'TM-08',
            'volume_m3' => 5.00,
        ]);
    }

    public function test_user_inputs_are_automatically_uppercased(): void
    {
        $operator = User::factory()->create(['role' => 'operator_produksi']);

        Livewire::actingAs($operator)
            ->test(ProductionDocketForm::class)
            ->set('customer_name', 'pt recta construction')
            ->set('address', 'jl. samanhudi samarinda')
            ->set('mutu_code', 'k300 / c')
            ->set('truck_no', 'tm-02')
            ->set('volume_m3', '6.00')
            ->set('silo1_act', '1920.00')
            ->set('pasir_palu_act', '3888.00')
            ->set('pasir_lokal_act', '1260.00')
            ->set('split_1_2_act', '6570.00')
            ->set('split_2_3_act', '0.00')
            ->set('water_act', '1200.00')
            ->set('admixture_act', '32.64')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('production_dockets', [
            'customer_name' => 'PT RECTA CONSTRUCTION',
            'address' => 'JL. SAMANHUDI SAMARINDA',
            'mutu_code' => 'K300 / C',
            'truck_no' => 'TM-02',
        ]);
    }

    public function test_consolidated_pasir_palu_stock_balance(): void
    {
        // Pasir Lariang & Pasir Tibo sudah tidak terdaftar sebagai entri terpisah
        $this->assertNull(Material::where('name', 'Pasir Lariang')->first());
        $this->assertNull(Material::where('name', 'Pasir Tibo')->first());

        // Pasir Palu konsolidasi
        $pasirPalu = Material::where('name', 'Pasir Palu')->first();
        $this->assertNotNull($pasirPalu);
        $this->assertEquals(1156452.97, (float) $pasirPalu->initial_stock_kg);

        $reconciliationService = app(StockReconciliationService::class);
        $recon = $reconciliationService->reconcileMaterial($pasirPalu);

        // Awal 1.156.452,97 + Terima 1.754.633,06 = Teoritis 2.911.086,03
        $this->assertEquals(1754633.06, $recon['penerimaan']);
        $this->assertEquals(2911086.03, $recon['stok_teoritis']);
    }

    public function test_production_docket_page_renders_realtime_stock_summary_widget(): void
    {
        $operator = User::factory()->create(['role' => 'operator_produksi']);

        Livewire::actingAs($operator)
            ->test(ProductionDocketForm::class)
            ->assertSee('Stok Akhir Teoritis')
            ->assertSee('Real-Time')
            ->assertSee('Pasir Palu')
            ->assertSee('2.911.086,03')
            ->assertSee('34.884.030,00');
    }

    public function test_selecting_sales_order_auto_fills_customer_address_and_mutu_code(): void
    {
        $operator = User::factory()->create(['role' => 'operator_produksi']);
        $customer = Customer::first();
        $jobMix = JobMix::first();

        $so = SalesOrder::create([
            'so_number' => 'SO/01/TBI/26/10/9991',
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $customer->id,
            'location' => 'PROYEK JEMBATAN KALTENG',
            'job_mix_id' => $jobMix->id,
            'volume_m3' => 50.00,
            'distance_km' => 15.00,
            'status' => 'aktif',
            'delivered_volume_m3' => 0.00,
        ]);

        Livewire::actingAs($operator)
            ->test(ProductionDocketForm::class)
            ->assertSee($so->so_number)
            ->set('sales_order_id', $so->id)
            ->assertSet('customer_id', $customer->id)
            ->assertSet('customer_name', strtoupper($customer->name))
            ->assertSet('address', 'PROYEK JEMBATAN KALTENG')
            ->assertSet('mutu_code', strtoupper($jobMix->mutu_code));
    }

    public function test_submitting_dockets_accumulates_delivered_volume_and_updates_so_status_to_parsial_then_selesai(): void
    {
        $operator = User::factory()->create(['role' => 'operator_produksi']);
        $customer = Customer::first();
        $jobMix = JobMix::first();

        $so = SalesOrder::create([
            'so_number' => 'SO/01/TBI/26/10/9992',
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $customer->id,
            'location' => 'PROYEK FLYOVER SAMARINDA',
            'job_mix_id' => $jobMix->id,
            'volume_m3' => 10.00,
            'distance_km' => 8.00,
            'status' => 'aktif',
            'delivered_volume_m3' => 0.00,
        ]);

        // Pengiriman rit 1: 6 m³ (harus mengubah status SO -> parsial)
        Livewire::actingAs($operator)
            ->test(ProductionDocketForm::class)
            ->set('sales_order_id', $so->id)
            ->set('truck_no', '05')
            ->set('volume_m3', '6.00')
            ->set('silo1_act', '1800')
            ->set('pasir_palu_act', '4000')
            ->set('pasir_lokal_act', '1500')
            ->set('split_1_2_act', '3000')
            ->set('split_2_3_act', '2500')
            ->set('water_act', '1000')
            ->set('admixture_act', '20')
            ->call('save')
            ->assertHasNoErrors();

        $so->refresh();
        $this->assertEquals(6.00, (float) $so->delivered_volume_m3);
        $this->assertEquals('parsial', $so->status);

        // Pengiriman rit 2: 4 m³ (total 10 m³, harus mengubah status SO -> selesai)
        Livewire::actingAs($operator)
            ->test(ProductionDocketForm::class)
            ->set('sales_order_id', $so->id)
            ->set('truck_no', '06')
            ->set('volume_m3', '4.00')
            ->set('silo1_act', '1200')
            ->set('pasir_palu_act', '2800')
            ->set('pasir_lokal_act', '1000')
            ->set('split_1_2_act', '2000')
            ->set('split_2_3_act', '1600')
            ->set('water_act', '650')
            ->set('admixture_act', '15')
            ->call('save')
            ->assertHasNoErrors();

        $so->refresh();
        $this->assertEquals(10.00, (float) $so->delivered_volume_m3);
        $this->assertEquals('selesai', $so->status);
    }

    public function test_production_docket_dropdown_contains_standard_mutu_groups(): void
    {
        $operator = User::factory()->create(['role' => 'operator_produksi']);

        Livewire::actingAs($operator)
            ->test(ProductionDocketForm::class)
            ->assertSee('Seri K (K-Series)')
            ->assertSee('Seri FC & FS (Megapascal & Flexural)', false)
            ->assertSee('K100 / C')
            ->assertSee('K100 / R')
            ->assertSee('K500 / C')
            ->assertSee('K500 / R')
            ->assertSee('FC10')
            ->assertSee('FC20')
            ->assertSee('FC30')
            ->assertSee('FS45');
    }

    public function test_only_admin_can_open_edit_modal_and_update_production_docket(): void
    {
        $admin = User::where('role', 'admin')->first();
        $docket = ProductionDocket::first();

        Livewire::actingAs($admin)
            ->test(ProductionDocketForm::class)
            ->assertSee('Edit')
            ->call('openEditModal', $docket->id)
            ->assertSet('showEditModal', true)
            ->assertSet('editingDocketId', $docket->id)
            ->set('edit_customer_name', 'PT KARYA REVISI')
            ->set('edit_mutu_code', 'K 350')
            ->set('edit_volume_m3', '7.00')
            ->call('updateDocket')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $docket->refresh();
        $this->assertEquals('PT KARYA REVISI', $docket->customer_name);
        $this->assertEquals('K 350', $docket->mutu_code);
        $this->assertEquals(7.00, (float) $docket->volume_m3);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'edit_production_docket',
            'model_id' => $docket->id,
        ]);
    }

    public function test_non_admin_cannot_edit_production_docket(): void
    {
        $operator = User::factory()->create(['role' => 'operator_produksi']);
        $docket = ProductionDocket::first();

        // Di tampilan tidak boleh ada tombol Edit
        Livewire::actingAs($operator)
            ->test(ProductionDocketForm::class)
            ->assertDontSee('Edit Data Surat Jalan (Khusus Admin Master)')
            ->call('openEditModal', $docket->id)
            ->assertStatus(403);
    }

    public function test_pagination_supports_custom_per_page_and_compact_navigation(): void
    {
        $operator = User::factory()->create(['role' => 'operator_produksi']);

        Livewire::actingAs($operator)
            ->test(ProductionDocketForm::class)
            ->assertSee('Tampilkan:')
            ->assertSee('Ke hal:')
            ->set('perPage', 25)
            ->assertSet('perPage', 25)
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2);
    }
}
