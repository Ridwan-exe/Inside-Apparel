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

Contoh business rule:

```text
Lifetime Purchase >= Rp1.000.000
        ↓
Customer mendapatkan benefit membership