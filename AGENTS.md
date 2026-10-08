# AGENTS.md

Panduan untuk AI coding agent (Codex/ChatGPT, Claude Code, dan lainnya) yang bekerja di repo Inside Apparel.

## Wajib dibaca sebelum bekerja

1. `CLAUDE.md`: aturan pengembangan. Berlaku untuk semua agent, bukan hanya Claude.
2. `docs/MASTER_REQUIREMENT.md`: kebutuhan bisnis.
3. `docs/DECISIONS.md`: keputusan yang sudah final. Jangan diubah tanpa persetujuan owner.
4. `docs/INVENTORY_DESIGN.md` dan `docs/DATABASE.md`: bila pekerjaan menyentuh stok atau database.

## Aturan ringkas

- Jangan ubah core WordPress atau WooCommerce. Pakai hook, filter, dan REST API.
- Kode custom hanya di `wp-content/plugins/inside-apparel-core/` dan `wp-content/themes/inside-apparel/`.
- Jangan menaruh password, API key, atau token di kode atau repo. Jangan commit `.env` atau `wp-config.php`.
- Selalu validasi dan sanitasi input, escape output, cek capability, dan pakai nonce.
- Jangan memasang plugin tanpa persetujuan owner. Jelaskan dulu alasan, dampak keamanan, performa, dan pemeliharaannya.
- Perubahan stok hanya lewat `IA_Inventory_Service::adjust()`. Jangan menulis meta stok langsung.
- Akses order lewat WooCommerce CRUD API agar kompatibel dengan HPOS.
- Jangan menghapus atau mengganti fungsi yang sudah ada tanpa persetujuan.

## Alur kerja

- Satu fitur satu branch (`feat/...`, `fix/...`, `docs/...`). Jangan push langsung ke `main`. Gabungkan lewat pull request.
- Pesan commit memakai awalan `feat:`, `fix:`, `docs:`, atau `chore:`.
- Perubahan database: perbarui `docs/DATABASE.md`.
- Keputusan baru: tambahkan ke `docs/DECISIONS.md`.

## Tanda di dokumen

- **[USULAN]**, **[TBD]**, dan **[VERIFIKASI]** berarti belum final. Tanyakan ke owner, jangan diasumsikan.
- Jika permintaan bertentangan dengan dokumen, berhenti dan tanyakan.

## Review silang

Kode dari satu AI sebaiknya direview oleh AI lain atau owner. Fokus review: keamanan (sanitasi, escape, capability, nonce), stok (lewat service, pembaruan atomik), dan tidak menyentuh core.