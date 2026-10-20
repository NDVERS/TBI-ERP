# Product Requirements Document (PRD): TBI - Batching Plant ERP System

## 1. Project Overview & Core Objectives

- **Nama Project:** TBI-ERP (TB Indonesia - Batching Plant & Material Management System)
- **Target Perangkat:** Laptop (Admin, Manajemen, Kasir SO/PO) & Tablet (Operator Batching Plant, Petugas Gudang, Surveyor Opname Lapangan).
- **Tech Stack:**
  - **Backend:** Laravel 11/12 (PHP 8.5+), MySQL (InnoDB Engine via XAMPP).
  - **Frontend:** Laravel Blade + Livewire 3 + Alpine.js + Tailwind CSS.
  - **Target AI Agent:** Gemini 3.8 Flash Medium (Google Antigravity IDE).
- **Tujuan Utama (CRITICAL):**
  - **Zero Formula Exposure (Strict IP Protection):** Menyembunyikan 100% rumus perhitungan bisnis (komposisi resep mutu beton/Job Mix, _waste factor_ 5%, berat jenis/density material, rasio solar, dan rumus geometri Stock Opname) dari seluruh user kecuali role `admin`.
  - **Data Integrity:** Menggantikan relasi manual `VLOOKUP` dan `SUMIFS` dari spreadsheet menjadi relasi _Foreign Key_ database yang konsisten tanpa risiko error `#N/A`.

---

## 2. Strict Architectural & Security Rules (Zero Formula Exposure)

> **INSTRUKSI WAJIB UNTUK AI AGENT (GEMINI 3.8 FLASH MEDIUM):**
> Patuhi aturan keamanan di bawah ini pada setiap pembuatan Controller, Livewire Component, Blade View, dan API Response.

### 2.1. Dilarang Menghitung di Client-Side

- **JANGAN PERNAH** menaruh rumus perhitungan bisnis di dalam file `.blade.php`, variabel Alpine.js (`x-data`), atau file `.js` eksternal.
- Seluruh perhitungan wajib dieksekusi di dalam `app/Services/` di sisi server (PHP).

### 2.2. Isolasi Payload Livewire & JSON

- Pada komponen Livewire yang diakses oleh non-admin (`operator_produksi`, `petugas_gudang`, `surveyor_opname`), **dilarang mendeklarasikan properti `public`** yang menyimpan nilai koefisien rahasia (seperti `$density`, `$wasteFactor`, `$jobMixRecipe`).
- Gunakan metode `protected` atau langsung panggil `Service Class` saat proses `save()`, lalu hanya tampilkan output yang diizinkan sesuai _role_.

### 2.3. Pembatasan Visibilitas Berdasarkan Role (RBAC)

- **`admin` (Manajemen / Superuser):** Memiliki akses penuh ke seluruh modul, bisa melihat & mengedit _Master Job Mix_, _Berat Jenis_, koefisien rumus, harga PO, serta tabel _Rekonsiliasi Stok_.
- **`kasir_logistik` (Admin SO & PO):** Hanya menginput _Sales Order_ (pilih Customer, Mutu Beton, Volume $\text{m}^3$, Jarak KM) dan _Purchase Order_. Tidak bisa melihat rincian resep komposisi Job Mix per $\text{m}^3$ maupun _waste factor_ `1.05`.
- **`petugas_gudang` (Inbound & Kapal):** Hanya menginput form _Penerimaan Material Truk_ (No. Pol, No. RI, Jam Masuk, Volume $\text{m}^3$) dan _Pengiriman Kapal_ (Volume Muat & Bongkar). Konversi ke Kg berjalan tertutup di server.
- **`operator_produksi` (Batching Plant):** Hanya menginput _Docket Produksi Harian_ (No. Docket, No. Truck Mixer, Jam Load, Volume, dan pemakaian aktual silo/agregat).
- **`surveyor_opname` (Lapangan - Tablet):** Hanya memilih jenis material, memilih bentuk tumpukan (_Limas Terpancung_ / _Kerucut Terpancung_), dan mengisi dimensi meter ($L_a, L_b, R_a, R_b, t$). **Dilarang menampilkan hasil konversi tonase akhir maupun stok teoritis sistem kepada surveyor** agar tidak terjadi manipulasi angka di lapangan.

---

## 3. UI/UX & Front-End Anti-Slop Guidelines

Seluruh antarmuka wajib mematuhi aturan **`C:\Users\V15\.gemini`** (Front-End Anti-Slop Guidelines):

