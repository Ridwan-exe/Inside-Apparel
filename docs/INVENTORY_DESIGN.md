# INVENTORY DESIGN

Version: 0.1
Status: Draft
Acuan: `docs/MASTER_REQUIREMENT.md` v0.8 (bagian 1.2, 9, 14) dan `CLAUDE.md`

Keterangan tanda:
- **[USULAN]** = usulan yang menunggu persetujuan owner.
- **[VERIFIKASI]** = harus dicek ke dokumentasi atau akun asli saat integrasi.
- Tanpa tanda = mengikuti keputusan yang sudah final di `docs/DECISIONS.md`.

---

# 1. TUJUAN DAN BATASAN

Tujuan: satu sumber kebenaran stok untuk semua channel (website dan Shopee), dengan risiko overselling sekecil mungkin dan jejak audit lengkap untuk setiap perubahan.

Skala desain: sekitar 100 SKU dan 100 order per hari, tetap nyaman hingga sekitar 10x lipatnya.

Di luar cakupan dokumen ini:
- Penyimpanan dan alur order Shopee (dibahas di dokumen order terpisah). Dokumen ini hanya membahas dampaknya ke stok.
- Multi-gudang, produk bundle/konsinyasi (tidak diperlukan), POS offline, modul Pembelian/Gudang/Keuangan.

---

# 2. PRINSIP

1. **Stok pusat = stok produk/variasi di WooCommerce.** Tidak ada salinan stok lain yang dianggap benar.
2. **Unit stok = SKU varian**, bukan produk induk.
3. **Satu pintu perubahan.** Semua perubahan stok dari kode Inside Apparel lewat `IA_Inventory_Service::adjust()`. Kode tidak menulis meta stok langsung. Perubahan oleh WooCommerce sendiri (order, pembatalan) dan edit langsung di layar WooCommerce tetap tercatat lewat hook.
4. **Log append-only.** Setiap perubahan tercatat dan tidak bisa diedit atau dihapus dari antarmuka.
5. **Kirim nilai absolut ke Shopee**, bukan selisih. Dengan begitu pengiriman ulang aman (idempoten).
6. **Panggilan API Shopee tidak pernah dijalankan saat checkout.** Semuanya lewat antrean latar belakang.
7. **Pusat menang.** Jika stok Shopee berbeda dengan stok pusat, stok pusat ditimpakan ke Shopee.
8. **Sederhana dulu** (CLAUDE.md bagian 6). Tidak ada fitur yang tidak dibutuhkan 100 SKU.

---

# 3. ANGKA STOK

Halaman Posisi Stok menampilkan beberapa angka per SKU (mengacu pada kolom "Stok Gudang" di Jubelio, yang menampilkan beberapa angka sekaligus).

| Angka | Arti | Cara menghitung |
|-------|------|-----------------|
| **Stok WooCommerce** | Stok setelah dikurangi order yang sudah dibayar | `stock_quantity` WooCommerce |
| **Ditahan** | Jumlah pada order website belum dibayar yang masih dalam batas 30 menit | Fitur hold stock WooCommerce |
| **Tersedia** | Stok yang boleh dijual sekarang di semua channel | Stok WooCommerce - Ditahan |
| **Dalam proses** | Jumlah pada order yang sudah dibayar tetapi belum dikirim (processing, packed) | Dihitung dari order |
| **Fisik** | Barang yang secara nyata masih ada di rak | Stok WooCommerce + Dalam proses |

Aturan pemakaian:
- **Yang tampil ke customer dan dikirim ke Shopee adalah Tersedia** **[USULAN]**. Dengan begitu order website yang belum dibayar ikut mengamankan stok selama 30 menit. Tanpa safety buffer (keputusan owner): Tersedia dikirim apa adanya, tidak dikurangi angka cadangan.
- **Stok opname memakai Fisik.** Admin menghitung barang di rak, memasukkan jumlah Fisik, dan sistem menghitung selisihnya terhadap Fisik saat ini.

---

# 4. DATA

## 4.1 Memakai penyimpanan WooCommerce

- Produk, variasi, SKU, stok, dan status stok memakai data produk WooCommerce.
- Order website memakai order WooCommerce, diakses lewat CRUD API (`wc_get_order`, `$order->get_meta`), **bukan** langsung lewat tabel post. Ini wajib agar kompatibel dengan HPOS (High-Performance Order Storage). Plugin harus mendeklarasikan kompatibilitas HPOS di file utama.
- Perubahan stok memakai fungsi WooCommerce (`wc_update_product_stock`) yang melakukan pembaruan atomik, bukan membaca lalu menulis ulang nilai.

