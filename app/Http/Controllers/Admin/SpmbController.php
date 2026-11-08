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
use Illuminate\Support\Str;
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
            'contact_whatsapp' => ['nullable', 'regex:/^62[0-9]{8,13}$/'],
        ]);

        $settings = $this->settings();
        $oldPosterPath = $settings->poster_path;
        $newPosterPath = $request->hasFile('poster')
            ? $request->file('poster')->storePublicly('spmb/posters', 'public')
            : null;

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
                    'detail' => 'Poster, keterangan tambahan, dan Contact Person diperbarui',
                ]);
            });
        } catch (Throwable $exception) {
            if ($newPosterPath) Storage::disk('public')->delete($newPosterPath);
            throw $exception;
        }

        if ($newPosterPath && $oldPosterPath && $oldPosterPath !== $newPosterPath) {
            Storage::disk('public')->delete($oldPosterPath);
            MediaAsset::where('path', $oldPosterPath)->delete();
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
