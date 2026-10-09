# INSIDE APPAREL
# MASTER REQUIREMENT

Version: 0.8
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
- Kasir/POS offline tidak termasuk cakupan awal. Jika dibutuhkan nanti, dirancang bersama setelah Fase 2. Desain stok dan order harus menyediakan sumber/channel yang bisa ditambah (contoh: `pos`) agar POS offline dapat ditambahkan tanpa membangun ulang.
- Jubelio dipakai sebagai acuan alur kerja dan fungsi (lihat 1.2), bukan untuk meniru tampilan atau identitas visualnya.

## 1.2 Acuan Jubelio (dari screenshot)

Menu utama Jubelio: Katalog, Persediaan, Penjualan, Pembelian, Gudang, Keuangan. Untuk Fase 1 yang dipakai sebagai acuan: Katalog (produk), Penjualan (pesanan, retur), dan Persediaan (posisi stok). Pembelian, Gudang, dan Keuangan di luar cakupan awal **[USULAN]**.

| Halaman acuan | Isi yang terlihat | Dipakai di Inside Apparel |
|---------------|-------------------|---------------------------|
| Katalog > Produk > Buat Produk (Produk Satuan) | Form dengan tab Detail Produk, Informasi Penjualan dan Pembelian, Informasi Pengiriman, Gambar dan Video Produk. Field: nama, merek, kategori bertingkat, toggle variasi ukuran/warna, SKU, barcode, deskripsi (30-10.000 karakter), tipe produk (bundle, konsinyasi, pre-order), atribut (bahan, motif, gender, tahun). | Form produk dengan tab serupa. Dipakai: nama, merek, kategori bertingkat, variasi ukuran/warna, SKU, barcode, deskripsi, pre-order, atribut produk. Bundle dan konsinyasi lihat bagian 8. |
| Penjualan > Transaksi Penjualan > Pesanan | Tab Pantauan, Pesanan, Faktur, Retur, Faktur Pajak. Filter cepat: Semua, Belum Dibayar, Gagal Download, Siap Proses. Kolom: no. pesanan, tanggal, no. resi, penerima, lokasi, nilai, toko, kurir, status channel, status internal, no. faktur. Filter: pencarian pesanan/produk, lokasi, status, kurir, channel, toko, tipe pesanan, isi pesanan, rentang tanggal. Tombol Export, Import, Tambah Baru. | Daftar order terpadu dengan kolom dan filter serupa. Status channel (status di channel asal, mis. Shopee) dipisah dari status internal. Filter cepat termasuk "Gagal Sinkron" untuk order Shopee yang gagal masuk. Tab Retur untuk pencatatan retur. Tambah Baru untuk order manual (mis. dari WhatsApp). Export/Import CSV. Faktur pajak di luar cakupan awal. |
| Persediaan > Posisi Stok > Stok Total | Tab Pantauan, Stok Total, Stok Lokasi. Filter: cari produk, tipe. Kolom: produk dan SKU, harga pokok, stok gudang (beberapa angka). | Halaman posisi stok per SKU dengan harga pokok. Satu lokasi "Pusat" dulu, desain tetap memungkinkan lokasi tambahan **[USULAN]**. Arti angka stok (fisik, dipesan, tersedia) ditentukan di `docs/INVENTORY_DESIGN.md`. |

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
| Gold | >= Rp5.000.000 | Diskon 7% |

Aturan:

- Lifetime purchase hanya dari order **website** berstatus completed. Order Shopee tidak dihitung.
- Nilai yang dihitung: subtotal produk setelah diskon, tidak termasuk ongkir **[USULAN]**.
- Order yang dibatalkan atau di-refund mengurangi lifetime purchase.
- Level bersifat permanen (tidak turun), meskipun lifetime purchase berkurang karena refund **[USULAN]**.
- Voucher diberikan saat customer naik level dan hanya dapat dipakai 1 kali.
- Setelah voucher dipakai, customer mendapat voucher berikutnya sesuai level saat itu.
- Saat naik level, voucher yang belum terpakai diganti dengan voucher level baru.
- Perubahan level otomatis dan tercatat (tanggal, nilai sebelum/sesudah).
- Voucher berikutnya diberikan 1 bulan setelah tanggal pemakaian voucher sebelumnya.
- Jika customer naik level, voucher level baru langsung diberikan tanpa menunggu jeda 1 bulan.
- Diskon voucher membership dihitung dari subtotal produk. Contoh: customer Gold (lifetime purchase di atas Rp5.000.000) membeli senilai Rp1.000.000 dan memakai voucher 7%, maka membayar Rp1.000.000 - 7% = Rp930.000 (belum termasuk ongkir).
- Tanpa batas maksimum nominal potongan **[USULAN]**. Perlu diperhatikan: pada order besar potongan ikut besar (order Rp10.000.000 dengan voucher 7% = potongan Rp700.000). Batas maksimum dapat ditambahkan kapan saja.

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
- Kode affiliate dapat digabung dengan voucher member. Kode affiliate tidak dihitung sebagai voucher potongan harga yang saling meniadakan.
- Kode affiliate memberi diskon ke pembeli sebesar 1-3% tergantung produk yang dibeli. Persentase diatur per produk oleh admin (field diskon affiliate pada produk).
- Diskon affiliate dan voucher member dapat berlaku bersamaan pada order yang sama. Keduanya dihitung dari harga produk sebelum diskon (dijumlahkan, tidak berlapis) **[USULAN]**. Contoh: produk Rp1.000.000 dengan voucher Gold 7% + diskon affiliate 3% = potongan Rp100.000, customer membayar Rp900.000 (belum termasuk ongkir).
- Produk yang belum diatur persentase diskon affiliate-nya memakai default 1% (diskon ke pembeli dan komisi BA sama-sama 1%).

