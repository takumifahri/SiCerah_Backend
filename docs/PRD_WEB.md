# PRD — SiCerah Web Dashboard (Pengurus Koperasi)

| | |
|---|---|
| **Produk** | SiCerah — Sistem Informasi Koperasi Cerdas & Transparan |
| **Dokumen** | PRD Web Dashboard (Multi-Role, Pengurus) |
| **Platform** | Web (React admin dashboard + REST API Laravel) |
| **Versi** | 1.0 — 10 Juli 2026 |
| **Status** | MVP Hackathon |
| **Dokumen terkait** | [PRD_MOBILE.md](PRD_MOBILE.md) |

---

## 1. Latar Belakang

Koperasi desa (Kopdes) punya masalah klasik: masyarakat tidak tertarik menjadi anggota karena tidak melihat manfaat nyata, dan tidak percaya karena keuangan koperasi tidak transparan. SiCerah menjawab ini dengan mendorong **4 perilaku anggota**:

1. **Menjadi anggota** — onboarding digital + "umpan" struk pembanding harga.
2. **Bertransaksi** — POS dengan manfaat langsung yang terlihat (harga anggota + KopPoin).
3. **Mengikuti kegiatan** — pengumuman & RSVP.
4. **Berkontribusi dalam keputusan** ⭐ — vote pengeluaran besar & aspirasi ("RAT mini berjalan terus").

Web dashboard adalah **"dapur"-nya**: tempat pengurus mencatat, memverifikasi, dan mengelola — yang hasilnya dikonsumsi anggota lewat mobile app. **Jantung produk adalah kepercayaan**: setiap transaksi harus berbukti dan terverifikasi oleh lawan transaksi sebelum dihitung sah.

### Kriteria penilaian yang ditarget

| Kriteria | Bobot | Dijawab oleh |
|---|---|---|
| Relevansi | 25% | Perilaku 4 (vote & aspirasi), segmentasi partisipasi |
| Inovasi | 20% | Engine verifikasi lawan transaksi |
| Dampak | 20% | Transparansi anggaran, parameter AD/ART (skalabel ke 83 ribu koperasi) |
| Kualitas Teknologi | 15% | Ledger append-only + hash, verifikasi multi-kanal |
| Kemudahan | 15% | Dashboard adaptif per role, alur sederhana |

## 2. Tujuan & Non-Tujuan

**Tujuan (MVP demo):**
- Pengurus bisa menjalankan operasi harian koperasi end-to-end: POS → kas → verifikasi → tampil transparan di mobile.
- Setiap rupiah keluar punya bukti (foto kuitansi / PDF struk digital) dan status verifikasi yang jelas.
- Minimal **satu kanal verifikasi lawan transaksi benar-benar berfungsi** (tanda tangan layar = jalur termudah).
- Aplikasi dapat dikonfigurasi mengikuti AD/ART tiap koperasi (parameter SHU, ambang persetujuan).

**Non-tujuan (MVP):**
- ~~OCR faktur~~ — **dibatalkan**; bukti memakai foto manual atau PDF struk digital yang di-generate sistem.
- Simpan-pinjam penuh (pengajuan, cicilan, reminder WA) — skema DB sudah siap, UI menyusul pasca-MVP.
- Forward contract, stok opname, evaluasi supplier — post-hackathon.
- Workflow approval multi-step — MVP cukup status Menunggu Verifikasi/Terverifikasi/Disengketakan.

## 3. Pengguna & Role

| Role | Deskripsi singkat | Prioritas MVP |
|---|---|---|
| **Administrator** | Kelola akun pengurus, pengaturan koperasi & parameter | MVP |
| **Bendahara** | Catat kas masuk/keluar berbukti, dashboard keuangan | MVP (paling kritis) |
| **Kasir** | POS gerai, struk digital, trigger verifikasi | MVP (paling tangible untuk demo) |
| **Sekretaris** | Manajemen anggota, CMS pengumuman | MVP |
| **Ketua** | Dashboard eksekutif, approval | Bonus |
| **Pengawas / Kades** | Read-only + audit trail | Bonus (kuat untuk pitch) |

