# DATABASE

Version: 0.1
Status: Draft

Dokumen ini mencatat semua perubahan database Inside Apparel (CLAUDE.md bagian 4: perubahan database wajib didokumentasikan).

Aturan:
1. Cek dulu apakah WordPress atau WooCommerce sudah menyediakan penyimpanan yang cocok.
2. Buat tabel custom hanya jika ada kebutuhan yang jelas, dan catat alasannya di sini.
3. Semua tabel memakai prefix `$wpdb->prefix` dan dibuat lewat `dbDelta()`.

---

# 1. DATA YANG MEMAKAI PENYIMPANAN WORDPRESS / WOOCOMMERCE

| Data | Tempat penyimpanan | Catatan |
|------|--------------------|---------|
| Produk, variasi, SKU, harga, stok | Produk WooCommerce | Stok diubah lewat fungsi WooCommerce, bukan menulis meta langsung |
| Order website | Order WooCommerce | Diakses lewat CRUD API agar kompatibel dengan HPOS |
| Customer | Pengguna WordPress dan data customer WooCommerce | Lifetime purchase dan level membership dirancang di dokumen membership |
| Harga pokok per SKU | Meta produk (rencana: `_ia_cost_price`) | Internal, tidak tampil ke customer **[USULAN]** |
| Barcode per SKU | Meta produk (rencana: `_ia_barcode`) | **[USULAN]** |
| Diskon affiliate per produk | Meta produk (rencana: `_ia_affiliate_discount_pct`) | 1-3%, default 1% bila kosong |

Nama meta di atas masih rencana dan dikonfirmasi saat modul terkait dibuat.

---

# 2. TABEL CUSTOM

## 2.1 `ia_stock_log`

Alasan: WooCommerce tidak menyimpan riwayat perubahan stok per SKU. Dibutuhkan untuk audit, filter per SKU, dan ekspor.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | BIGINT UNSIGNED, PK, auto increment | |
| `product_id` | BIGINT UNSIGNED | ID produk atau variasi |
| `sku` | VARCHAR(100) | SKU saat perubahan terjadi |
| `delta` | INT | Selisih (negatif untuk pengurangan) |
| `qty_before` | INT | Stok sebelum perubahan |
| `qty_after` | INT | Stok sesudah perubahan |
| `reason` | VARCHAR(40) | Kode alasan (lihat INVENTORY_DESIGN.md bagian 4.3) |
| `source` | VARCHAR(20) | `website`, `shopee`, `admin`, `system`, `pos` (disiapkan) |
| `ref_type` | VARCHAR(20) NULL | `order`, `shopee_order`, `adjustment`, `import` |
| `ref_id` | VARCHAR(64) NULL | ID referensi (ID order, `order_sn`, dan sebagainya) |
| `note` | TEXT NULL | Catatan admin |
| `user_id` | BIGINT UNSIGNED NULL | Pengguna yang melakukan perubahan (NULL untuk sistem) |
| `created_at` | DATETIME | Waktu dalam UTC |

Indeks: `(sku, created_at)`, `(product_id)`, `(ref_type, ref_id)`.

Aturan: append-only. Tidak ada fitur ubah atau hapus dari antarmuka. Retensi: disimpan tanpa batas pada skala saat ini (sekitar 100 order per hari sangat kecil untuk ukuran tabel).

## 2.2 `ia_channel_sku_map`

Alasan: memetakan SKU pusat ke item/model di channel luar dan menyimpan status sinkronisasinya. Dibutuhkan query seperti "semua SKU yang gagal sinkron", yang tidak efisien jika disimpan sebagai meta produk.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | BIGINT UNSIGNED, PK, auto increment | |
| `sku` | VARCHAR(100) | SKU pusat |
| `product_id` | BIGINT UNSIGNED | ID produk atau variasi |
| `channel` | VARCHAR(20) | Saat ini `shopee` |
| `channel_item_id` | VARCHAR(64) | ID item di channel |
| `channel_model_id` | VARCHAR(64) NULL | ID model/variasi di channel |
| `last_pushed_qty` | INT NULL | Stok terakhir yang berhasil dikirim |
| `last_pushed_at` | DATETIME NULL | Waktu pengiriman terakhir (UTC) |
| `sync_status` | VARCHAR(20) | `ok`, `pending`, `failed`, `unmapped` |
| `retry_count` | SMALLINT UNSIGNED | Jumlah percobaan ulang |
| `last_error` | TEXT NULL | Pesan error terakhir (tanpa token) |
| `updated_at` | DATETIME | UTC |

Indeks: `UNIQUE (channel, sku)`, `(sync_status)`.

---

# 3. KEBIJAKAN MIGRASI

- Tabel dibuat dan diperbarui lewat `dbDelta()` saat aktivasi plugin dan saat versi database berubah. Versi disimpan di opsi `ia_core_db_version`.
- Menonaktifkan plugin tidak menghapus tabel atau data.
- Penghapusan data atau tabel (uninstall) tidak dilakukan otomatis. Itu perubahan destruktif yang memerlukan persetujuan owner (CLAUDE.md bagian 8).
- Semua waktu disimpan dalam UTC dan ditampilkan dalam WIB.

---

# 4. RIWAYAT PERUBAHAN

| Versi DB | Tanggal | Perubahan |
|----------|---------|-----------|
| 1 | 2026-10-09 | Membuat `ia_stock_log` dan `ia_channel_sku_map` lewat `IA_Inventory_Installer` (plugin v0.2.0). Capability `ia_view_stock`, `ia_manage_stock`, `ia_manage_channels` ditambahkan ke Administrator dan Shop Manager. Tabel `ia_channel_sku_map` sudah dibuat tetapi belum dipakai (Tahap 3). |