Prioritas: gunakan sistem kupon bawaan WooCommerce sebagai fondasi, tambahkan logika custom hanya jika kebutuhan tidak tercakup.

---

# 7. AFFILIATE / BRAND AMBASSADOR

Aturan:

- Setiap affiliate memiliki kode referral dan link referral unik.
- Penjualan melalui kode atau link tercatat atas nama affiliate.
- Komisi dihitung dari order **completed**, bukan saat order dibuat.
- Komisi affiliate (BA) sama dengan nilai diskon affiliate yang diterima pembeli (1-3% per produk, sesuai pengaturan produk). Dasar perhitungan: harga produk sebelum diskon, tidak termasuk ongkir. Contoh: produk Rp900.000 dengan diskon affiliate 3% = Rp27.000, maka pembeli membayar Rp900.000 - Rp27.000 dan komisi affiliate Rp27.000.
- Komisi tidak bergantung pada voucher member. Pembeli yang juga memakai voucher member tetap menghasilkan komisi sebesar diskon affiliate.
- Tidak ada persentase komisi per affiliate. Semua affiliate memakai aturan yang sama, besarnya mengikuti pengaturan diskon affiliate pada produk.
- **Catatan margin:** pada kombinasi tertinggi (voucher Gold 7% + diskon affiliate 3% + komisi 3%), untuk produk Rp1.000.000 customer membayar Rp900.000, komisi Rp30.000, sehingga total biaya promosi Rp130.000 (13% dari harga normal), belum termasuk biaya payment gateway.
- Komisi order yang dibatalkan atau di-refund dibatalkan.
- Affiliate tidak boleh memakai kodenya untuk order sendiri.

Status komisi:

1. **Pending**: order belum completed.
2. **Approved**: 1 hari setelah order berstatus completed, komisi masuk ke saldo yang dapat ditarik. Retur yang masuk sebelum komisi approved membatalkan komisi, dan retur setelahnya dikoreksi dari saldo (lihat aturan penarikan).
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
- barcode per SKU
- harga normal dan harga promo
- harga pokok per SKU (internal, tidak tampil ke customer), dasar analisis margin nanti **[USULAN]**
- diskon affiliate per produk (1-3%, default 1%)
- atribut produk tambahan (bahan, motif, gender, tahun) untuk kebutuhan listing Shopee **[USULAN]**
- gambar produk
- berat dan dimensi (untuk ongkir)
- deskripsi dan size guide
- pencarian dan filter (kategori, ukuran, warna, harga)
- status: tersedia, habis, pre-order
- Produk bundle dan produk konsinyasi (ada di Jubelio) tidak diperlukan, jadi tidak dibangun. Produk cukup berupa produk satuan dengan variasi.

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
- Order menjadi **completed** saat customer mengonfirmasi paket diterima, atau otomatis 3 hari setelah paket berstatus diterima, mana yang lebih dulu **[USULAN: tafsir dari "saat diterima / 3 hari setelah diterima"]**.
- Status "diterima" diambil dari tracking kurir, sehingga bergantung pada integrasi pengiriman (bagian 12). Admin dapat menandai diterima secara manual jika tracking gagal.
- Completed memicu perhitungan lifetime purchase, kenaikan level, dan komisi affiliate.

Pembatalan, retur, dan refund:

- Permintaan customer masih melalui WhatsApp.
- Admin mencatat refund/retur di backend agar stok, lifetime purchase, dan komisi affiliate ikut terkoreksi.
- Syarat retur: ada bukti unboxing dan paket sudah diterima customer.
- Ongkir retur ditanggung customer, kecuali kesalahan berasal dari toko.
- Batas waktu pengajuan retur: 3 hari sejak paket diterima. Jika order sudah completed saat retur masuk (mis. customer konfirmasi lebih awal), lifetime purchase dan komisi dikoreksi sesuai aturan refund.
- Jika kesalahan berasal dari toko (barang salah kirim atau cacat), ongkir retur ditanggung penjual.

---

# 11. PAYMENT

