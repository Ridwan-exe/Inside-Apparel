# INSIDE APPAREL
# MASTER REQUIREMENT

Version: 0.2
Status: Draft
Terakhir diperbarui: 2026-10-08

Keterangan tanda:
- **[USULAN]** = usulan yang menunggu persetujuan owner.
- **[TBD]** = belum diputuskan.
- Tanpa tanda = sudah diputuskan.

---

# 1. PROJECT OVERVIEW

Inside Apparel adalah platform e-commerce yang digunakan sebagai pusat penjualan online dan pengelolaan customer, produk, order, inventory, serta integrasi marketplace.

Platform utama menggunakan WordPress dan WooCommerce dengan custom development untuk kebutuhan bisnis Inside Apparel.

Sales channel yang direncanakan:

1. Website Inside Apparel
2. Shopee
3. Marketplace lain di masa depan

Sistem harus dirancang agar dapat berkembang tanpa perlu membangun ulang seluruh sistem ketika jumlah produk, customer, order, atau sales channel bertambah.

## 1.1 Backend Admin Custom

Backend untuk proses pesanan, upload produk, dan pengelolaan stok dibuat custom dengan acuan tampilan dan alur kerja Jubelio POS.

Prinsip:
- Data tetap disimpan di struktur WordPress/WooCommerce (order, produk, stok). Tidak membuat data ganda.
- Halaman admin custom dibuat di plugin `inside-apparel-core`, bukan mengubah core WooCommerce.
- Dibangun bertahap **[USULAN]**:
  - Fase 1: daftar order terpadu, proses order (status, packing, resi/label), upload dan edit produk beserta varian, edit stok, log perubahan stok.
  - Fase 2: integrasi Shopee dan sinkronisasi stok.
  - Fase 3: laporan dan fitur lanjutan.
- **[TBD]** Daftar halaman/fitur Jubelio yang menjadi acuan (screenshot atau daftar menu) dan apakah kasir/POS offline termasuk cakupan.

---

# 2. PROJECT OBJECTIVES

Tujuan utama:

- Membuat website e-commerce profesional.
- Memberikan pengalaman belanja yang mudah bagi customer.
- Mengurangi pekerjaan manual admin.
- Memusatkan pengelolaan order.
- Memusatkan pengelolaan inventory.
- Mengurangi risiko overselling.
- Mengintegrasikan marketplace.
- Membuat sistem customer/member.
- Membuat membership berdasarkan lifetime purchase.
- Membuat voucher dan promotion system.
- Membuat affiliate / Brand Ambassador system.
- Membuat sistem notifikasi customer.
- Menyediakan reporting untuk kebutuhan bisnis.

---

# 3. CUSTOMER EXPERIENCE

Customer harus dapat:

- membuka website
- melihat produk
- mencari produk
- memfilter produk
- melihat kategori
- melihat detail produk
- memilih varian
- memilih ukuran
- memilih warna jika tersedia
- melihat harga
- melihat stok
- menambahkan produk ke cart
- mengubah quantity
- checkout
- memilih alamat
- memilih metode pengiriman
- memilih metode pembayaran
- menerima informasi order
- melihat status order
- melihat riwayat order
- mendapatkan notifikasi
- menggunakan voucher
- mendapatkan benefit membership

Customer experience harus dibuat sederhana dan cepat.

---

# 4. CUSTOMER ACCOUNT

Customer account minimal memiliki:

- customer ID
- nama
- email
- nomor handphone
- password
- alamat
- order history
- lifetime purchase
- membership level
- voucher
- affiliate information jika tersedia

Customer dapat:

- mengubah profile
- mengubah password
- mengelola alamat
- melihat order
- melihat detail order
- melihat voucher
- melihat membership
- melihat reward yang tersedia

---

# 5. MEMBERSHIP

Membership berdasarkan lifetime purchase customer.