Semua role login lewat satu pintu; konten dan akses dibedakan oleh middleware `role:` di API dan routing di frontend.

## 4. Fitur

Format prioritas: **[MVP]** wajib untuk demo · **[Bonus]** dikerjakan jika sempat · **[Skip]** tidak dikerjakan.

### F0 — Fondasi (semua role)

**F0.1 [MVP] Login berbasis peran** *(sudah dibangun)*
- Login email+password → Sanctum token. Akun nonaktif ditolak login dan token aktifnya dicabut saat dinonaktifkan.
- API: `POST /api/login`, `POST /api/logout`, `GET /api/user`.
- ✅ AC: user dengan `is_active=false` mendapat pesan "Akun Anda telah dinonaktifkan"; user role kasir yang memanggil endpoint admin mendapat 403.

**F0.2 [MVP] Dashboard adaptif**
- Satu layout, konten berbeda per role: ringkasan kas hari ini, pemasukan vs pengeluaran, realisasi anggaran per pos (progress bar), item yang butuh tindakan.
- ✅ AC: login sebagai bendahara vs kasir menampilkan widget berbeda tanpa route terpisah.

### F1 — Administrator

**F1.1 [MVP] Manajemen Akun Pengurus (RBAC)** *(sudah dibangun)*
- CRUD akun pengurus (ketua, kasir, bendahara, logistik, sekretaris, pengawas) + aktif/nonaktif.
- Guard: admin tidak bisa menonaktifkan/menghapus akun sendiri; nonaktif = semua token dicabut.
- API: `GET|POST /api/admin/akun-pengurus`, `GET|PUT|DELETE /api/admin/akun-pengurus/{id}`, `PATCH /api/admin/akun-pengurus/{id}/status`.
- ✅ AC: akun yang dinonaktifkan langsung terlempar dari sesi berjalan (request berikutnya 401/403).

**F1.2 [MVP] Parameter SHU & AD/ART**
- Set %Jasa Modal, %Jasa Usaha, alokasi SHU (dana cadangan, porsi anggota, jasa pengurus), ambang auto-announce, **ambang vote anggota** (`member_approval_threshold`) dan kuorum (`approval_quorum_pct`), periode tahun buku.
- Parameter di-lock setelah RAT (oleh Ketua) — tidak bisa diubah sampai RAT berikutnya.
- Tabel: `shu_parameters`, `cooperative_profiles`, `fiscal_years`.
- ✅ AC: mengubah parameter yang `is_locked=true` ditolak dengan pesan jelas. Ini bukti klaim "app menyesuaikan diri ke AD/ART tiap koperasi".

**F1.3 [Bonus] Profil koperasi** — nama, alamat, badan hukum, logo, nomor WA bot Fonnte, saldo kas awal (C₀). Tabel: `cooperative_profiles`.

### F2 — Bendahara (paling kritis — feed transparansi mobile)

**F2.1 [MVP] Catat pemasukan**
- Form: nominal, sumber (simpanan, penjualan, off-taker, cicilan), kategori pos, tanggal, catatan.
- Tabel: `transactions` (type=masuk), `savings` untuk simpanan.
- ✅ AC: entri muncul di buku kas mobile anggota (setelah terverifikasi) < 5 detik.

**F2.2 [MVP] Catat pengeluaran + bukti wajib**
- Form pengeluaran dengan upload bukti (foto kuitansi/nota atau PDF struk digital). **Tanpa bukti → otomatis "Menunggu Verifikasi"**, tidak dihitung sah, tidak masuk kas & SHU.
- Pengeluaran > `announcement_threshold` → auto-buat pengumuman berbukti ke semua anggota.
- Pengeluaran > `member_approval_threshold` → auto-buat poll persetujuan anggota (lihat F7.3); transaksi tidak dieksekusi sebelum poll disetujui.
- Tabel: `transactions` (type=keluar, `proof_path`), `announcements` (`is_auto`), `polls`.
- ✅ AC: submit tanpa bukti tetap tersimpan berstatus `menunggu_verifikasi`; pengeluaran Rp 2.000.000 (di atas ambang default Rp 1.000.000) otomatis memunculkan poll di mobile.