## 4.2 Tabel custom

Sesuai CLAUDE.md bagian 4, tabel custom hanya dibuat karena WooCommerce tidak menyediakan penyimpanan yang cocok. Detail kolom ada di `docs/DATABASE.md`.

| Tabel | Alasan |
|-------|--------|
| `ia_stock_log` | WooCommerce tidak menyimpan riwayat perubahan stok per SKU (hanya catatan di order). Dibutuhkan untuk audit, filter per SKU, dan ekspor. |
| `ia_channel_sku_map` | Pemetaan SKU pusat ke item/model Shopee beserta status sinkronisasi. Dibutuhkan query seperti "semua SKU yang gagal sinkron", yang tidak efisien jika disimpan sebagai meta produk. |

## 4.3 Kode alasan perubahan stok

| Kode | Arti | Sumber |
|------|------|--------|
| `stok_awal` | Stok awal atau impor CSV | admin |
| `order_website` | Order website dibayar | website |
| `order_cancel` | Order website dibatalkan sebelum dikirim | website |
| `return_restock` | Retur diterima dan layak jual | admin |
| `order_shopee` | Order Shopee siap diproses | shopee |
| `shopee_cancel` | Order Shopee dibatalkan sebelum dikirim | shopee |
| `opname` | Hasil stok opname | admin |
| `rusak_hilang` | Barang rusak atau hilang | admin |
| `koreksi` | Koreksi salah input | admin |
| `sync_correction` | Koreksi hasil rekonsiliasi dengan Shopee | system |
| `edit_langsung` | Stok diubah langsung lewat layar WooCommerce (tanpa alasan) | admin |

Kolom sumber: `website`, `shopee`, `admin`, `system`. Nilai `pos` disiapkan untuk masa depan tanpa mengubah struktur.

---

# 5. ALUR

## 5.1 Order website

1. Checkout membuat order berstatus pending payment. WooCommerce menahan stok selama 30 menit. Angka **Ditahan** naik, **Tersedia** turun.
2. Pembayaran sukses (callback Midtrans). Order menjadi processing dan WooCommerce mengurangi stok. Hook mencatat log `order_website`.
3. Perubahan stok memicu job push ke Shopee (5.5).
4. Jika tidak dibayar dalam 30 menit, WooCommerce membatalkan order dan melepas tahanan. Job push dijalankan lagi karena Tersedia berubah.
5. Order dibatalkan sebelum dikirim: stok dikembalikan, log `order_cancel`.
6. Checkout menolak order jika Tersedia kurang dari jumlah yang diminta.

## 5.2 Penyesuaian manual

1. Admin membuka Posisi Stok dan memilih Sesuaikan pada SKU.
2. Mode: tambah, kurangi, atau set jumlah Fisik (stok opname).
3. Alasan **wajib** (pilih dari `opname`, `rusak_hilang`, `koreksi`, `stok_awal`) dan catatan opsional.
4. Service memeriksa hak akses dan nonce, memastikan hasil tidak negatif, memperbarui stok, mencatat log (dengan user), lalu memicu push.

## 5.3 Retur

1. Admin mencatat retur: order, item, jumlah, kondisi barang.
2. Layak jual: admin memilih **Kembalikan ke stok**, service menjalankan `adjust(+jumlah)` dengan alasan `return_restock` dan referensi order.
3. Cacat atau tidak layak: stok tidak berubah, hanya dicatat pada retur.
4. Koreksi lifetime purchase dan komisi affiliate ditangani modul lain, bukan modul stok.

## 5.4 Order Shopee masuk

1. Order diterima lewat push Shopee dan polling berkala sebagai cadangan **[USULAN: polling tiap 5 menit]**.
2. Idempoten berdasarkan nomor order Shopee (`order_sn`). Order yang sama tidak pernah mengurangi stok dua kali.
3. SKU Shopee dipetakan ke SKU pusat lewat `ia_channel_sku_map`. Jika tidak ditemukan: order ditandai **SKU tidak dikenal**, stok tidak berubah, dan muncul peringatan agar admin memetakan SKU.
4. Saat order berstatus siap diproses (sudah dibayar) **[VERIFIKASI]**: `adjust(-jumlah)` dengan alasan `order_shopee`.
5. Jika stok tidak cukup: stok menjadi 0, selisih dicatat sebagai **oversold** (flag dan peringatan). Order tetap diterima karena penjualan sudah terjadi di Shopee dan tidak bisa ditolak dari sisi kita. **[USULAN]**
6. Dibatalkan sebelum dikirim: `adjust(+jumlah)` dengan alasan `shopee_cancel`.
7. Setiap perubahan memicu push nilai Tersedia terbaru (5.5).
8. Retur Shopee mengikuti proses Shopee. Stok dikembalikan manual oleh admin jika barang layak jual **[USULAN]**.

