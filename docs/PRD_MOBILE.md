# PRD — SiCerah Mobile App (Anggota & Petugas)

| | |
|---|---|
| **Produk** | SiCerah — Sistem Informasi Koperasi Cerdas & Transparan |
| **Dokumen** | PRD Mobile App (Flutter — Anggota & Petugas) |
| **Platform** | Flutter (Android prioritas demo), konsumsi REST API Laravel |
| **Versi** | 1.0 — 10 Juli 2026 |
| **Status** | MVP Hackathon |
| **Dokumen terkait** | [PRD_WEB.md](PRD_WEB.md) |

---

## 1. Latar Belakang

Kalau web dashboard adalah "dapur", mobile app adalah **"jendela" yang kita ubah menjadi "ruang partisipasi"** — kata kunci TOR. Mobile app adalah alasan warga mau jadi anggota dan tetap aktif, dengan menjawab dua pertanyaan yang selama ini tak terjawab:

- *"Uang koperasi dipakai apa?"* → transparansi real-time berbukti.
- *"Aku dapat apa?"* → manfaat langsung yang selalu terlihat (hemat harga anggota, KopPoin, proyeksi SHU).

Dan satu langkah lebih jauh: anggota **ikut memutuskan** (vote pengeluaran besar, aspirasi, polling) — Perilaku 4 yang menutup gap Relevansi 25%.

### Dua persona pengguna

| Persona | Siapa | Kebutuhan utama |
|---|---|---|
| **Anggota** | Warga desa pemilik koperasi | Lihat manfaat & keuangan, kumpulkan/tukar poin, ikut memutuskan |
| **Petugas** | Pengurus lapangan (mencatat di luar kantor: beli panen, bayar vendor) | Input transaksi cepat + bukti, verifikasi, laporan ringkas |

Role petugas mengikuti akun pengurus dari web (bendahara/kasir/logistik yang bekerja mobile); menu app menyesuaikan `role` user.

## 2. Tujuan & Non-Tujuan

**Tujuan (MVP demo):**
- Anggota melihat kondisi keuangan koperasi yang **sama persis** dengan pembukuan web, lengkap dengan bukti, tanpa jeda berarti.
- Setiap transaksi anggota menghasilkan umpan balik positif instan ("kamu hemat Rp X", "+Y poin").
- Anggota bisa memberi suara pada pengeluaran besar dan mengajukan aspirasi dari HP.
- Petugas bisa mencatat transaksi lapangan dengan bukti dan verifikasi lawan transaksi.

**Non-tujuan (MVP):**
- ~~OCR foto nota~~ — dibatalkan; bukti = foto manual atau PDF struk digital dari sistem.
- Pengajuan pinjaman & cicilan dari app — pasca-MVP (skema DB siap).
- iOS build — Android dulu untuk demo.
- Chat/DM antar anggota — di luar scope.

## 3. Fitur — Sisi ANGGOTA

Format prioritas: **[MVP]** wajib demo · **[Bonus]** jika sempat.

### A1 — Beranda

**A1.1 [MVP] Ringkasan beranda**
- Kartu proyeksi SHU terkini, pengumuman terbaru, ringkasan aktivitas kas koperasi hari ini, saldo KopPoin.
- API: agregat dari `financial_snapshots`, `announcements`, `point_transactions`.

**A1.2 [MVP] Kartu manfaat langsung** ⭐ *penahan "kapok"*
- Setelah tiap transaksi di gerai: "Kamu hemat **Rp X** vs harga luar" + poin yang didapat. **Selalu dibingkai positif.**
- Sumber: `sales.member_savings`, `sale_items.regular_unit_price` vs `unit_price`.
- ✅ AC: transaksi POS di web memunculkan kartu ini di beranda anggota ≤ 5 detik.

**A1.3 [MVP] Kartu anggota digital**
- QR dari `no_anggota` untuk di-scan kasir saat belanja (identifikasi kontribusi U_i). Tampil foto nama & status keanggotaan.