### 3.1. Visual & Layout (Design System Principles)

- **No AI Slop:** Dilarang menggunakan gradient ungu/cyan/neon, _rounded_ berlebihan (`rounded-3xl` / `rounded-full` pada kontainer), atau efek _glassmorphism_ (`backdrop-blur`) mengambang yang tidak fungsional.
- **Color Palette:** Gunakan palet netral tegas berbasis **`zinc`** atau **`slate`**:
  - Background Utama: `bg-zinc-50` (Light) / Surface Card: `bg-white`.
  - Border & Divider: `border-zinc-200` (1px solid tegas, `rounded-md` atau `rounded-lg`).
  - Tipografi: Teks utama `text-zinc-900`, teks sekunder/label `text-zinc-500`. Gunakan `font-mono tabular-nums` untuk seluruh kolom angka, volume, tonase, nomor dokumen, dan jam.
  - Satu Warna Aksen Fungsional: Gunakan `bg-zinc-900 text-white hover:bg-zinc-800` untuk Primary Action, atau `blue-600` khusus untuk indikator fokus/aktif.
  - Status Semantik: `emerald-600` (Sesuai), `amber-600` (Warning/Kelebihan), `red-600` (Kekurangan/Selisih Minus).

### 3.2. Component Architecture (shadcn/ui Style)

- Bangun komponen Blade yang modular dan bebas bloat di `resources/views/components/ui/`:
  - `<x-ui.card>`, `<x-ui.button>`, `<x-ui.input>`, `<x-ui.select>`, `<x-ui.badge>`, `<x-ui.table>`.
- Pisahkan _state/logic_ (di dalam Class Livewire / Service) dari _visual layout_ (Blade view).

### 3.3. Tailwind Practices & Responsiveness (Laptop vs Tablet)

- **Urutan Penulisan Class:** `Layout (flex/grid/block)` $\rightarrow$ `Spacing (p/m/gap)` $\rightarrow$ `Sizing (w/h)` $\rightarrow$ `Typography (text/font)` $\rightarrow$ `Visual/Colors (bg/border/shadow)`.
- **Dilarang memakai _arbitrary values_ acak** (seperti `w-[342px]` atau `mt-[13px]`). Gunakan skala standar Tailwind.
- **Adaptasi Layar Tablet & Laptop:**
  - Gunakan tinggi kontrol input minimal `h-10` (Laptop) dan `h-11` atau `h-12` (Tablet) agar nyaman disentuh jari (_touch-friendly_).
  - Tambahkan atribut `inputmode="decimal"` pada seluruh input angka agar _numpad_ tablet otomatis muncul.

---

## 4. Database Schema Specification (MySQL - InnoDB)

> **Aturan Tipe Data:** Seluruh kolom volume, berat jenis, tonase, persentase, dan harga **WAJIB** menggunakan `DECIMAL`, **DILARANG** menggunakan `FLOAT` atau `DOUBLE`.

### 4.1. Tabel Master (`materials`, `suppliers`, `customers`, `job_mixes`)

- **`materials`**:
  - `id`, `name` (string, unique), `unit` (string: Kg/Liter), `density_kg_m3` (`DECIMAL(10,2)` — hanya terlihat oleh Admin), `initial_stock_kg` (`DECIMAL(15,2)`), `notes` (nullable).
- **`suppliers`**:
  - `id`, `name`, `material_type`, `lead_time_days` (integer, nullable).
- **`customers`**:
  - `id`, `name`, `address` (nullable), `default_distance_km` (`DECIMAL(8,2)`).
- **`job_mixes`** _(Tabel Rahasia Admin)_:
  - `id`, `mutu_code` (unique, misal: `K100 / C`, `K300 / C`, `FC35NCPC`, `B0`), `semen_kg` (`DECIMAL(10,2)`), `pasir_palu_kg` (`DECIMAL(10,2)`), `pasir_lokal_kg` (`DECIMAL(10,2)`), `split_1_2_kg` (`DECIMAL(10,2)`), `split_2_3_kg` (`DECIMAL(10,2)`), `additive_liter` (`DECIMAL(10,2)`), `waste_factor` (`DECIMAL(5,4)` default `1.0500`).

### 4.2. Tabel Transaksi Operasional

