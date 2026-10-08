# INSIDE APPAREL
# MASTER REQUIREMENT

Version: 0.1
Status: Draft

---

# 1. PROJECT OVERVIEW

Inside Apparel adalah platform e-commerce yang digunakan sebagai pusat penjualan online dan pengelolaan customer, produk, order, inventory, serta integrasi marketplace.

Platform utama menggunakan WordPress dan WooCommerce dengan custom development untuk kebutuhan bisnis Inside Apparel.

Sales channel yang direncanakan:

1. Website Inside Apparel
2. Shopee
3. Marketplace lain di masa depan

Sistem harus dirancang agar dapat berkembang tanpa perlu membangun ulang seluruh sistem ketika jumlah produk, customer, order, atau sales channel bertambah.

Untuk Backend proses pesanan, upload produk, edit stok, dll saya ingin custom dan acuannya pada website JUBELIO POS
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

Inside Apparel akan memiliki sistem membership berdasarkan lifetime purchase customer.

Business rule:

Lifetime Purchase >= Rp1.000.000
        ↓
Customer mendapatkan benefit membership

# 6. VOUCHER DAN PROMOTION

Jenis voucher:

potongan nominal
potongan persentase (dengan batas maksimum)
gratis ongkir
voucher khusus level membership
voucher khusus customer tertentu

Aturan voucher:

kode unik
periode berlaku
minimum belanja
kuota total dan kuota per customer
dapat/tidak dapat digabung dengan voucher lain [TBD]
berlaku untuk produk atau kategori tertentu
bisa dinonaktifkan admin kapan saja
setiap pemakaian tercatat (customer, order, nilai)

Prioritas: gunakan sistem kupon bawaan WooCommerce sebagai fondasi, tambahkan logika custom hanya jika kebutuhan tidak tercakup.

# 7. AFFILIATE / BRAND AMBASSADOR
Setiap affiliate memiliki kode referral/voucher unik.
Penjualan melalui kode atau link affiliate tercatat atas nama affiliate.
Komisi dihitung dari order completed, bukan saat order dibuat.
Persentase atau nominal komisi [TBD] (sama untuk semua atau per affiliate).
Komisi order yang dibatalkan atau di-refund dibatalkan.
Affiliate melihat: jumlah order, total penjualan, komisi pending, komisi disetujui, komisi dibayar.
Pembayaran komisi [TBD]: manual oleh admin atau terjadwal. Minimum payout [TBD].
Pencegahan penyalahgunaan: affiliate tidak boleh memakai kodenya untuk order sendiri.

# 8. PRODUCT CATALOG
kategori dan sub-kategori
produk variabel: ukuran, warna
SKU unik per varian (kunci pemetaan ke Shopee)
harga normal dan harga promo
gambar produk
berat dan dimensi (untuk ongkir)
deskripsi dan size guide
pencarian dan filter (kategori, ukuran, warna, harga)
status: tersedia, habis, pre-order [TBD]

# 9. CENTRAL INVENTORY

Prinsip:

Stok dikelola dari satu sumber kebenaran (source of truth).
Website dan Shopee membaca dan memperbarui stok melalui sumber yang sama.
Setiap perubahan stok tercatat: produk/SKU, jumlah, alasan, sumber (website/Shopee/admin), waktu.

Aturan:

Unit stok adalah SKU varian, bukan produk induk.
Stok tidak boleh negatif.
Order website mengurangi stok langsung; pembatalan mengembalikan stok.
Order Shopee mengurangi stok pusat, lalu stok baru disinkronkan ke channel lain.
Pencegahan overselling: [TBD] safety buffer per channel (contoh: stok tampil = stok pusat dikurangi 1).
Jika sinkronisasi gagal, sistem mencoba ulang dan menandai SKU yang bermasalah untuk ditinjau admin.
Penyesuaian stok manual oleh admin wajib menyertakan alasan.