**F2.3 [MVP] Dashboard keuangan**
- Kas harian/mingguan/bulanan, grafik pemasukan vs pengeluaran, realisasi vs rencana per pos.
- Hanya transaksi `terverifikasi` yang masuk angka kas & basis SHU; yang pending/disengketakan tampil terpisah.
- Tabel: `transactions`, `budget_posts`, `financial_snapshots` (snapshot L_t untuk grafik SHU mobile).

**F2.4 [Bonus] Manajemen anggaran** — input rencana tahunan per pos (dari RAT), ceiling, auto-flag mendekati/lewat batas. Untuk demo boleh hardcode rencana. Tabel: `budget_posts`.

**F2.5 [Bonus] Rekonsiliasi kas harian** — terima tutup kas Kasir, konfirmasi/flag selisih. Tabel: `cash_register_closings`.

### F3 — Kasir (POS)

**F3.1 [MVP] POS transaksi gerai**
- Pilih **anggota** (scan QR / cari nama) atau **non-anggota**, input item, hitung total, pembayaran.
- Anggota dikenakan `member_price`, non-anggota `price`; sistem menghitung `member_savings`.
- Anggota → auto-catat kontribusi (U_i untuk Jasa Usaha) + generate KopPoin sesuai `point_rules`.
- Tabel: `sales`, `sale_items`, `products`, `point_transactions`.
- ✅ AC: transaksi anggota otomatis menambah poin dan muncul di riwayat poin mobile.

**F3.2 [MVP] Struk digital PDF + struk WA pembanding harga**
- Setiap transaksi generate **PDF struk digital** (`sales.receipt_pdf_path`) — dipakai juga sebagai bukti faktur di `transactions.proof_path` saat penjualan direkap ke kas.
- **Non-anggota**: struk dikirim via WA (Fonnte) menampilkan selisih "kalau jadi anggota kamu hemat Rp X" → umpan akuisisi.
- Untuk transaksi dengan warga (beli panen dsb.), struk ini adalah titik pemicu **verifikasi lawan transaksi** (F7.1).
- Tabel: `sales` (`customer_wa`, `member_savings`, `receipt_sent_at`, `receipt_pdf_path`).
- ✅ AC: non-anggota dengan nomor WA menerima struk berisi perbandingan harga; PDF tersimpan dan bisa dibuka dari detail transaksi.

**F3.3 [Bonus] Manajemen produk** — CRUD barang, harga umum + harga anggota, stok. Tabel: `products`.
**F3.4 [Bonus] Stok alert** — notifikasi saat stok < `min_stock`.
**F3.5 [Bonus] Tutup kas harian** — rekap penjualan, input kas fisik, hitung selisih, submit ke Bendahara.

### F4 — Sekretaris

**F4.1 [MVP] Manajemen anggota**
- Daftar + search, data simpanan, status keanggotaan (aktif/pasif/keluar + riwayat & alasan), tier kontribusi, tambah anggota baru (onboarding digital: KTP/domisili).
- **Nomor WA didaftarkan & dikunci ke identitas di sini** (`wa_verified_at`) — kunci anti-substitusi untuk engine verifikasi.
- Kartu anggota digital (QR dari `no_anggota`) di-generate otomatis.
- Tabel: `users`, `member_status_histories`.
- ✅ AC: anggota baru langsung bisa login mobile dan punya QR kartu anggota.

**F4.2 [MVP] CMS pengumuman**
- Buat pengumuman: judul, isi, kategori (umum/RAT-rapat/keuangan/komoditas), tanggal publish, lampiran opsional; channel in-app / WA blast / keduanya; bisa dijadwalkan.
- Muncul di beranda mobile anggota.
- Tabel: `announcements`, `announcement_reads`.