**A1.4 [MVP] Notifikasi in-app**
- Pengumuman penting, konfirmasi transaksi yang melibatkan anggota, poll baru, hasil vote, update SHU signifikan.
- Tabel: `notifications`.

### A2 — SHU Tracker

**A2.1 [MVP] Grafik proyeksi SHU (ala Stockbit)**
- Line chart proyeksi SHU bulanan yang naik-turun mengikuti laba berjalan L_t. Sumbu X bulan, Y estimasi Rupiah.
- Sumber: `financial_snapshots` + parameter `shu_parameters`.

**A2.2 [MVP] Breakdown dua faktor**
- (1) Laba koperasi — faktor kolektif; (2) Kontribusi pribadi (jasa modal dari simpanan + jasa usaha dari belanja) — faktor personal. Terpisah dan jelas.

**A2.3 [MVP] Label estimasi wajib**
- Setiap angka SHU selalu berlabel: *"\*Estimasi berdasarkan laba berjalan. Angka final ditetapkan di RAT."* Proyeksi terpisah dari realisasi (`shu_distributions`).

**A2.4 [MVP] Tampilan jujur saat rugi**
- Saat L_t ≤ 0: tidak disembunyikan — pesan jelas "SHU belum bisa dihitung karena koperasi sedang defisit", dan **KopPoin tetap berjalan normal**.
- ✅ AC: seed satu periode defisit dan tunjukkan state ini di demo (nilai jual kejujuran).

**A2.5 [Bonus] Simulasi actionable** — "Jika simpananmu naik Rp X, estimasi SHU naik Rp Y" (slider interaktif).
**A2.6 [Bonus] Histori SHU tahun lalu** — dari `shu_distributions` + status pencairan.

### A3 — Transparansi ⭐ (Dampak 20%)

**A3.1 [MVP] Dashboard kesehatan koperasi**
- Saldo kas saat ini, total pemasukan/pengeluaran periode berjalan, jumlah anggota aktif.

**A3.2 [MVP] Realisasi anggaran real-time**
- Tiap pos anggaran = progress bar rencana vs realisasi; alert visual jika overbudget. Sumber: `budget_posts` + `transactions` terverifikasi.

**A3.3 [MVP] Buku kas real-time + detail berbukti**
- Daftar kronologis semua transaksi: nominal, kategori, vendor, **status** (Menunggu Verifikasi / Terverifikasi / Disengketakan), tombol "Lihat Bukti" (foto kuitansi / PDF struk).
- Detail: diinput siapa, diverifikasi siapa, kapan.
- ✅ AC: transaksi yang baru dicatat Bendahara di web muncul di list ini dengan status dan buktinya bisa dibuka.

**A3.4 [MVP] Pengumuman berbukti**
- Pengeluaran besar otomatis tampil sebagai pengumuman + lampiran bukti ke semua anggota (dibuat otomatis oleh web, F2.2).

### A4 — Partisipasi ⭐⭐ (Perilaku 4 — gap TOR, wajib MVP)

**A4.1 [MVP] Vote persetujuan pengeluaran besar ("RAT mini")**
- Poll masuk via notifikasi: deskripsi pengeluaran, nominal, bukti pendukung, pilihan **Setuju/Tolak**, deadline, progress kuorum live.
- Satu anggota satu suara (`poll_votes` unique). Hasil transparan setelah ditutup.
- ✅ AC (demo): Bendahara input pengeluaran Rp 2 jt di web → poll muncul di HP anggota → anggota vote → kuorum tercapai → web menampilkan "disetujui anggota" → transaksi dieksekusi.

**A4.2 [MVP] Aspirasi + polling**
- Form usul (judul, isi, kategori) → anggota lain bisa **dukung** (`aspiration_supports`) → pengurus menanggapi / mengangkat jadi poll.
- Tab polling umum untuk keputusan koperasi non-pengeluaran (`polls` type=keputusan/jajak_pendapat).

