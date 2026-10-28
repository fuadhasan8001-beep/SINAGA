# Website Sekolah SINAGA

Situs publik tetap berupa HTML, CSS, dan JavaScript, serta dapat diterbitkan melalui GitHub Pages. Repository ini kini juga menyertakan panel CMS Admin/Guru berbasis Express dan SQLite. Fitur CMS membutuhkan server Node.js; GitHub Pages sendiri tidak menjalankan backend.

## Menjalankan CMS secara lokal

Persyaratan: Node.js 22.12 atau lebih baru dan npm.

```sh
npm install
cp .env.example .env
```

Isi `ADMIN_EMAIL`, `ADMIN_PASSWORD` (minimal 12 karakter), serta `SESSION_SECRET` acak minimal 32 karakter di `.env`. Kemudian:

```sh
npm start
```

Buka `http://localhost:3000/admin/`. Akun Admin pertama dibuat saat server mulai jika belum ada akun Admin. Buat akun Guru melalui menu **Akun Guru**. Kata sandi awal diberikan saat akun dibuat; sistem menyimpan hash kata sandi dan tidak menyediakan cara untuk membacanya kembali.

Database, sesi, dan unggahan disimpan di `data/` (dapat dipindah dengan `DATA_DIR`). Cadangkan folder data secara berkala. Jangan menghapus database lama saat memperbarui aplikasi.

## Hosting

GitHub Pages dapat tetap menerbitkan folder repository untuk situs publik, tetapi panel Admin dan API tidak akan berfungsi di Pages. Untuk mengaktifkan CMS produksi, jalankan `npm start` pada host Node.js dan gunakan domain HTTPS dengan penyimpanan persisten untuk `DATA_DIR`. Atur variabel `NODE_ENV=production`, `SESSION_SECRET`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD` saat pertama kali menyiapkan database. Di hosting dengan reverse proxy TLS, Express mempercayai satu proxy.

Untuk situs dan CMS pada host yang sama, aplikasi Express melayani `index.html`, `/admin/`, `/api/`, `/uploads/`, dan halaman artikel `/artikel/:slug`; daftar berita di halaman depan akan mengambil artikel yang telah terbit. Jika situs publik tetap di GitHub Pages, CMS beroperasi pada host terpisah. Isi `content` pada meta `sinaga-api-base` di `index.html` dengan origin backend (contoh `https://cms.sekolah.id`) dan set `PUBLIC_ORIGIN` pada backend ke origin Pages yang persis (contoh `https://sekolah.github.io`) agar daftar berita bisa dimuat lintas domain. Tautan artikel akan mengarah ke backend CMS.

## Fitur CMS

- Login Admin/Guru dengan sesi HTTP-only, batas percobaan login, hash PBKDF2, token CSRF, dan otorisasi backend.
- Dashboard dan statistik, profil, manajemen artikel, kategori, akun Guru, riwayat aktivitas, persetujuan, catatan revisi, penolakan, dan publikasi.
- Guru hanya dapat membaca/mengubah artikelnya sendiri; pengiriman Guru selalu masuk status menunggu persetujuan.
- Editor rich text untuk judul, ringkasan, kategori, sampul, heading, daftar, tautan, kutipan, undo/redo, pratinjau, dan gambar inline dengan teks alternatif serta keterangan.
- Impor `.docx` mengonversi paragraf, heading, daftar, tautan, dan gambar tertanam ke editor. Hasil konversi disanitasi dan dapat diperiksa sebelum disimpan.
- Unggahan gambar dibatasi 5 MB, dokumen Word 10 MB. Gambar diperiksa berdasarkan signature file, nama file dibuat acak, dan file disimpan di disk, bukan di database.

## Verifikasi

Jalankan `npm test` untuk uji integrasi autentikasi, otorisasi peran dan kepemilikan artikel, alur persetujuan, unggahan tidak valid, impor Word, serta endpoint artikel publik. Jalankan `npm audit` untuk laporan advisori dependensi.

Workflow GitHub Pages yang sudah ada tetap menerbitkan situs statis dengan alur yang sama.