## 5.5 Push stok ke Shopee

1. **Pemicu:** hook perubahan stok membuat job antrean per SKU. Jika sudah ada job pending untuk SKU yang sama, tidak dibuat lagi.
2. **Eksekusi:** job membaca Tersedia saat itu (bukan nilai saat job dibuat), memanggil API update stok Shopee berdasarkan item/model di `ia_channel_sku_map`.
3. **Berhasil:** `sync_status = ok`, `last_pushed_qty` dan `last_pushed_at` diperbarui.
4. **Gagal:** `retry_count` bertambah, job dijadwalkan ulang. Jadwal **[USULAN]**: 1, 5, 15, 60, dan 180 menit.
5. **Gagal ke-5:** `sync_status = failed`, `last_error` disimpan, peringatan muncul di dashboard admin.
6. **Perilaku saat gagal (keputusan owner):** stok pusat tidak diubah dan stok Shopee tidak di-set 0. Admin menyinkronkan manual lewat tombol **Sinkron Sekarang** (per SKU atau semua yang gagal).
7. **Rate limit:** respons yang menandakan batas laju membuat job ditunda dengan backoff, tidak dihitung sebagai kegagalan permanen.
8. **Antrean:** memakai Action Scheduler yang sudah menjadi bagian WooCommerce, sehingga tidak perlu plugin tambahan. **[USULAN]**

## 5.6 Rekonsiliasi berkala

1. Tiap 30 menit **[USULAN]**, ambil stok Shopee untuk semua SKU terpetakan (per batch).
2. Bandingkan dengan Tersedia. Jika berbeda: push nilai pusat, catat `sync_correction`, dan tandai sebagai drift di laporan.
3. Menangkap: stok yang diubah manual di Seller Centre, push Shopee yang hilang, job yang tidak terkirim.

---

# 6. PENCEGAHAN OVERSELLING

Berlapis, dari yang paling murah:

1. **Reservasi checkout.** Hold stock 30 menit dan penolakan saat Tersedia tidak cukup.
2. **Pembaruan atomik.** Dua order bersamaan untuk stok terakhir: hanya satu yang berhasil.
3. **Push cepat.** Perubahan stok sampai ke Shopee dalam hitungan detik sampai menit.
4. **Rekonsiliasi.** Selisih yang lolos dikoreksi otomatis.
5. **Penanganan oversold.** Jika tetap terjadi, tercatat dan terlihat admin.

Risiko yang tersisa (diterima karena keputusan tanpa buffer): jendela sinkronisasi beberapa detik sampai menit. Jika order website dan Shopee masuk bersamaan pada stok terakhir, keduanya bisa terjual. Sistem mencatatnya sebagai oversold. Jumlah kejadian oversold per bulan dipakai sebagai dasar meninjau kembali safety buffer.

---

# 7. KEGAGALAN DAN PENANGANANNYA

| Kejadian | Penanganan |
|----------|------------|
| API Shopee error atau timeout | Retry bertahap, lalu `failed` dan peringatan. Stok tidak di-set 0. |
| Rate limit | Tunda dengan backoff. |
| Token Shopee kedaluwarsa | Refresh otomatis. Jika refresh gagal, peringatan "Koneksi Shopee bermasalah" dan semua push ditunda. |
| Push (webhook) Shopee hilang | Polling berkala mengambil order yang terlewat berdasarkan waktu update terakhir. |
| Order Shopee yang sama diterima dua kali | Diabaikan (idempoten pada `order_sn`). |
| SKU Shopee tidak terpetakan | Order ditandai SKU tidak dikenal, stok tidak berubah, peringatan. |
| Order Shopee saat stok tidak cukup | Stok menjadi 0, oversold dicatat. |
| Stok diubah langsung di Seller Centre | Rekonsiliasi menimpakan stok pusat dan mencatat drift. |
| Server mati sementara | Antrean tersimpan di database. Setelah pulih, polling dan rekonsiliasi mengejar ketertinggalan. |
| Stok diedit langsung di layar WooCommerce | Tercatat sebagai `edit_langsung` dan tetap memicu push. |

---

# 8. HALAMAN ADMIN (FASE 1)

Mengacu pada Persediaan > Posisi Stok di Jubelio.

1. **Posisi Stok**
   - Kolom: produk, SKU, harga pokok, Stok WooCommerce, Ditahan, Tersedia, Dalam proses, Fisik, status sinkron Shopee.
   - Filter: pencarian produk/SKU, status stok, status sinkron.
   - Aksi: Sesuaikan, lihat log SKU, Export CSV.