**F4.3 [Skip] Notulen/dokumen** — upload berita acara (tabel `documents` & `meetings` sudah tersedia untuk pasca-MVP).

### F5 — Ketua

**F5.1 [Bonus] Dashboard eksekutif** — ringkasan semua unit: keuangan, stok, anggota aktif, transaksi hari ini, tren bulanan.
**F5.2 [Bonus] Approval** — tinjau transaksi "Menunggu Verifikasi" / di atas ambang. MVP cukup status pending/verified tanpa workflow multi-step. Tabel: `approvals`.
**F5.3 [Bonus] Lock parameter tahunan** — kunci `shu_parameters` setelah RAT (pasangan F1.2).

### F6 — Pengawas / Kepala Desa

**F6.1 [Bonus] Dashboard read-only** — semua keuangan, realisasi anggaran, log transaksi, filter per periode. Tanpa akses edit/hapus (di-enforce middleware).
**F6.2 [Bonus] Audit trail** — log siapa input apa, kapan, buktinya apa; append-only. Tabel: `audit_logs`, `audit_flags`, `integrity_hash`/`prev_hash` di `transactions`.

### F7 — Engine lintas-role (jantung produk)

**F7.1 [MVP] Engine verifikasi lawan transaksi** ⭐ *pembeda utama — wajib minimal satu kanal jalan*
- Begitu transaksi dengan warga dicatat (beli panen, pengeluaran ke vendor), sistem memicu konfirmasi ke lawan transaksi lewat kanal yang sesuai:
  - **Tanda tangan layar** (termudah — target utama demo): lawan transaksi tanda tangan di layar kasir/petugas → `signature_path`.
  - **WA**: link konfirmasi bertoken ke nomor WA yang sudah dikunci ke identitas.
  - **In-app**: notifikasi konfirmasi untuk anggota terdaftar.
- Alur status: `menunggu_verifikasi` → `terverifikasi` / `disengketakan`. **Hanya yang terverifikasi masuk perhitungan kas & SHU.**
- Tabel: `transaction_verifications` (counterparty, channel, token, signature_path, dispute_reason).
- ✅ AC (demo): kasir mencatat pembelian panen dari warga → warga tanda tangan di layar → status berubah terverifikasi → nominal masuk kas → tampil di buku kas mobile. Jika di demo hanya "kasir menandai sendiri", klaim kebaruan gugur — ini tidak boleh terjadi.

**F7.2 [MVP] Engine proyeksi SHU**
- Hitung otomatis proyeksi SHU per anggota dari jasa modal (simpanan) + jasa usaha (kontribusi transaksi), memakai parameter F1.2 dan laba berjalan L_t.
- Snapshot harian ke `financial_snapshots` → sumber grafik SHU di mobile. Proyeksi selalu berlabel estimasi, terpisah dari realisasi tahunan (`shu_distributions`).
- ✅ AC: transaksi baru terverifikasi mengubah angka proyeksi SHU anggota terkait pada snapshot berikutnya.

**F7.3 [MVP] Vote persetujuan pengeluaran besar** (sisi web)
- Pengeluaran > ambang → sistem membuat poll persetujuan (opsi Setuju/Tolak) yang divote anggota via mobile; hasil (kuorum & mayoritas) menentukan apakah transaksi boleh dieksekusi.
- Web menampilkan status poll di detail transaksi; Bendahara tidak bisa menandai transaksi sah sebelum poll disetujui.
- Tabel: `polls` (pollable→transaction, type=persetujuan_pengeluaran), `poll_options`, `poll_votes`.

**F7.4 [MVP-ringan] Segmentasi partisipasi anggota**
- Grafik anggota aktif vs pasif, dipecah per kelompok usia (`tanggal_lahir`), menandai segmen partisipasi terendah.
- Menjawab challenge question TOR ("kelompok usia mana partisipasi terendah?"). Versi minimal: satu halaman grafik segmentasi.
- Sumber: agregasi `sales`, `point_transactions`, `poll_votes`, `announcement_reads`.