- **`sales_orders`**:
  - `id`, `so_number` (unique: `SO/01/TBI/YY/MM/XXXX`), `order_date` (date), `customer_id` (FK), `location` (string), `job_mix_id` (FK), `volume_m3` (`DECIMAL(10,2)`), `distance_km` (`DECIMAL(8,2)`).
  - Kolom hasil kalkulasi server (hanya terlihat oleh Admin): `est_semen_kg`, `est_pasir_palu_kg`, `est_pasir_lokal_kg`, `est_split_1_2_kg`, `est_split_2_3_kg`, `est_additive_liter`, `est_solar_liter` (`DECIMAL(12,2)`).
- **`purchase_orders`**:
  - `id`, `po_number` (unique: `PO-PCR/01/TBI/YY/MM/XXXX`), `po_date` (date), `supplier_id` (FK), `vessel_name` (nullable), `material_id` (FK), `specification` (nullable), `qty_ordered` (`DECIMAL(15,2)`), `unit_price` (`DECIMAL(15,2)`), `subtotal` (`DECIMAL(15,2)`), `ppn_amount` (`DECIMAL(15,2)`), `grand_total` (`DECIMAL(15,2)`).
- **`vessel_shipments`** _(Pengiriman Kapal)_:
  - `id`, `departure_date`, `arrival_date`, `purchase_order_id` (FK, nullable), `load_volume` (`DECIMAL(12,2)`), `unload_volume` (`DECIMAL(12,2)`), `volume_diff` (`DECIMAL(12,2)`), `shrinkage_percent` (`DECIMAL(8,4)`), `lab_result` (string, nullable), `document_path` (string, nullable).
- **`material_receives`** _(Penerimaan Harian)_:
  - `id`, `receive_date` (date), `supplier_id` (FK), `material_id` (FK), `vehicle_no` (string), `ri_no` (string), `time_in` (time), `volume_m3` (`DECIMAL(10,2)`), `density_used` (`DECIMAL(10,2)`), `total_weight_kg` (`DECIMAL(15,2)`).
- **`production_dockets`** _(Produksi Harian)_:
  - `id`, `docket_no` (unique: `SP01_XXXX`), `production_date` (date), `sales_order_id` (FK, nullable), `truck_no` (string: `TM 05`), `customer_name`, `address`, `mutu_code`, `time_load` (time), `volume_m3` (`DECIMAL(10,2)`), `daily_cumulative_m3` (`DECIMAL(10,2)`), `load_time_diff` (time, nullable).
  - Kolom Pemakaian Target (`_tar`) & Aktual (`_act`) bertipe `DECIMAL(12,2)`: `silo1_tar`, `silo1_act`, `silo2_tar`, `silo2_act`, `pasir_palu_tar`, `pasir_palu_act`, `pasir_lokal_tar`, `pasir_lokal_act`, `split_1_2_tar`, `split_1_2_act`, `split_2_3_tar`, `split_2_3_act`, `water_tar`, `water_act`, `admixture_tar`, `admixture_act`.
- **`fleet_trip_logs`** _(Sheet Sparepart & BBM)_:
  - `id`, `sales_order_id` (FK), `location_km` (`DECIMAL(8,2)`), `trip_distance_km` (`DECIMAL(8,2)`), `duration_hours` (`DECIMAL(6,2)`), `total_fuel_liter` (`DECIMAL(10,2)`), `est_tire_cost` (`DECIMAL(15,2)`), `est_sparepart_cost` (`DECIMAL(15,2)`), `grand_total` (`DECIMAL(15,2)`), `status` (string).
- **`stock_opnames`** _(Opname Manual & Drone)_:
  - `id`, `opname_date` (date), `material_id` (FK), `method` (`enum: limas_terpancung, kerucut_terpancung, drone`), `dimensions_json` (JSON: menyimpan input mentah panjang/lebar atas, bawah, jari-jari, tinggi $t$, stock bin), `calculated_volume_m3` (`DECIMAL(12,4)`), `calculated_weight_kg` (`DECIMAL(15,2)`), `created_by` (FK users).

---

## 5. Backend Business Logic & Hidden Formulas (`app/Services/`)

Seluruh rumus berikut **wajib dienkapsulasi di dalam `app/Services/`** dan tidak boleh terekspos ke user non-admin:

### 5.1. DocumentNumberService.php

- Format SO: `SO/01/TBI/{YY}/{MM}/{0001}` (Reset nomor urut ke `0001` setiap pergantian bulan berdasarkan `order_date`).
- Format PO: `PO-PCR/01/TBI/{YY}/{MM}/{0001}` (Reset nomor urut setiap bulan berdasarkan `po_date`).

### 5.2. JobMixCalculatorService.php