2. **Log Stok**
   - Filter: SKU, alasan, sumber, rentang tanggal, user.
   - Hanya baca. Export CSV.
3. **Sinkron Shopee** (aktif di Fase 2)
   - Daftar SKU berstatus failed, unmapped, atau drift. Tombol Sinkron Sekarang.
   - Daftar oversold yang belum ditangani.
4. **Impor Stok Awal**
   - CSV dengan kolom `sku`, `qty`. Alasan otomatis `stok_awal`. Pratinjau sebelum diterapkan.
5. **Peringatan di dashboard:** jumlah SKU failed, oversold, SKU tidak dikenal, dan status koneksi Shopee.

---

# 9. HAK AKSES DAN KEAMANAN

- Capability custom: `ia_view_stock`, `ia_manage_stock`, `ia_manage_channels` (pemetaan dan sinkron Shopee). Diberikan ke Administrator dan Shop Manager secara default.
- Setiap aksi tulis: cek capability, verifikasi nonce, sanitasi input, escape output (CLAUDE.md bagian 5).
- Endpoint penerima push Shopee (REST API): wajib memverifikasi tanda tangan permintaan sebelum diproses **[VERIFIKASI]**.
- Kunci partner Shopee disimpan di konfigurasi server (`wp-config.php` atau environment), bukan di kode atau repo. Access token dan refresh token disimpan terenkripsi di database dengan kunci dari konfigurasi server, karena berubah setiap refresh **[USULAN]**.
- Error API dicatat lewat logger WooCommerce tanpa menyertakan token.
- Waktu disimpan UTC dan ditampilkan WIB (Asia/Jakarta).

---

# 10. STRUKTUR KODE

```text
wp-content/plugins/inside-apparel-core/includes/modules/
├── inventory/
│   ├── class-installer.php            (membuat/memperbarui tabel, versi DB)
│   ├── class-inventory-service.php    (adjust, posisi stok)
│   ├── class-stock-log.php            (tulis dan baca log)
│   ├── class-woocommerce-hooks.php    (menangkap perubahan stok oleh WooCommerce)
│   └── admin/
│       ├── class-stock-position-page.php
│       ├── class-stock-log-page.php
│       └── class-stock-import-page.php
└── shopee/                            (Fase 2)
    ├── class-shopee-client.php        (pemanggil API dan penandatangan)
    ├── class-token-store.php
    ├── class-sku-map.php
    ├── class-stock-push.php           (job antrean)
    ├── class-order-intake.php
    └── class-reconciler.php
```

---

# 11. PENGATURAN WOOCOMMERCE YANG DIWAJIBKAN

- Kelola stok: aktif, di level variasi untuk produk variabel.
- Backorders: tidak diizinkan.
- Hold stock (menit): 30, sama dengan batas pembayaran.
- Notifikasi stok rendah dan habis: aktif ke email admin. Ambang stok rendah awal 2 **[USULAN]**.
- Plugin mendeklarasikan kompatibilitas HPOS.

---

# 12. URUTAN IMPLEMENTASI DAN KRITERIA SELESAI

| Tahap | Isi | Syarat Shopee? |
|-------|-----|----------------|
| 1 | Installer dan tabel, service `adjust()`, log, halaman Posisi Stok, Sesuaikan, Impor Stok Awal | Tidak |
| 2 | Hook WooCommerce (order, batal, edit langsung), angka Ditahan, Dalam proses, Fisik, pengaturan di bagian 11 | Tidak |
| 3 | Pemetaan SKU, token, push stok dengan antrean dan retry | Ya |
| 4 | Penerimaan order Shopee, oversold, SKU tidak dikenal | Ya |
| 5 | Rekonsiliasi, halaman Sinkron Shopee, peringatan dashboard | Ya |

Tahap 1 dan 2 dapat dikerjakan sekarang. Tahap 3 sampai 5 menunggu akses Shopee Open Platform.

Kriteria selesai (dipakai sebagai uji penerimaan):
1. Penyesuaian manual memerlukan alasan, mengubah stok, dan tercatat di log lengkap dengan user.
2. Order website dibayar mengurangi stok sekali dan tercatat. Pembatalan mengembalikannya.
3. Dua order bersamaan untuk stok terakhir: hanya satu yang berhasil.
4. Order tidak dibayar 30 menit dibatalkan otomatis dan tahanan dilepas.
5. Perubahan stok membuat job push. Gagal lima kali menghasilkan status failed dan peringatan. Sinkron manual berhasil memulihkan.
6. Order Shopee yang sama diterima dua kali tidak mengurangi stok dua kali.
7. Order Shopee saat stok kurang: stok tetap 0 dan oversold tercatat.
8. Stok diubah langsung di Seller Centre: rekonsiliasi mengembalikan ke nilai pusat dan mencatatnya.
9. Pengguna tanpa capability tidak dapat menyesuaikan stok.
10. Tidak ada panggilan API Shopee saat proses checkout.