**F7.5 [Bonus] Offline-first + sync** — idempotency via `transactions.client_uuid`; kalau mepet, disimulasikan saat demo.

## 5. Alur Kunci

**A. Penjualan non-anggota (umpan akuisisi):** Kasir POS → pilih non-anggota + input WA → bayar → PDF struk → WA struk "hemat Rp X kalau jadi anggota" → warga tertarik → didaftarkan Sekretaris (F4.1).

**B. Pengeluaran besar (rantai kepercayaan penuh):** Bendahara input pengeluaran Rp 2 jt + PDF/foto bukti → sistem: di atas ambang vote → poll ke semua anggota (mobile) → kuorum & mayoritas setuju → Bendahara eksekusi → verifikasi lawan transaksi (vendor tanda tangan layar) → terverifikasi → auto-pengumuman berbukti → masuk kas, L_t, dan proyeksi SHU.

**C. Beli panen dari warga:** Petugas/Kasir catat pembelian → struk PDF → warga konfirmasi (tanda tangan layar / WA) → terverifikasi → masuk kas.

## 6. Kebutuhan Non-Fungsional

- **Keamanan**: Sanctum token; middleware `role:`; akun nonaktif tercabut sesinya; password di-hash.
- **Integritas data**: ledger append-only — koreksi lewat entri baru (`corrects_transaction_id`), bukan edit; `integrity_hash` + `prev_hash` untuk audit.
- **Bahasa**: seluruh UI & pesan error Bahasa Indonesia.
- **Performa demo**: dashboard < 2 detik dengan data seed; perubahan web → mobile terasa real-time (polling ≤ 5 detik cukup untuk MVP).
- **Aturan bisnis di service layer** (bukan skema): bukti wajib untuk kas keluar, hanya terverifikasi masuk SHU, threshold → poll/pengumuman otomatis.

## 7. Stack & Dependensi

- **Backend**: Laravel 12 + Sanctum + MySQL (`simkopdes`) — skema 40 tabel sudah termigrasi.
- **Frontend**: React admin dashboard (shadcn/ui admin / TailAdmin), Recharts untuk grafik & progress bar.
- **Integrasi**: Fonnte (WA blast, struk WA, link verifikasi), generator PDF (dompdf/laravel-pdf) untuk struk digital.

## 8. Urutan Build yang Disarankan

1. ✅ Auth + RBAC + Manajemen Akun Pengurus (selesai)
2. Pengaturan parameter (F1.2) + seed profil koperasi — semua engine bergantung ke sini
3. Produk + POS + PDF struk (F3.1–F3.2)
4. Kas masuk/keluar + bukti wajib (F2.1–F2.2)
5. Engine verifikasi tanda tangan layar (F7.1) ⭐
6. Poll pengeluaran besar (F7.3) + pengumuman otomatis
7. Dashboard keuangan + proyeksi SHU (F2.3, F7.2)
8. Manajemen anggota + CMS pengumuman (F4)
9. Segmentasi partisipasi (F7.4)
10. Bonus sesuai sisa waktu (Ketua, Pengawas, WA channel verifikasi)

## 9. Risiko

| Risiko | Mitigasi |
|---|---|
| Engine verifikasi tidak selesai → klaim kebaruan gugur di depan juri | Prioritaskan kanal tanda tangan layar (tanpa dependensi eksternal); WA menyusul |
| Fonnte gagal/lambat saat demo | Fallback: tampilkan payload WA yang "terkirim" di UI; kirim asli hanya di dry-run |
| Data demo kosong/tidak meyakinkan | Seeder lengkap: pengurus semua role, produk 2 harga, transaksi campur status, poll berjalan |
| Parameter SHU salah hitung di depan juri | Unit test rumus SHU dengan angka contoh yang juga dipakai di slide pitch |
