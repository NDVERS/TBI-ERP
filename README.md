# TBI-ERP (Ready-Mix Enterprise Resource Planning)

Sistem Enterprise Resource Planning (ERP) Batching Plant dan Logistik Ready-Mix untuk pengelolaan operasional terpadu, pencegahan *data silo*, kalkulasi susut material, dan rekonsiliasi stok bahan baku.

---

## Gambaran Umum Sistem

TBI-ERP dirancang untuk mendigitalisasi dan mengotomatiskan seluruh alur kerja operasional batching plant beton ready-mix, meliputi:

1. **Pengiriman Kapal & Tongkang (Vessel Logistics):** Pencatatan volume muat (*Bill of Lading*) vs volume bongkar di dermaga, kalkulasi otomatis persentase susut kubikasi, dan verifikasi kelayakan uji mutu laboratorium.
2. **Purchase Order (PO):** Manajemen pengadaan logistik material laut dan darat dengan state-machine status satu arah (*Menunggu Konfirmasi* s/d *Selesai Seluruhnya*) dan pencatatan tanggal logistik realisasi.
3. **Penerimaan Material Truk Inbound:** Verifikasi surat jalan penerimaan material, nomor polisi armada, berat kotor/tarra, berat jenis (*density*), dan konversi tonase ke volume ($m^3$).
4. **Produksi Harian & Surat Jalan (Production Docket):** Pencatatan nomor tiket pengiriman rit demi rit (*TM-XXX*), mutu beton, komposisi aktual penimbangan agregat/semen/air/admixture, dan pemotongan stok teoritis otomatis.
5. **Sales Order (SO):** Manajemen pemesanan cor customer, kalkulasi otomatis estimasi kebutuhan material server-side, dan estimasi konsumsi bahan bakar solar armada delivery.
6. **Stock Opname Geometri (Manual & Drone):** Pengukuran timbunan material agregat (pasir & batu pecah) menggunakan formula geometri volume limas terpancung dan kerucut terpancung.
7. **Rekonsiliasi Stok & Dashboard Eksekutif:** Audit berkala deviasi selisih antara stok akhir teoritis (*Stok Awal + Penerimaan - Pemakaian*) terhadap stok fisik opname riil lapangan dengan ambang batas toleransi $\le 0.50\%$.

---

## Tech Stack & Arsitektur

- **Backend Framework:** Laravel 12 (PHP 8.5)
- **Frontend & Reaktivitas:** Livewire 4 & Alpine.js
- **Styling:** Tailwind CSS (Shadcn/UI Design System — Clean Slate/Zinc Monokrom)
- **Database:** MySQL / MariaDB (Skema `DECIMAL` untuk presisi audit tonase & keuangan)
- **Testing:** PHPUnit Feature & Unit Test Suite

---

## Petunjuk Penggunaan Lokal

### 1. Menjalankan Server Pengembangan

```bash
# Menjalankan PHP Development Server
php artisan serve

# Menjalankan Vite Asset Bundler
npm run dev
```

### 2. Menjalankan Pengujian (Test Suite)

```bash
# Menjalankan seluruh test suite
php artisan test --compact

# Menjalankan pengecekan format kode
vendor/bin/pint --dirty --format agent
```

---

## Peran Pengguna & Hak Akses (RBAC)

- **Admin:** Akses penuh seluruh modul operasional, rekonsiliasi stok, manajemen user, dan log aktivitas audit trail.
- **Kasir Logistik:** Akses modul Sales Order dan Purchase Order.
- **Petugas Gudang:** Akses modul Penerimaan Material Truk dan Pengiriman Kapal/Tongkang.
- **Operator Produksi:** Akses modul Surat Jalan / Tiket Produksi Harian (Docket).
- **Surveyor Opname:** Akses modul Pengukuran Fisik Stock Opname di lapangan (tablet friendly).
- **Manajemen:** Akses pemantauan performa pada Dashboard Eksekutif.
