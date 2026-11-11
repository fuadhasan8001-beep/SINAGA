<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\MediaAsset;
use App\Models\SpmbSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class SpmbController extends Controller
{
    public function dashboard(): View
    {
        return view('admin.spmb.dashboard', ['settings' => $this->settings()]);
    }

    public function updateDashboard(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'poster' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'introduction' => ['nullable', 'string', 'max:5000'],
            'contact_whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^(?=.*\d)[0-9+().\-\s]+$/'],
        ], [
            'poster.image' => 'File poster harus berupa gambar.',
            'poster.mimes' => 'Format poster harus JPG, PNG, atau WebP.',
            'poster.max' => 'Ukuran poster maksimal 10 MB. Kompres gambar atau pilih file yang lebih kecil, lalu simpan kembali.',
            'poster.uploaded' => 'Poster gagal diunggah. Batas PHP saat ini lebih kecil dari file yang dipilih atau koneksi terputus. Gunakan poster maksimal 10 MB dan jalankan server lokal dengan perintah bash tools/serve.',
            'introduction.max' => 'Keterangan SPMB maksimal 5.000 karakter.',
            'contact_whatsapp.max' => 'Nomor WhatsApp terlalu panjang.',
            'contact_whatsapp.regex' => 'Gunakan nomor Indonesia seperti 081234567890 atau +62 812-3456-7890.',
        ]);

        $whatsapp = preg_replace('/\D+/', '', (string) ($data['contact_whatsapp'] ?? ''));
        if ($whatsapp !== '') {
            if (str_starts_with($whatsapp, '0')) {
                $whatsapp = '62'.substr($whatsapp, 1);
            } elseif (str_starts_with($whatsapp, '8')) {
                $whatsapp = '62'.$whatsapp;
            }

            if (! preg_match('/^62[0-9]{8,13}$/', $whatsapp)) {
                throw ValidationException::withMessages([
                    'contact_whatsapp' => 'Masukkan nomor WhatsApp Indonesia yang valid, misalnya 081234567890 atau +62 812-3456-7890.',
                ]);
            }
        }
        $data['contact_whatsapp'] = $whatsapp !== '' ? $whatsapp : null;

        $settings = $this->settings();
        $oldPosterPath = $settings->poster_path;
        try {
            $newPosterPath = $request->hasFile('poster')
                ? $request->file('poster')->storePublicly('spmb/posters', 'public')
                : null;
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'poster' => 'Poster tidak dapat disimpan ke penyimpanan website. Periksa kapasitas dan izin folder storage, lalu coba kembali. Data sebelumnya tetap digunakan.',
            ]);
        }

        if ($request->hasFile('poster') && ! is_string($newPosterPath)) {
            throw ValidationException::withMessages(['poster' => 'Poster gagal disimpan. Silakan coba unggah kembali.']);
        }

        try {
            DB::transaction(function () use ($request, $settings, $data, $newPosterPath): void {
                $settings->update([
                    'poster_path' => $newPosterPath ?: $settings->poster_path,
                    'introduction' => filled($data['introduction'] ?? null) ? trim($data['introduction']) : null,
                    'contact_whatsapp' => $data['contact_whatsapp'] ?? null,
                    'published' => true,
                ]);

                if ($newPosterPath) {
                    MediaAsset::create([
                        'uploaded_by' => $request->user()->id,
                        'path' => $newPosterPath,
                        'mime_type' => $request->file('poster')->getMimeType(),
                        'size' => $request->file('poster')->getSize(),
                        'alt_text' => 'Poster SPMB',
                    ]);
                }

                ActivityLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'perbarui informasi SPMB',
                    'detail' => 'Poster, pengumuman, dan Contact Person diperbarui',
                ]);
            });
        } catch (Throwable $exception) {
            if ($newPosterPath) {
                try {
                    Storage::disk('public')->delete($newPosterPath);
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }
            report($exception);

            return back()->withInput()->withErrors([
                'save' => 'Perubahan SPMB gagal disimpan. Data sebelumnya tetap digunakan. Silakan periksa koneksi database dan coba kembali.',
            ]);
        }

        if ($newPosterPath && $oldPosterPath && $oldPosterPath !== $newPosterPath) {
            try {
                Storage::disk('public')->delete($oldPosterPath);
                MediaAsset::where('path', $oldPosterPath)->delete();
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return back()->with('success', 'Informasi SPMB berhasil disimpan dan langsung tampil di halaman pengunjung.');
    }

    private function settings(): SpmbSetting
    {
        DB::table('spmb_settings')->insertOrIgnore([
            'id' => 1,
            'title' => 'Penerimaan Murid Baru',
            'published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $settings = SpmbSetting::query()->findOrFail(1);
        if (! $settings->published) {
            $settings->forceFill(['published' => true])->save();
        }

        return $settings;
    }

}