| Level | Lifetime Purchase | Voucher |
|-------|-------------------|---------|
| Member | Rp0 | - |
| Bronze | >= Rp1.000.000 | Diskon 3% |
| Silver | >= Rp2.500.000 | Diskon 5% |
| Gold | >= Rp5.000.000 | Diskon 7% **[TBD: konfirmasi, sebelumnya tertulis "silver" dua kali]** |

Aturan:

- Lifetime purchase hanya dari order **website** berstatus completed. Order Shopee tidak dihitung.
- Nilai yang dihitung: subtotal produk setelah diskon, tidak termasuk ongkir **[USULAN]**.
- Order yang dibatalkan atau di-refund mengurangi lifetime purchase.
- Level bersifat permanen (tidak turun), meskipun lifetime purchase berkurang karena refund **[USULAN]**.
- Voucher diberikan saat customer naik level dan hanya dapat dipakai 1 kali.
- Setelah voucher dipakai, customer mendapat voucher berikutnya sesuai level saat itu.
- Saat naik level, voucher yang belum terpakai diganti dengan voucher level baru.
- Perubahan level otomatis dan tercatat (tanggal, nilai sebelum/sesudah).
- **[TBD]** Voucher berikutnya diberikan langsung setelah yang lama dipakai, atau ada jeda (contoh: 30 hari)? Tanpa jeda, customer bisa mendapat diskon di setiap order.
- **[TBD]** Batas maksimum nominal potongan per voucher membership (contoh: maks Rp100.000).

---

# 6. VOUCHER DAN PROMOTION

Jenis voucher:

- potongan nominal
- potongan persentase (dengan batas maksimum)
- gratis ongkir
- voucher khusus level membership
- voucher khusus customer tertentu

Aturan voucher:

- kode unik
- periode berlaku
- minimum belanja
- kuota total dan kuota per customer
- berlaku untuk semua produk dan kategori
- bisa dinonaktifkan admin kapan saja
- setiap pemakaian tercatat (customer, order, nilai)

Aturan penggabungan:

- Voucher gratis ongkir dapat digabung dengan voucher potongan harga (contoh: gratis ongkir + voucher member).
- Voucher potongan harga tidak dapat digabung dengan voucher potongan harga lain. Maksimal satu voucher potongan harga per order.
- **[TBD]** Apakah kode affiliate memberi diskon ke pembeli? Kalau ya, apakah dihitung sebagai voucher potongan harga (tidak bisa digabung dengan voucher member)?

Prioritas: gunakan sistem kupon bawaan WooCommerce sebagai fondasi, tambahkan logika custom hanya jika kebutuhan tidak tercakup.

---

# 7. AFFILIATE / BRAND AMBASSADOR

Aturan:

- Setiap affiliate memiliki kode referral dan link referral unik.
- Penjualan melalui kode atau link tercatat atas nama affiliate.
- Komisi dihitung dari order **completed**, bukan saat order dibuat.
- Komisi 10-15% dari harga produk yang terjual. Dasar perhitungan: subtotal produk setelah diskon, tidak termasuk ongkir **[USULAN]**.
- Besaran komisi **[USULAN]**: diatur per affiliate oleh admin dalam rentang 10-15%, default 10%.
- Komisi order yang dibatalkan atau di-refund dibatalkan.
- Affiliate tidak boleh memakai kodenya untuk order sendiri.

Status komisi:

1. **Pending**: order belum completed.
2. **Approved**: order completed dan melewati masa tunggu refund **[USULAN: 7 hari setelah completed]**, masuk ke saldo.
3. **Paid**: sudah ditarik dan dibayar admin.

Halaman Affiliate (khusus affiliate):

- jumlah order dan total penjualan
- saldo tersedia, komisi pending, total sudah dibayar
- riwayat komisi per order
- tombol **Tarik Saldo**
- data rekening bank affiliate

Penarikan saldo:

- Minimum penarikan Rp100.000.
- Affiliate mengajukan penarikan, admin mentransfer manual lalu menandai **Paid** di backend.
- Pengajuan penarikan memiliki status: diajukan, diproses, dibayar, ditolak.
- Jika refund terjadi setelah komisi approved, komisi dikoreksi (dikurangi dari saldo atau penarikan berikutnya) **[USULAN]**.
- **[TBD]** Cek kewajiban pajak atas pembayaran komisi bersama akuntan.