Detail desain ditulis di dokumen terpisah: docs/INVENTORY_DESIGN.md.

# 10. ORDER MANAGEMENT

Status order: pending payment, processing, packed, shipped, completed, cancelled, refunded [TBD] konfirmasi daftar.

Order dari website dan Shopee terlihat dalam satu daftar dengan penanda sumber.
Admin dapat mengubah status, menambah catatan, dan mencetak invoice/label.
Setiap perubahan status tercatat (siapa, kapan).
Pembatalan dan refund mengikuti kebijakan [TBD].

# 11. PAYMENT

Mata uang: IDR.
Payment gateway: [TBD] (contoh: Midtrans, Xendit, DOKU).
Metode: transfer bank/virtual account, e-wallet, QRIS, kartu [TBD].
Batas waktu pembayaran dan pembatalan otomatis [TBD].
Data kartu tidak pernah disimpan di server Inside Apparel.

# 12. SHIPPING

Perhitungan ongkir berdasarkan alamat dan berat.
Kurir: [TBD] (contoh: JNE, J&T, SiCepat) dan penyedia tarif (API ongkir atau tarif manual).
Input resi oleh admin atau otomatis.
Customer dapat melacak pengiriman dari halaman order.

# 13. NOTIFICATION

Notifikasi dikirim saat: order dibuat, pembayaran diterima, order dikirim (dengan resi), order selesai, naik level membership, voucher diterima.

Kanal awal: email.
WhatsApp [TBD] (fase berikutnya, butuh penyedia resmi).
Template dapat diubah admin.

# 14. SHOPEE INTEGRATION

Memakai Shopee Open Platform API.
Sinkronisasi: produk/SKU, stok, order, status order.
Arah data stok: pusat → Shopee.
Order Shopee masuk ke sistem pusat dan mengurangi stok pusat.
Pemetaan SKU website ↔ item/model Shopee wajib ada.
Token API disimpan aman (bukan di kode, bukan di repo).
Penanganan rate limit dan kegagalan API (retry + log).

# 15. NON-FUNCTIONAL REQUIREMENTS

Keamanan: ikuti aturan di CLAUDE.md.
Performa: halaman produk dan checkout termuat cepat di koneksi mobile.
Mobile-first: mayoritas pembeli diperkirakan memakai handphone.
Backup: backup database dan file terjadwal [TBD].
Logging: error integrasi dan perubahan stok tercatat.
Skalabilitas: penambahan produk, order, dan channel tidak memerlukan pembangunan ulang.

16. OPEN QUESTIONS

Harus diputuskan sebelum fitur terkait dibuat:

- Level (Gold, Silver, Bronze), nilai (Gold lifetime purchase 5.000.000, Silver lifetime purchase 2.500.000, Bronze lifetime purchase 1.000.000), dan benefit membership (Akana mendapatkan voucher diskon sesuai dengan levelnya).
- Apakah order Shopee dihitung ke lifetime purchase? Tidak
- Skema komisi affiliate dan cara pembayarannya. Untuk komisi setiap penjualan melalui refferal link akan mendapatkan 10-15% dari harga produk yang dijual, dan cara pembayarannya saya ingin seperti ada page tersendiri untuk melihat saldo, tarik saldo
- Payment gateway dan kurir yang dipakai. Untuk payment gateway saya ingin memudahkan customer pada intinya, misal ada pilihan QRIS dan Virtual Account, untuk kurir yang terbayang bisa generate resi langsung dari backend dan sudah otomatis barcode pada jasa kirimnya
- Safety buffer stok dan perilaku saat sinkronisasi gagal. Saat sinkronisasi gagal, alihkan pada manual stok
- Kebijakan pembatalan, retur, dan refund. Masih dilakukan dengan cara Whatsapp
- Perlu WhatsApp atau cukup email? Boleh keduanya
- Jumlah produk/SKU awal dan target order per hari. Sebanyak mungkin