# STATUS

Catatan serah terima untuk siapa pun (manusia atau AI) yang melanjutkan pekerjaan. Perbarui file ini di akhir setiap sesi kerja.

Terakhir diperbarui: 2026-10-09

---

# 1. URUTAN BACA

1. `CLAUDE.md` dan `AGENTS.md`: aturan.
2. `docs/DECISIONS.md`: keputusan final. Jangan diubah tanpa persetujuan owner.
3. `docs/MASTER_REQUIREMENT.md` (v0.8): kebutuhan bisnis.
4. `docs/INVENTORY_DESIGN.md` dan `docs/DATABASE.md`: desain stok dan database.
5. File ini: posisi pekerjaan sekarang.

---

# 2. KONTEKS SINGKAT

- Proyek: e-commerce Inside Apparel (apparel), WordPress + WooCommerce.
- Owner: Ridwan. Bahasa kerja: Indonesia.
- Kode custom hanya di plugin `inside-apparel-core` dan tema `inside-apparel`.
- Skala awal: sekitar 100 SKU dan 100 order per hari.
- Channel: website dan Shopee. Backend admin custom dengan acuan Jubelio (Katalog, Penjualan, Persediaan).
- Payment: Midtrans (plugin resmi). Pengiriman: J&T dan JNE lewat Biteship (nanti, resi manual dulu).

---

# 3. SUDAH SELESAI

- Repo GitHub `Ridwan-exe/Inside-Apparel` terhubung, branch `main`.
- `.gitignore` model whitelist: hanya `docs`, `CLAUDE.md`, `AGENTS.md`, `README.md`, plugin `inside-apparel-core`, dan tema `inside-apparel` yang masuk Git.
- Kerangka plugin `inside-apparel-core`: file utama dan loader yang memeriksa WooCommerce aktif. Folder modul (inventory, membership, voucher, affiliate, notification, shopee) masih kosong.
- Kerangka tema `inside-apparel`: `style.css`, `functions.php`, `index.php`.
- WordPress lokal di LocalWP. Plugin Inside Apparel Core sudah aktif.
- Dokumen: MASTER_REQUIREMENT v0.8, INVENTORY_DESIGN v0.1, DATABASE v0.1, DECISIONS, AGENTS.

# 4. BELUM ADA

- Belum ada kode fitur sama sekali (belum ada tabel, service stok, halaman admin).
- Dokumen design baru untuk inventory. Belum ada untuk order, membership, voucher, affiliate, notifikasi, Shopee.

---

# 5. LANGKAH BERIKUTNYA (URUT)

1. Owner membaca MASTER_REQUIREMENT dan INVENTORY_DESIGN, lalu menyetujui atau mengoreksi butir bertanda [USULAN] (D-017 sampai D-020 di DECISIONS).
2. Tulis kode inventory Tahap 1: installer dan tabel (`ia_stock_log`, `ia_channel_sku_map`), `IA_Inventory_Service::adjust()`, log stok, halaman Posisi Stok, Sesuaikan stok, Impor Stok Awal.
3. Inventory Tahap 2: hook WooCommerce (order, batal, edit langsung), angka Ditahan/Dalam proses/Fisik, pengaturan WooCommerce (hold stock 30 menit, backorder mati), deklarasi kompatibilitas HPOS.
4. Tulis dokumen desain berikutnya: order management dan retur, lalu membership dan voucher, lalu affiliate.
5. Owner mendaftar sebagai developer Shopee Open Platform (persetujuan bisa lama, jalur kritis Fase 2).

Aturan kerja: satu fitur satu branch, review silang antar AI, merge lewat pull request. Setiap perubahan database dicatat di `docs/DATABASE.md`, setiap keputusan baru di `docs/DECISIONS.md`.

---

# 6. YANG DITUNDA

- Pendaftaran dan verifikasi akun Biteship (setelah operasional berjalan).
- Analisis margin (menunggu harga pokok dan target margin).
- Pajak atas komisi affiliate (dicek bersama akuntan).
- Safety buffer stok dan penanganan sync gagal otomatis (ditinjau saat order naik).
- POS offline (dirancang bersama nanti).

---

# 7. CATATAN TEKNIS LOKAL (bukan untuk repo)

- Folder site LocalWP ditautkan ke repo lewat junction (atau disalin bila junction tidak terbaca). Cek dengan `dir` di `wp-content\plugins`.
- Di PowerShell, here-string `@'...'@` harus ditulis dalam satu kali jalan, dan penutup `'@` harus di awal baris.