**A4.3 [Bonus] RSVP kegiatan** — jadwal rapat/kegiatan + konfirmasi kehadiran (`meetings`, `meeting_participants`); membuat "mengikuti kegiatan" terwakili nyata.

### A5 — KopPoin

**A5.1 [MVP] Dashboard poin** — total poin, riwayat perolehan bulan ini (belanja, simpanan, dst. dari `point_transactions`).
**A5.2 [MVP] Katalog & request tukar poin** — pilih benefit (`point_catalog_items`) → submit → pantau status: Menunggu → Disetujui → Diklaim (`point_redemptions`).
**A5.3 [MVP] Poin tetap jalan saat rugi** — banner/pesan eksplisit saat koperasi defisit (pasangan A2.4).
**A5.4 [Bonus] Leaderboard kontribusi** — ranking poin bulan ini; hanya nama + poin, data finansial tetap privat.
**A5.5 [Bonus] Badge pencapaian** — milestone kehadiran/transaksi/simpanan.

### A6 — Verifikasi (anggota sebagai lawan transaksi)

**A6.1 [MVP] Konfirmasi transaksi in-app**
- Saat koperasi mencatat transaksi yang melibatkan anggota (beli panen darinya, dsb.), anggota menerima permintaan konfirmasi: detail transaksi + tombol **Konfirmasi** / **Sengketakan** (dengan alasan).
- Tabel: `transaction_verifications` (channel=in_app).
- ✅ AC: sengketa mengubah status transaksi menjadi `disengketakan` dan mengeluarkannya dari kas & SHU.

## 4. Fitur — Sisi PETUGAS

### P1 — Beranda

**P1.1 [MVP] Dashboard petugas** — kas masuk/keluar/net hari ini; antrian aksi: transaksi pending verifikasi, poll menunggu hasil.
**P1.2 [MVP] Notifikasi aksi diperlukan** — transaksi pending, hasil vote pengeluaran, (bonus) stok menipis.

### P2 — Input Transaksi

**P2.1 [MVP] Input manual + bukti**
- Form: nominal, jenis (masuk/keluar), kategori pos, vendor/penerima, keterangan, **upload foto bukti** (kamera langsung).
- Kas keluar tanpa bukti → tersimpan sebagai `menunggu_verifikasi` (tidak sah).
- Offline-aware: simpan lokal dengan `client_uuid`, kirim saat online (lihat P5.1).

**P2.2 [MVP] Trigger verifikasi lawan transaksi** ⭐
- Setelah input transaksi dengan warga: pilih kanal konfirmasi — **tanda tangan layar** (warga tanda tangan langsung di HP petugas; kanal utama demo), kirim link WA, atau in-app (anggota terdaftar).
- ✅ AC: alur beli panen — petugas input Rp 400.000 ke Bu Siti → Bu Siti tanda tangan di layar → status terverifikasi → masuk buku kas semua anggota.

**P2.3 [Bonus] Input via voice note** — transkripsi + parsing otomatis jadi draft transaksi untuk dikonfirmasi.
**P2.4 [Bonus] Input via WhatsApp bot (Fonnte)** — pesan terstruktur → antrian verifikasi (`transactions.source=whatsapp`).

### P3 — Verifikasi Internal

**P3.1 [MVP] Dual approval** — transaksi yang diinput Petugas A diverifikasi Petugas B sebelum resmi (`verified_by`); antrian pending terlihat jelas. Transaksi di atas ambang menunggu hasil poll anggota.

### P4 — Laporan

**P4.1 [MVP] Cashflow harian** — pemasukan, pengeluaran, net hari ini + bar chart 7 hari terakhir.
**P4.2 [Bonus] Laporan mingguan otomatis** — ter-generate tiap Senin pagi.
**P4.3 [Bonus] Export & kirim PDF via WA.**

### P5 — Lintas Fitur