- Mata uang: IDR.
- Metode: QRIS, Virtual Account, e-wallet.
- Batas waktu pembayaran: 30 menit setelah invoice dibuat, lalu order dibatalkan otomatis. Masa berlaku VA/QRIS di payment gateway harus diselaraskan dengan 30 menit ini.
- Data kartu tidak pernah disimpan di server Inside Apparel.
- Payment gateway: Midtrans (mendukung QRIS, Virtual Account, dan e-wallet).
- Integrasi memakai plugin resmi Midtrans untuk WooCommerce (keputusan owner). Sesuai CLAUDE.md, sebelum dipasang dicatat: sumber resmi dan versi terbaru, dampak keamanan dan performa, serta rencana pemeliharaan.
- Server key dan client key Midtrans disimpan di konfigurasi server (`wp-config.php` atau environment), tidak di kode dan tidak di repo.

---

# 12. SHIPPING

- Perhitungan ongkir berdasarkan alamat dan berat.
- Kurir: J&T dan JNE.
- Resi dan label berbarcode dibuat otomatis dari backend.
- Customer dapat melacak pengiriman dari halaman order.
- Integrasi pengiriman memakai API Biteship (tarif, pembuatan order/resi, label, dan tracking untuk J&T dan JNE). Diintegrasikan langsung ke API Biteship dari plugin `inside-apparel-core`, tanpa plugin pihak ketiga **[USULAN: tafsir dari "API langsung menggunakan Biteship"]**.
- Pendaftaran akun Biteship dan verifikasinya (J&T dan JNE aktif, label berbarcode, mekanisme update status, alur pickup/drop-off) dilakukan nanti setelah operasional mulai berjalan.
- Sampai integrasi Biteship aktif, admin menginput nomor resi dan label secara manual **[USULAN]**. Modul pengiriman dibuat dengan lapisan terpisah (pengiriman manual dan Biteship) agar Biteship dapat dipasang tanpa mengubah alur order.
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
- Target kapasitas awal: sekitar 100 order per hari (semua channel). Desain harus tetap nyaman hingga sekitar 10x lipatnya tanpa dibangun ulang.
- Jumlah SKU awal: sekitar 100 SKU. Pemetaan SKU ke Shopee untuk jumlah ini dapat dilakukan lewat impor massal (CSV), tanpa membangun fitur pemetaan otomatis yang kompleks **[USULAN]**.

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
| Komisi affiliate | Sama dengan diskon affiliate yang diterima pembeli (1-3% per produk), dari order completed, approved 1 hari setelah completed. Produk tanpa pengaturan memakai default 1% |
| Diskon affiliate ke pembeli | 1-3% tergantung produk, dapat digabung dengan voucher member |
| Voucher Gold | 7%, tanpa batas maksimum nominal **[USULAN]** |
| Pembayaran komisi | Halaman saldo + tarik saldo, dibayar manual admin, minimum Rp100.000 |
| Metode pembayaran | QRIS, Virtual Account, e-wallet |
| Batas bayar | 30 menit |
| Payment gateway | Midtrans, memakai plugin resmi Midtrans untuk WooCommerce |
| Kurir | J&T dan JNE lewat API Biteship, resi/label otomatis dari backend. Akun Biteship didaftarkan setelah operasional berjalan, sementara itu resi diinput manual **[USULAN]** |
| Retur dan refund | Melalui WhatsApp, dicatat admin di backend. Syarat: bukti unboxing, paket sudah diterima, diajukan maksimal 3 hari sejak diterima, ongkir retur ditanggung customer. Jika salah kirim atau cacat, ongkir retur ditanggung penjual |
| Margin | Belum ada gambaran, ditunda. Sistem menyimpan harga pokok per SKU untuk analisis nanti **[USULAN]** |
| Order completed | Saat customer konfirmasi diterima, atau otomatis 3 hari setelah diterima |
| Kode affiliate + voucher member | Dapat digabung |
| Jeda voucher member | 1 bulan setelah pemakaian; naik level langsung diberikan |
| Target awal | Sekitar 100 order per hari, sekitar 100 SKU |
| Notifikasi | Email otomatis, WhatsApp manual oleh admin (fase 1) |
| Backend admin | Custom, acuan Jubelio (screenshot katalog produk, pesanan, posisi stok), lihat 1.2 |
| POS offline | Di luar cakupan awal, dirancang bersama nanti jika dibutuhkan |
| Produk bundle dan konsinyasi | Tidak diperlukan |

## Masih terbuka

Tidak ada pertanyaan bisnis yang menghalangi desain. Semua poin yang tersisa ada di bagian Ditunda.

## Ditunda

1. Pendaftaran dan verifikasi akun Biteship: setelah operasional berjalan. Status "diterima" otomatis bergantung pada ini, sementara admin menandai manual.
2. Margin: menunggu gambaran harga pokok dan target margin. Kombinasi biaya promosi tertinggi saat ini 13% (lihat bagian 7).
3. Pajak atas komisi affiliate: dicek bersama akuntan.