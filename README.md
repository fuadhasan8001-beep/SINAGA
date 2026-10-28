# Website Sekolah (HTML)

Website statis satu halaman yang dibuat dengan HTML, CSS, dan JavaScript. Tidak memerlukan build step, framework, database, atau server PHP. Folder ini siap dipublikasikan melalui GitHub Pages.

## Publikasi melalui GitHub Pages

Workflow `.github/workflows/pages.yml` menerbitkan isi repository setiap kali ada push ke branch `main`.

1. Push file ke repository GitHub pada branch `main`.
2. Buka **Settings → Pages** pada repository.
3. Pada **Build and deployment**, pilih **GitHub Actions** sebagai source.
4. Buka tab **Actions** dan tunggu workflow **Publish website to GitHub Pages** selesai.
5. URL bawaan akan terlihat pada **Settings → Pages** dan di hasil workflow.

## Domain khusus

Nama domain belum tersedia, jadi belum ada file `CNAME` atau pengaturan DNS spesifik. Setelah memiliki domain:

1. Masukkan domain pada **Settings → Pages → Custom domain**.
2. Pada DNS provider domain, arahkan apex domain (`contoh.id`) ke alamat IP GitHub Pages yang tercantum di dokumentasi GitHub, atau buat record `CNAME` untuk subdomain seperti `www.contoh.id` ke `fuadhasan8001-beep.github.io`.
3. Tunggu propagasi DNS dan verifikasi domain di Settings → Pages. Aktifkan **Enforce HTTPS** setelah sertifikat tersedia.

GitHub Pages hanya melayani file statis. Tidak ada panel admin atau penyimpanan formulir/database pada versi ini.

## Sebelum dipublikasikan sebagai situs resmi

Edit `index.html` untuk mengisi nama dan logo sekolah, profil, alamat, nomor telepon, email, jadwal kegiatan, berita, serta informasi layanan menggunakan data resmi. Area foto sengaja berupa placeholder sampai tersedia dokumentasi sekolah yang boleh dipublikasikan. Formulir kontak belum terhubung ke layanan pengiriman atau penyimpanan pesan.