- Rumus Estimasi Material SO:
  $$\text{Kebutuhan Material} = \text{Komposisi Job Mix per m}^3 \times \text{Volume SO} \times 1.05$$
- Rumus Estimasi Solar SO:
  $$\text{Solar (Liter)} = \left(\frac{1}{1.2}\right) \times \text{Jarak Lokasi (KM)}$$

### 5.3. ProcurementCalculatorService.php

- Rumus PO: $\text{Subtotal} = \text{Qty} \times \text{Harga Satuan}$, $\text{PPN} = \text{Subtotal} \times 11\%$, $\text{Grand Total} = \text{Subtotal} + \text{PPN}$.
- Rumus Susut Kapal: $\text{Selisih} = \text{Vol Muat} - \text{Vol Bongkar}$, $\text{Susut (\%)} = \frac{\text{Selisih}}{\text{Vol Muat}}$.
- Rumus Penerimaan Material: $\text{Total Berat (Kg)} = \text{Volume (m}^3\text{)} \times \text{Berat Jenis Material (kg/m}^3\text{)}$.

### 5.4. OpnameGeometryService.php

- **Limas Terpancung:**
  $$L_a = \frac{p_{a1} + p_{a2}}{2} \times \frac{l_{a1} + l_{a2}}{2}, \quad L_b = \frac{p_{b1} + p_{b2}}{2} \times \frac{l_{b1} + l_{b2}}{2}$$
  $$V = \frac{1}{3} \times t \times \left(L_a + L_b + \sqrt{L_a \times L_b}\right)$$
- **Kerucut Terpancung:**
  $$V = \frac{1}{3} \times 3.14 \times t \times \left(R_a^2 + R_b^2 + (R_a \times R_b)\right)$$
- **Konversi Tonase Akhir (Kg):**
  $$\text{Total Berat (Kg)} = \text{Stock Bin} + (V \times \text{Berat Jenis})$$

### 5.5. StockReconciliationService.php (Khusus Admin)

- $\text{Stok Akhir Teoritis} = \text{Stok Awal Bulan} + \sum \text{Penerimaan Bulan Ini} - \sum \text{Pemakaian Produksi Bulan Ini}$
- $\text{Selisih Stok} = \text{Stok Akhir Teoritis} - \text{Stok Fisik Opname}$
- $\text{Persentase Selisih} = \frac{\vert{}\text{Selisih Stok}\vert{}}{\text{Stok Akhir Teoritis}}$
- Status: Jika $\text{Teoritis} = \text{Fisik} \rightarrow \text{"SESUAI"}$, Jika $\text{Teoritis} < \text{Fisik} \rightarrow \text{"KELEBIHAN"}$, Jika $\text{Teoritis} > \text{Fisik} \rightarrow \text{"KEKURANGAN"}$.

---

## 6. Step-by-Step Implementation Roadmap (Untuk Eksekusi Bertahap di IDE)

Agar pembuatan kode tetap bersih dan bebas _hallucination_, kerjakan secara berurutan per fase:

- **Phase 1: Foundation, Auth, RBAC & Seeder**
  - Setup Migration semua tabel dengan tipe `DECIMAL`.
  - Buat `DatabaseSeeder` berisi data awal asli `TBI - ERP.xlsx` (10 Material utama beserta berat jenisnya, daftar Supplier, Customer, dan 24 resep _Master Job Mix_ `K100` s/d `K350`).
  - Buat Middleware Role (`admin`, `kasir_logistik`, `petugas_gudang`, `operator_produksi`, `surveyor_opname`).
- **Phase 2: Base UI Components (shadcn/ui Blade Style)**
  - Buat layout responsif (Sidebar di Laptop, Drawer/Top Nav di Tablet) sesuai _Front-End Anti-Slop Guidelines_.
  - Buat komponen reusable `<x-ui.*>`.
- **Phase 3: Backend Calculation Services**
  - Implementasi ke-5 _Service Class_ di `app/Services/` beserta _Unit Test_ sederhana untuk memvalidasi kecocokan hasil hitung dengan angka di Excel.
- **Phase 4: Operational Modules (Tablet & Laptop Forms)**
  - Modul Penerimaan Material & Kapal, Sales Order, Purchase Order, Produksi Harian (Docket), dan Form Geometri Stock Opname.
- **Phase 5: Executive Dashboard & Rekonsiliasi Stok (Admin Only)**
  - Halaman Rekap Mingguan dan Tabel Rekonsiliasi Stok otomatis beserta fitur Export ke `.xlsx` / `.pdf` (Values Only — tanpa menyertakan rumus Excel).
