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
- Kerangka plugin `inside-apparel-core`: file utama dan loader yang memeriksa WooCommerce aktif. Modul inventory sudah berisi kode (Tahap 1 dan 2). Folder modul membership, voucher, affiliate, notification, shopee masih kosong.
- Kerangka tema `inside-apparel`: `style.css`, `functions.php`, `index.php`.
- WordPress lokal di LocalWP. Plugin Inside Apparel Core sudah aktif.
- Dokumen: MASTER_REQUIREMENT v0.8, INVENTORY_DESIGN v0.1, DATABASE v0.1, DECISIONS, AGENTS.
- Inventory Tahap 1 (plugin v0.2.0), kode ditulis 2026-10-09: installer dan tabel, capability, deklarasi HPOS, `IA_Inventory_Service::adjust()` dan `set_quantity()`, log stok, halaman Posisi Stok (cari, filter, Sesuaikan, Export CSV), Log Stok (filter, Export CSV), Impor Stok Awal (CSV dengan pratinjau).
- Inventory Tahap 2 (plugin v0.3.0), kode ditulis 2026-10-10: `IA_Inventory_Service::record()`; hook WooCommerce (`IA_Inventory_Woo_Hooks`) yang mencatat order dibayar/dibatalkan, edit langsung di layar produk, dan produk baru dengan stok; angka Ditahan, Tersedia, Dalam proses, Fisik (`IA_Stock_Metrics`) di Posisi Stok dan Export; stok opname berbasis Fisik; halaman Pengaturan Stok (hold stock 30 menit, notifikasi stok, matikan backorder). 77 uji logika (Tahap 1 + 2) lulus dengan WordPress tiruan.

# 4. BELUM ADA

- Tahap 1 dan 2 BELUM diuji di WordPress/WooCommerce sungguhan (hanya diuji dengan tiruan). Uji di LocalWP sebelum lanjut Tahap 3.
- Asumsi tentang internal WooCommerce yang harus dibuktikan di LocalWP: (a) hook `woocommerce_reduce_order_stock` dan `woocommerce_restore_order_stock` terpicu dan item order memakai meta `_reduced_stock`; (b) `woocommerce_before/after_product_object_save` terpicu untuk produk dan variasi; (c) `ReserveStock::get_reserved_stock()` ada untuk angka Ditahan; (d) status stok produk berubah ke Habis saat stok 0.
- Belum tercatat di log: perubahan stok dari fitur refund WooCommerce ("Restock refunded items") dan penyesuaian item di layar edit order. Retur dicatat lewat Sesuaikan > "Retur dikembalikan ke stok".
- Dokumen design untuk order, membership, voucher, affiliate, notifikasi, Shopee belum ada.

---

# 5. LANGKAH BERIKUTNYA (URUT)

1. Owner menguji Tahap 1 dan 2 di LocalWP (checklist uji ada di pesan serah terima sesi 2026-10-09 dan 2026-10-10) dan melaporkan hasilnya. Butir [USULAN] D-017 sampai D-022 dianggap disetujui kecuali owner menyatakan lain.
2. Inventory Tahap 3 (butuh akses Shopee Open Platform): pemetaan SKU, token, push stok dengan antrean dan retry. Sebelum itu, tulis dokumen desain order management dan retur (Tahap 4 bergantung padanya).
3. Tulis dokumen desain berikutnya: order management dan retur, lalu membership dan voucher, lalu affiliate.
4. Owner mendaftar sebagai developer Shopee Open Platform (persetujuan bisa lama, jalur kritis Fase 2).

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