---

# 8. PRODUCT CATALOG

- kategori dan sub-kategori
- produk variabel: ukuran, warna
- SKU unik per varian (kunci pemetaan ke Shopee)
- harga normal dan harga promo
- gambar produk
- berat dan dimensi (untuk ongkir)
- deskripsi dan size guide
- pencarian dan filter (kategori, ukuran, warna, harga)
- status: tersedia, habis, pre-order

---

# 9. CENTRAL INVENTORY

Prinsip:

- Stok dikelola dari satu sumber kebenaran (source of truth): stok pusat di WooCommerce.
- Website dan Shopee membaca dan memperbarui stok melalui sumber yang sama.
- Setiap perubahan stok tercatat: produk/SKU, jumlah, alasan, sumber (website/Shopee/admin), waktu.

Aturan:

- Unit stok adalah SKU varian, bukan produk induk.
- Stok tidak boleh negatif.
- Order website mengurangi stok langsung; pembatalan mengembalikan stok.
- Order Shopee mengurangi stok pusat, lalu stok baru disinkronkan ke channel lain.
- Tanpa safety buffer. Stok yang tampil di setiap channel sama dengan stok pusat.
- Jika sinkronisasi ke Shopee gagal: sistem mencoba ulang, menandai SKU bermasalah, menampilkan peringatan di dashboard admin, dan stok dikelola manual oleh admin. Stok tidak otomatis di-set 0. Keputusan ini ditinjau ulang saat volume order meningkat.
- Penyesuaian stok manual oleh admin wajib menyertakan alasan.

Detail desain ditulis di dokumen terpisah: `docs/INVENTORY_DESIGN.md`.

---

# 10. ORDER MANAGEMENT

Status order **[USULAN]**: pending payment, processing, packed, shipped, completed, cancelled, refunded.

- Order dari website dan Shopee terlihat dalam satu daftar dengan penanda sumber.
- Admin dapat mengubah status, menambah catatan, dan mencetak invoice/label.
- Setiap perubahan status tercatat (siapa, kapan).
- Order pending payment dibatalkan otomatis 30 menit setelah invoice dibuat (lihat bagian 11), stok dikembalikan.
- **[TBD]** Order otomatis completed berapa hari setelah barang diterima? Ini menentukan kapan lifetime purchase dan komisi affiliate dihitung.

Pembatalan, retur, dan refund:

- Permintaan customer masih melalui WhatsApp.
- Admin mencatat refund/retur di backend agar stok, lifetime purchase, dan komisi affiliate ikut terkoreksi.
- **[TBD]** Batas waktu pengajuan retur, syarat barang, dan siapa menanggung ongkir retur.

---

# 11. PAYMENT

- Mata uang: IDR.
- Metode: QRIS, Virtual Account, e-wallet.
- Batas waktu pembayaran: 30 menit setelah invoice dibuat, lalu order dibatalkan otomatis. Masa berlaku VA/QRIS di payment gateway harus diselaraskan dengan 30 menit ini.
- Data kartu tidak pernah disimpan di server Inside Apparel.
- Payment gateway **[USULAN]**: Midtrans atau Xendit. Keduanya mendukung QRIS, VA berbagai bank, dan e-wallet, serta memiliki integrasi WooCommerce. Pemilihan akhir berdasarkan biaya per transaksi dan kemudahan pendaftaran bisnis. Pemasangan plugin gateway memerlukan persetujuan owner sesuai CLAUDE.md.

---

# 12. SHIPPING

