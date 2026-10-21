<?php

namespace Tests\Feature;

use App\Livewire\ProductionDocketForm;
use App\Livewire\PurchaseOrderForm;
use App\Models\Material;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QolSessionOneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_production_docket_form_auto_calculates_total_weight_and_mixture_density(): void
    {
        $operator = User::where('role', 'operator_produksi')->first()
            ?? User::factory()->create(['role' => 'operator_produksi']);

        Livewire::actingAs($operator)
            ->test(ProductionDocketForm::class)
            ->set('volume_m3', '7.0')
            ->set('silo1_act', '2450')       // Semen: 2.450 kg
            ->set('pasir_palu_act', '4900')  // Pasir Palu: 4.900 kg
            ->set('pasir_lokal_act', '1400') // Pasir Lokal: 1.400 kg
            ->set('split_1_2_act', '4200')   // Split 1/2: 4.200 kg
            ->set('split_2_3_act', '2800')   // Split 2/3: 2.800 kg
            ->set('water_act', '1050')       // Air: 1.050 Liter (kg)
            ->set('admixture_act', '35')     // Admixture: 35 Liter (kg)
            ->assertSeeHtml('Ringkasan Batching Aktual')
            ->assertSeeHtml('Densitas Normal');
    }

    public function test_purchase_order_renders_whatsapp_share_and_copy_links(): void
    {
        $admin = User::where('role', 'admin')->first()
            ?? User::factory()->create(['role' => 'admin']);

        $supplier = Supplier::first() ?? Supplier::factory()->create(['name' => 'PT Semen Test']);
        $material = Material::first() ?? Material::factory()->create(['name' => 'Semen Conch']);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-QOL-001',
            'po_date' => now()->toDateString(),
            'supplier_id' => $supplier->id,
            'material_id' => $material->id,
            'qty_ordered' => 5000,
            'unit' => 'Ton',
            'status' => 'Dalam Perjalanan',
            'unit_price' => 0.00,
            'subtotal' => 0.00,
            'ppn_amount' => 0.00,
            'grand_total' => 0.00,
            'vessel_name' => 'BG. TESTER 300',
            'loading_jetty' => 'Jetty Palu',
            'unloading_jetty' => 'Jetty BPM Samarinda',
        ]);

        Livewire::actingAs($admin)
            ->test(PurchaseOrderForm::class)
            ->assertSee('PO-TEST-QOL-001')
            ->assertSeeHtml('wa.me/?text=')
            ->assertSeeHtml('Tersalin!')
            ->assertSeeHtml('Belum Disimpan');
    }
}
