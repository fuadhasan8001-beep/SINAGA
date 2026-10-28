# SINAGA — Website Sekolah dengan Laravel

Website sekolah kini berjalan di Laravel 13 dan memakai Blade untuk tampilan publik dan panel pengelola. Struktur HTML, palet hijau, CSS, tipografi sistem, breakpoint responsif, dan interaksi menu dari situs sebelumnya dipertahankan. HTML lama disimpan sebagai referensi di `resources/legacy/website-original.html`; tidak ada data resmi sekolah yang ditambahkan.

Laravel 13 memerlukan PHP 8.3 atau lebih baru. Ekstensi PHP yang dipakai di lingkungan lokal: `pdo_sqlite`, `sqlite3`, `fileinfo`, `gd`, `mbstring`, `xml`, dan `zip`.

## Instalasi lokal

```sh
composer install
npm ci
cp .env.example .env
```

Atur `APP_NAME`, `APP_URL`, `DB_CONNECTION=sqlite`, serta `ADMIN_NAME`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD` di `.env`. Kata sandi Admin awal harus unik dan sedikitnya 12 karakter; gunakan pembuat kata sandi acak. Kredensial awal tidak disimpan di repository.

Siapkan aplikasi dan database:

```sh
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000` untuk website dan `/login` untuk panel. Seeder membuat akun Admin dari variabel `.env` hanya jika email tersebut belum terdaftar. Menjalankan ulang seeder tidak mengganti kata sandi akun yang ada. Buat akun Guru dari menu **Akun Guru**.

SQLite berada di `database/database.sqlite`; unggahan berada di `storage/app/public`. Backup database dan direktori storage sebelum deployment atau perubahan skema. Migrasi tambahan mempertahankan tabel pengguna Laravel yang sudah ada.

## Fitur

- Beranda satu halaman mempertahankan desain lama. Rute `/profil`, `/akademik`, `/berita`, `/pengumuman`, `/ekstrakurikuler`, `/ppdb`, `/galeri`, dan `/kontak` membuka bagian yang sesuai. Artikel terbit memiliki halaman `/berita/{slug}`.
- Halaman publik membaca artikel, pengumuman, agenda, profil guru, ekstrakurikuler, prestasi, galeri, dokumen, kontak, dan pengaturan dari database. Jika belum ada data sekolah, halaman tetap menampilkan placeholder.
- Login memakai autentikasi dan sesi Laravel, regenerasi sesi, rate limiting, CSRF, validasi server, serta middleware peran Admin/Guru. Registrasi publik dimatikan.
- Admin mengelola artikel semua penulis, kategori, pengumuman, agenda, guru, ekstrakurikuler, prestasi, galeri, dokumen, pesan kontak, dan pengaturan website. Admin dapat menerbitkan, membatalkan publikasi, meminta revisi, atau menolak artikel.
- Guru membuat draf dan mengirim artikel untuk persetujuan. Guru hanya dapat membaca atau mengubah artikel miliknya sendiri; artikel pending dan terbit tidak dapat diedit oleh Guru.
- Editor mendukung heading, bold/italic/underline, daftar, tautan, kutipan, undo/redo, pratinjau, gambar inline, foto sampul, teks alternatif, dan keterangan.
- Impor Word `.docx` mempertahankan teks, heading, daftar, tautan, dan gambar tertanam sesuai kemampuan konversi PHPWord. Gambar diekstrak ke Laravel Storage, bukan disimpan sebagai Base64 di database.
- HTML artikel disanitasi dengan HTML Purifier. Unggahan gambar divalidasi tipe dan ukuran; dokumen dibatasi ke format kantor yang didukung.

## Database dan data awal

Skema dibuat oleh Laravel migrations: pengguna/peran, kategori, artikel, konten terkelola, metadata media, pengaturan, aktivitas, pesan kontak, sesi, dan password reset. Relasi artikel mengarah ke penulis dan kategori; foreign key menggunakan cascade atau set-null yang sesuai.

Seeder hanya menambahkan kategori dasar dan akun Admin dari `.env`; tidak menambahkan berita, agenda, guru, atau identitas sekolah fiktif.

## Pengujian dan aset

```sh
php artisan test
composer audit
npm audit
```

CSS publik yang dipindahkan ke `resources/css/site.css` berasal dari desain sebelumnya. CSS panel berada di `resources/css/admin.css`; Vite membangun keduanya bersama JavaScript tanpa mengganti desain dengan Tailwind.

## Deployment

GitHub Pages hanya melayani file statis dan tidak menjalankan Laravel/PHP. Workflow penerbitan Pages lama sudah diganti dengan workflow CI Laravel. Untuk menjalankan situs, gunakan host PHP yang document root-nya menunjuk ke direktori `public/`, siapkan PHP 8.3+, Composer, Node.js untuk build aset, database SQLite/MySQL, dan storage persisten. Jangan mengekspos root repository atau file `.env` ke web.

Pada production:

1. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` HTTPS, kredensial database, dan `SESSION_SECURE_COOKIE=true`.
2. Jalankan `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, `php artisan migrate --force`, dan `php artisan storage:link`.
3. Seed Admin pertama dari variabel rahasia `ADMIN_NAME`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD`, lalu hapus variabel kata sandi dari lingkungan deployment jika kebijakan host mengharuskan.
4. Pastikan `storage/` dan `bootstrap/cache/` dapat ditulis oleh PHP dan memiliki backup terjadwal.

File `CNAME` lama dipertahankan sebagai catatan domain. Arahkan DNS/domain ke host Laravel yang digunakan; file itu sendiri tidak mengaktifkan backend pada GitHub Pages.
