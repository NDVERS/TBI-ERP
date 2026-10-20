<?php

namespace Tests\Feature;

use App\Livewire\VesselShipmentForm;
use App\Models\Material;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VesselShipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VesselShipmentSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_vessel_shipment_dropdown_only_lists_in_transit_and_loaded_purchase_orders(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas_gudang']);
        $supplier = Supplier::first();
        $material = Material::first();

        // PO 1: Menunggu Konfirmasi (Tidak boleh muncul)
        $poWaiting = PurchaseOrder::create([
            'po_number' => 'PO-PCR/01/TBI/26/10/8001',
            'po_date' => now()->format('Y-m-d'),
            'supplier_id' => $supplier->id,
            'vessel_name' => 'TB. WAITING',
            'status' => 'Menunggu Konfirmasi',
            'material_id' => $material->id,
            'qty_ordered' => 1000,
            'unit_price' => 100000,
            'subtotal' => 100000000,
            'ppn_amount' => 11000000,
            'grand_total' => 111000000,
        ]);

        // PO 2: Sudah Dimuat (Harus muncul)
        $poLoaded = PurchaseOrder::create([
            'po_number' => 'PO-PCR/01/TBI/26/10/8002',
            'po_date' => now()->format('Y-m-d'),
            'supplier_id' => $supplier->id,
            'vessel_name' => 'TB. LOADED 01',
            'status' => 'Sudah Dimuat',
            'material_id' => $material->id,
            'qty_ordered' => 2000,
            'unit_price' => 100000,
            'subtotal' => 200000000,
            'ppn_amount' => 22000000,
            'grand_total' => 222000000,
        ]);

        // PO 3: Dalam Perjalanan (Harus muncul)
        $poInTransit = PurchaseOrder::create([
            'po_number' => 'PO-PCR/01/TBI/26/10/8003',
            'po_date' => now()->format('Y-m-d'),
            'supplier_id' => $supplier->id,
            'vessel_name' => 'TB. TRANSIT 02',
            'status' => 'Dalam Perjalanan',
            'material_id' => $material->id,
            'qty_ordered' => 3000,
            'unit_price' => 100000,
            'subtotal' => 300000000,
            'ppn_amount' => 33000000,
            'grand_total' => 333000000,
        ]);

        // PO 4: Sudah Tiba di Pelabuhan (Tidak boleh muncul lagi untuk kedatangan baru)
        $poArrived = PurchaseOrder::create([
            'po_number' => 'PO-PCR/01/TBI/26/10/8004',
            'po_date' => now()->format('Y-m-d'),
            'supplier_id' => $supplier->id,
            'vessel_name' => 'TB. ARRIVED',
            'status' => 'Sudah Tiba di Pelabuhan',
            'material_id' => $material->id,
            'qty_ordered' => 4000,
            'unit_price' => 100000,
            'subtotal' => 400000000,
            'ppn_amount' => 44000000,
            'grand_total' => 444000000,
        ]);

        Livewire::actingAs($petugas)
            ->test(VesselShipmentForm::class)
            ->assertSee($poLoaded->po_number)
            ->assertSee('TB. LOADED 01')
            ->assertSee($poInTransit->po_number)
            ->assertSee('TB. TRANSIT 02')
            ->assertDontSee($poWaiting->po_number)
            ->assertDontSee($poArrived->po_number);
    }

    public function test_saving_vessel_shipment_automatically_updates_po_status_to_sudah_tiba_di_pelabuhan_and_sets_actual_arrival_date(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas_gudang']);
        $supplier = Supplier::first();
        $material = Material::first();

        $po = PurchaseOrder::create([
            'po_number' => 'PO-PCR/01/TBI/26/10/8005',
            'po_date' => now()->subDays(5)->format('Y-m-d'),
            'supplier_id' => $supplier->id,
            'vessel_name' => 'TB. KAIROS JAYA',
            'status' => 'Dalam Perjalanan',
            'material_id' => $material->id,
            'qty_ordered' => 2500,
            'unit_price' => 150000,
            'subtotal' => 375000000,
            'ppn_amount' => 41250000,
            'grand_total' => 416250000,
            'actual_departure_date' => now()->subDays(3)->format('Y-m-d'),
            'actual_arrival_date' => null,
        ]);

        $arrivalDate = now()->format('Y-m-d');

        Livewire::actingAs($petugas)
            ->test(VesselShipmentForm::class)
            ->set('purchase_order_id', $po->id)
            ->set('departure_date', now()->subDays(3)->format('Y-m-d'))
            ->set('arrival_date', $arrivalDate)
            ->set('load_volume', '2500.00')
            ->set('unload_volume', '2480.00')
            ->set('lab_result', 'Lolos Uji Kadar Lumpur 1.8%')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Sudah Tiba di Pelabuhan');

        // Verifikasi Vessel Shipment tersimpan
        $shipment = VesselShipment::where('purchase_order_id', $po->id)->first();
        $this->assertNotNull($shipment);
        $this->assertEquals(2500.00, (float) $shipment->load_volume);
        $this->assertEquals(2480.00, (float) $shipment->unload_volume);
        $this->assertEquals(20.00, (float) $shipment->volume_diff);

        // Verifikasi PO otomatis tersinkronisasi
        $po->refresh();
        $this->assertEquals('Sudah Tiba di Pelabuhan', $po->status);
        $this->assertEquals($arrivalDate, $po->actual_arrival_date->format('Y-m-d'));
    }
}
