<?php

namespace Tests\Feature;

use App\Livewire\MaterialReceiveForm;
use App\Livewire\SalesOrderForm;
use App\Livewire\StockOpnameForm;
use App\Livewire\UserManagement;
use App\Models\Customer;
use App\Models\JobMix;
use App\Models\Material;
use App\Models\StockOpname;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QolAndDecimalNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_opname_form_normalizes_commas_to_dots_for_decimals(): void
    {
        $surveyor = User::factory()->create([
            'role' => 'surveyor_opname',
            'is_active' => true,
        ]);

        $material = Material::create([
            'code' => 'PASIR-PALU',
            'name' => 'Pasir Palu (Kasar)',
            'unit' => 'm3',
            'density_kg_m3' => 1400.00,
            'initial_stock' => 100.00,
            'is_active' => true,
        ]);

        $this->actingAs($surveyor);

        Livewire::test(StockOpnameForm::class)
            ->set('opname_date', now()->format('Y-m-d'))
            ->set('material_id', $material->id)
            ->set('shape_type', 'limas_terpancung')
            ->set('tinggi', '3,5')
            ->set('la', '15,75')
            ->set('lb', '45,25')
            ->set('stock_bin', '150,5')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('stock_opnames', [
            'material_id' => $material->id,
            'method' => 'limas_terpancung',
        ]);

        $opname = StockOpname::where('material_id', $material->id)->first();
        $this->assertNotNull($opname);
        $this->assertEquals(3.5, $opname->dimensions_json['tinggi']);
        $this->assertEquals(15.75, $opname->dimensions_json['la']);
        $this->assertEquals(45.25, $opname->dimensions_json['lb']);
        $this->assertEquals(150.5, $opname->dimensions_json['stock_bin']);
    }

    public function test_material_receive_form_normalizes_commas_to_dots(): void
    {
        $gudang = User::factory()->create([
            'role' => 'petugas_gudang',
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'name' => 'PT Pasir Sejahtera',
            'contact_person' => 'Budi',
            'phone' => '08123456789',
        ]);

        $material = Material::create([
            'code' => 'SEMEN-TONASA',
            'name' => 'Semen Tonasa Bulk',
            'unit' => 'Kg',
            'density_kg_m3' => 1250.00,
            'initial_stock' => 50000.00,
            'is_active' => true,
        ]);

        $this->actingAs($gudang);

        Livewire::test(MaterialReceiveForm::class)
            ->set('receive_date', now()->format('Y-m-d'))
            ->set('supplier_id', $supplier->id)
            ->set('material_id', $material->id)
            ->set('density', '1250,75')
            ->set('vehicle_no', 'DN 8899 AB')
            ->set('ri_no', 'RI-202610-0099')
            ->set('volume_m3', '24,5')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('material_receives', [
            'vehicle_no' => 'DN 8899 AB',
            'volume_m3' => 24.50,
            'density_used' => 1250.75,
            'total_weight_kg' => 30643.38,
        ]);
    }

    public function test_sales_order_form_normalizes_commas_to_dots(): void
    {
        $kasir = User::factory()->create([
            'role' => 'kasir_logistik',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'name' => 'PT Konstruksi Jaya Abadi',
            'phone' => '081299998888',
            'address' => 'Jl. Veteran No. 10',
        ]);

        $jobMix = JobMix::where('mutu_code', 'K250 / C')->first() ?? JobMix::create([
            'mutu_code' => 'K250 / C',
            'description' => 'Beton K250 / C Slump 12',
            'composition_json' => [
                'semen_kg' => 380,
                'pasir_palu_kg' => 300,
                'pasir_lokal_kg' => 400,
                'split_1_2_kg' => 500,
                'split_2_3_kg' => 500,
                'additive_liter' => 2.5,
            ],
            'price_per_m3' => 950000.00,
        ]);

        $this->actingAs($kasir);

        Livewire::test(SalesOrderForm::class)
            ->set('order_date', now()->format('Y-m-d'))
            ->set('customer_id', $customer->id)
            ->set('location', 'Proyek Jembatan Palu IV')
            ->set('job_mix_id', $jobMix->id)
            ->set('volume_m3', '12,5')
            ->set('distance_km', '8,75')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sales_orders', [
            'location' => 'Proyek Jembatan Palu IV',
            'volume_m3' => 12.50,
            'distance_km' => 8.75,
        ]);
    }

    public function test_user_management_modals_have_esc_and_backdrop_and_loading(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(UserManagement::class)
            ->call('openCreateModal')
            ->assertSeeHtml('@keydown.escape.window="$wire.closeModal()"')
            ->assertSeeHtml('wire:click.self="closeModal"')
            ->assertSeeHtml('wire:loading wire:target="save"')
            ->call('openResetPasswordModal', $admin->id)
            ->assertSeeHtml('@keydown.escape.window="$wire.closePasswordModal()"')
            ->assertSeeHtml('wire:click.self="closePasswordModal"')
            ->assertSeeHtml('wire:loading wire:target="resetPassword"');
    }
}
