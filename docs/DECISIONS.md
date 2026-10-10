# DECISIONS

Log keputusan penting Inside Apparel. Tujuannya agar manusia dan AI mana pun yang melanjutkan pekerjaan tidak mengulang diskusi atau membuat keputusan yang bertentangan.

Aturan:
- Keputusan berstatus **Disetujui** tidak diubah tanpa persetujuan owner.
- Keputusan berstatus **Usulan** menunggu persetujuan owner. Jangan diperlakukan sebagai final.
- Keputusan baru ditambahkan di bawah dengan ID berikutnya. Keputusan yang diganti ditandai **Diganti oleh D-xxx**, tidak dihapus.

| ID | Tanggal | Keputusan | Alasan | Status |
|----|---------|-----------|--------|--------|
| D-001 | 2026-10-08 | Platform WordPress + WooCommerce dengan custom development | Sesuai stack proyek | Disetujui |
| D-002 | 2026-10-08 | Logika bisnis di plugin `inside-apparel-core`, tampilan di tema `inside-apparel` | Pemisahan tanggung jawab, tidak menyentuh core | Disetujui |
| D-003 | 2026-10-08 | Stok pusat = stok WooCommerce, unit stok SKU varian | Satu sumber kebenaran, memakai fondasi bawaan | Disetujui |
| D-004 | 2026-10-08 | Tanpa safety buffer stok | Volume order awal kecil | Disetujui |
| D-005 | 2026-10-08 | Sinkron Shopee gagal: kelola stok manual, stok tidak otomatis di-set 0 | Volume awal kecil, ditinjau ulang saat order naik | Disetujui |
| D-006 | 2026-10-08 | Order Shopee tidak dihitung ke lifetime purchase | Pembeli Shopee sulit dicocokkan dengan akun website | Disetujui |
| D-007 | 2026-10-08 | Membership Bronze Rp1.000.000 (3%), Silver Rp2.500.000 (5%), Gold Rp5.000.000 (7%), level permanen | Keputusan owner | Disetujui |
| D-008 | 2026-10-08 | Voucher membership 1x pakai, berikutnya 1 bulan setelah pemakaian, naik level langsung diberikan | Keputusan owner | Disetujui |
| D-009 | 2026-10-08 | Komisi affiliate sama dengan diskon affiliate yang diterima pembeli (1-3% per produk, default 1%), approved 1 hari setelah completed | Sederhana dan margin terkendali | Disetujui |
| D-010 | 2026-10-08 | Order completed saat customer konfirmasi diterima atau otomatis 3 hari setelah diterima | Keputusan owner (tafsir perlu dikonfirmasi) | Disetujui |
| D-011 | 2026-10-08 | Retur maksimal 3 hari sejak diterima, wajib bukti unboxing, ongkir ditanggung customer kecuali salah kirim/cacat | Keputusan owner | Disetujui |
| D-012 | 2026-10-08 | Payment gateway Midtrans dengan plugin resmi | Keputusan owner | Disetujui |
| D-013 | 2026-10-08 | Pengiriman J&T dan JNE lewat API Biteship. Akun didaftarkan setelah operasional berjalan, sementara resi diinput manual | Keputusan owner (input manual sementara adalah usulan) | Disetujui sebagian |
| D-014 | 2026-10-08 | Backend admin custom dengan acuan Jubelio, dibangun bertahap | Keputusan owner | Disetujui |
| D-015 | 2026-10-08 | Produk bundle dan konsinyasi tidak dibangun | Tidak diperlukan | Disetujui |
| D-016 | 2026-10-08 | Tahap awal: email otomatis, WhatsApp manual oleh admin | Keputusan owner | Disetujui |
| D-017 | 2026-10-08 | Yang tampil ke customer dan dikirim ke Shopee adalah angka Tersedia (stok dikurangi tahanan checkout) | Mengamankan stok selama order belum dibayar | Usulan |
| D-018 | 2026-10-08 | Order Shopee saat stok kurang: stok menjadi 0 dan dicatat sebagai oversold, order tetap diterima | Penjualan sudah terjadi di Shopee | Usulan |
| D-019 | 2026-10-08 | Antrean sinkronisasi memakai Action Scheduler bawaan WooCommerce | Tanpa plugin tambahan | Usulan |
| D-020 | 2026-10-08 | Rekonsiliasi stok Shopee tiap 30 menit, polling order tiap 5 menit | Cukup untuk 100 SKU dan 100 order per hari | Usulan |
| D-021 | 2026-10-10 | Retur dicatat manual lewat Sesuaikan > "Retur dikembalikan ke stok". Fitur "Restock refunded items" di refund WooCommerce tidak dipakai | Retur masuk lewat WhatsApp dan perlu dicek kondisi barang; menjaga satu jalur pencatatan stok | Usulan |
| D-022 | 2026-10-10 | Stok opname memakai angka Fisik (stok WC + dalam proses): admin memasukkan hitungan rak, sistem menghitung stok WC | Barang order dibayar yang belum dikirim masih ada di rak | Usulan |