- Perhitungan ongkir berdasarkan alamat dan berat.
- Kurir: J&T dan JNE.
- Resi dan label berbarcode dibuat otomatis dari backend.
- Customer dapat melacak pengiriman dari halaman order.
- **[TBD]** Cara integrasi: API langsung ke J&T dan JNE (umumnya perlu akun korporat/kemitraan) atau melalui agregator pengiriman (contoh: Biteship) yang menyediakan tarif, pembuatan resi, label, dan tracking dalam satu API. Perlu dicek ketersediaan dan syaratnya sebelum desain.
- Pengiriman order Shopee mengikuti logistik Shopee dan di luar cakupan bagian ini.

---

# 13. NOTIFICATION

Notifikasi dikirim saat: order dibuat, pembayaran diterima, order dikirim (dengan resi), order selesai, naik level membership, voucher diterima.

- Email: otomatis, template dapat diubah admin.
- WhatsApp fase 1: manual oleh admin **[USULAN]**, backend menyediakan tombol chat WhatsApp dengan pesan template yang terisi otomatis.
- WhatsApp otomatis (penyedia resmi) menjadi fase berikutnya dan membutuhkan biaya serta persetujuan template pesan.

---

# 14. SHOPEE INTEGRATION

- Memakai Shopee Open Platform API.
- Sinkronisasi: produk/SKU, stok, order, status order.
- Arah data stok: pusat → Shopee.
- Order Shopee masuk ke sistem pusat dan mengurangi stok pusat.
- Pemetaan SKU website ↔ item/model Shopee wajib ada.
- Token API disimpan aman (bukan di kode, bukan di repo).
- Penanganan rate limit dan kegagalan API (retry + log).

---

# 15. NON-FUNCTIONAL REQUIREMENTS

- **Keamanan**: ikuti aturan di `CLAUDE.md`.
- **Performa**: halaman produk dan checkout termuat cepat di koneksi mobile.
- **Mobile-first**: mayoritas pembeli diperkirakan memakai handphone.
- **Backup**: backup database dan file terjadwal.
- **Logging**: error integrasi dan perubahan stok tercatat.
- **Skalabilitas**: penambahan produk, order, dan channel tidak memerlukan pembangunan ulang.
- **[TBD]** Target kapasitas desain. "Sebanyak mungkin" perlu angka agar hosting dan struktur data bisa dirancang. Contoh: awal 500-1.000 SKU dan 50-100 order/hari, dengan desain yang tahan 10x lipat.

---

# 16. KEPUTUSAN DAN PERTANYAAN TERBUKA

## Keputusan

| Topik | Keputusan |
|-------|-----------|
| Level membership | Bronze Rp1.000.000, Silver Rp2.500.000, Gold Rp5.000.000 |
| Benefit membership | Voucher diskon sesuai level (3% / 5% / 7%), 1x pakai |
| Order Shopee ke lifetime purchase | Tidak dihitung |
| Safety buffer stok | Tidak ada |
| Sinkronisasi Shopee gagal | Kelola stok manual, tidak di-set 0 |
| Komisi affiliate | 10-15%, dihitung dari order completed |
| Pembayaran komisi | Halaman saldo + tarik saldo, dibayar manual admin, minimum Rp100.000 |
| Metode pembayaran | QRIS, Virtual Account, e-wallet |
| Batas bayar | 30 menit |
| Kurir | J&T dan JNE, resi/label otomatis dari backend |
| Retur dan refund | Melalui WhatsApp, dicatat admin di backend |
| Notifikasi | Email otomatis, WhatsApp manual oleh admin (fase 1) |
| Backend admin | Custom, acuan Jubelio POS |

## Masih terbuka

1. Besar diskon Gold (7%?) dan batas maksimum nominal voucher membership.
2. Jeda antar voucher membership.
3. Apakah kode affiliate memberi diskon ke pembeli dan bagaimana aturan penggabungannya.
4. Komisi: tetap atau per affiliate, serta masa tunggu sebelum komisi approved.
5. Kapan order otomatis completed.
6. Kebijakan retur (batas waktu, syarat, ongkir).
7. Payment gateway final.
8. Cara integrasi kurir (API langsung atau agregator).
9. Daftar fitur Jubelio yang jadi acuan dan apakah POS offline termasuk.
10. Target jumlah SKU dan order per hari.