---

# 13. HAL YANG DIVERIFIKASI SAAT INTEGRASI SHOPEE

1. Kapan Shopee mengurangi stoknya sendiri (saat order dibuat atau dibayar), dan apakah nilai stok yang kita kirim sudah memperhitungkan order Shopee yang belum dibayar. Ini menentukan titik pengurangan stok pusat di 5.4.
2. Mekanisme push yang tersedia, cara memverifikasi tanda tangan, dan perilaku pengiriman ulang.
3. Batas laju panggilan API.
4. Pembaruan stok dilakukan per item atau per model (variasi).
5. Umur access token dan refresh token.
6. Lama proses persetujuan aplikasi dan akses production di Shopee Open Platform. Proses ini bisa menjadi jalur kritis, jadi pendaftaran sebaiknya dimulai lebih awal.
7. Jika order Shopee disimpan sebagai order WooCommerce: pastikan email WooCommerce ke customer dinonaktifkan dan WooCommerce tidak mengurangi stok otomatis untuk order tersebut, agar tidak terjadi pengurangan ganda dengan `order_shopee`. Keputusan penyimpanan ada di dokumen order.

---

# 14. DITUNDA DAN DI LUAR CAKUPAN

- Safety buffer (ditinjau jika oversold sering terjadi).
- Set stok Shopee ke 0 otomatis saat sinkron gagal (ditinjau saat volume order naik).
- Multi-lokasi gudang. Desain tabel dan service tidak menutup kemungkinan ini.
- Marketplace selain Shopee. Kolom `channel` pada tabel pemetaan sudah menyiapkannya.
- Pre-order: dirancang saat fitur dibuat. Usulan awal: dikecualikan dari aturan stok tidak boleh negatif dan dari push ke Shopee, dengan kuota pre-order dikelola terpisah.
- POS offline: dirancang bersama nanti, sumber `pos` sudah disiapkan pada log.

---

# 15. CATATAN IMPLEMENTASI TAHAP 1 DAN 2

Ditambahkan 2026-10-10. Bagian ini mencatat keputusan teknis yang diambil saat menulis kode.

1. **Dua jalur penulisan log.** `adjust()` dan `set_quantity()` mengubah stok lalu mencatat. `record()` hanya mencatat perubahan yang sudah dilakukan WooCommerce (order, edit langsung). Keduanya memicu hook `ia_stock_adjusted` untuk antrean Shopee (Tahap 3).
2. **Log order berbasis status, bukan berbasis kejadian.** Hook `woocommerce_reduce_order_stock` dan `woocommerce_restore_order_stock` membandingkan `_reduced_stock` (milik WooCommerce) dengan `_ia_stock_logged` (milik kita) pada tiap item order. Selisihnya yang dicatat, sehingga aman dipanggil berulang dan gagal-tulis dicoba lagi pada pemicu berikutnya.
3. **Edit langsung.** Sebelum produk disimpan, stok lama dibaca dari database; setelah disimpan, selisihnya dicatat sebagai `edit_langsung` (atau `stok_awal` bila sebelumnya stok tidak dikelola). Produk baru dengan stok dicatat `stok_awal` lewat hook produk baru. Perubahan oleh `adjust()` diabaikan lewat penanda `is_busy()`.
4. **Angka Ditahan** diambil dari `ReserveStock` bawaan WooCommerce. Jika tidak tersedia, kolom menampilkan tanda strip dan Tersedia sama dengan Stok WC.
5. **Dalam proses** dihitung dari item order berstatus processing yang stoknya sudah dikurangi. Status tambahan (mis. packed) ditambahkan lewat filter `ia_in_process_order_statuses`. Dibatasi 2.000 order.
6. **Stok opname** memasukkan jumlah Fisik. Stok WC = Fisik - Dalam proses. Ditolak bila hasilnya negatif.
7. **Yang belum tercatat di log:** refund WooCommerce dengan restock otomatis, dan penyesuaian item di layar edit order. Lihat D-021.
8. **Pengaturan Stok** hanya menampilkan dan menerapkan pengaturan global WooCommerce (hold stock, notifikasi stok) dan mematikan backorder per produk, atas tombol eksplisit admin.