**P5.1 [Bonus] Offline-first + sync**
- Local DB (drift/sqflite) + event log; sync otomatis saat online; idempotent via `client_uuid`. Krusial secara narasi (desa konektivitas buruk); kalau mepet, **disimulasikan** saat demo (toggle airplane mode → antri → sync).

**P5.2 [Bonus] Buat pengumuman dari app** — form ringkas (judul, isi, kategori, lampiran, channel).

## 5. Alur Kunci (Demo Script)

1. **Belanja anggota:** scan QR kartu anggota di POS → bayar harga anggota → HP anggota: kartu "kamu hemat Rp 7.500" + "+15 KopPoin" → grafik SHU bergerak.
2. **Umpan non-anggota:** warga belanja tanpa keanggotaan → terima struk WA "kalau jadi anggota, hari ini kamu hemat Rp 7.500" → CTA daftar.
3. **RAT mini:** pengeluaran Rp 2 jt diinput di web → semua HP anggota bergetar: poll Setuju/Tolak dengan bukti → kuorum tercapai → pengumuman berbukti otomatis.
4. **Beli panen + verifikasi:** petugas di sawah input pembelian → warga tanda tangan di layar HP petugas → transaksi sah masuk buku kas real-time.
5. **Kejujuran saat rugi:** tunjukkan bulan defisit — SHU jujur "belum bisa dihitung", KopPoin tetap jalan.

## 6. Kebutuhan Non-Fungsional

- **Auth**: Sanctum token (login `POST /api/login`); registrasi anggota via `POST /api/register` (KTP upload) atau didaftarkan Sekretaris.
- **Bahasa & nada**: Bahasa Indonesia sederhana, ramah warga desa; angka uang format Rupiah; hindari jargon ("SHU" selalu dengan penjelasan singkat).
- **Aksesibilitas lapangan**: target Android low-end; ukuran font besar; komponen sentuh besar; hemat data (thumbnail bukti, lazy load).
- **Freshness**: polling ≤ 5 detik atau push (FCM) untuk poll & konfirmasi — cukup untuk kesan "real-time" di demo.
- **Privasi**: data finansial pribadi (simpanan, SHU) hanya milik anggota ybs.; leaderboard hanya nama + poin.

## 7. Stack & Dependensi

- **Flutter** (Android), state management bebas tim (Riverpod/Bloc), `fl_chart` untuk grafik SHU & cashflow, `signature` package untuk tanda tangan layar, kamera untuk bukti.
- **Backend**: REST API Laravel yang sama dengan web (lihat PRD_WEB §7); push notification via FCM atau polling.
- **WA**: dikirim server-side (Fonnte) — app tidak memanggil Fonnte langsung.

## 8. Urutan Build yang Disarankan

1. Auth + shell navigasi role-aware (anggota vs petugas)
2. Beranda anggota + buku kas transparansi (A1, A3.3) — butuh API read paling sederhana
3. Poll vote pengeluaran (A4.1) ⭐ — sinkron dengan web F7.3
4. Petugas: input transaksi + tanda tangan layar (P2.1–P2.2) ⭐
5. SHU tracker + tampilan jujur rugi (A2)
6. KopPoin: dashboard + katalog + request tukar (A5)
7. Kartu manfaat + kartu anggota QR (A1.2–A1.3)
8. Aspirasi & polling umum (A4.2)
9. Bonus sesuai sisa waktu (RSVP, leaderboard, offline sync, voice)

## 9. Risiko

| Risiko | Mitigasi |
|---|---|
| Tanda tangan layar gagal saat demo | Latihan device asli; fallback tombol konfirmasi in-app dengan akun anggota kedua |
| Push notification tidak reliable | Polling 5 detik sebagai fallback — visualnya sama di demo |
| Grafik SHU kosong (data kurang) | Seeder `financial_snapshots` 12 bulan termasuk 1–2 bulan defisit |
| Dua persona membengkakkan scope | Petugas MVP hanya P1, P2.1–P2.2, P3.1, P4.1 — sisanya bonus |
