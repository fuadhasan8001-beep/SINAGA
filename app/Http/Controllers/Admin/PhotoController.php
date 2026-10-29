<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ContentEntry;
use App\Models\MediaAsset;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PhotoController extends Controller
{
    private const PLACEMENTS = [
        'hero' => [
            'setting' => 'hero_photo_path',
            'type' => 'gallery',
            'title' => 'Edit Foto Lingkungan Sekolah',
            'label' => 'Foto lingkungan sekolah',
        ],
        'profile' => [
            'setting' => 'profile_photo_path',
            'type' => 'teacher',
            'title' => 'Edit Foto Profil Sekolah',
            'label' => 'Foto profil sekolah',
        ],
    ];

    public function edit(string $placement): View
    {
        $details = $this->placement($placement);

        return view('admin.content.photo', [
            'placement' => $placement,
            'title' => $details['title'],
            'label' => $details['label'],
            'imagePath' => $this->currentPath($placement, $details),
        ]);
    }

    public function update(Request $request, string $placement): RedirectResponse
    {
        $details = $this->placement($placement);
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'image.required' => 'Pilih foto yang akan disimpan.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Foto harus berformat JPEG, PNG, atau WebP.',
            'image.max' => 'Ukuran foto maksimal 5 MB.',
        ]);

        $file = $request->file('image');
        $newPath = $file->storePublicly('school-photos', 'public');

        if (! $newPath) {
            throw ValidationException::withMessages(['image' => 'Foto gagal disimpan. Silakan coba lagi.']);
        }

        $settingKey = $details['setting'];
        $oldManagedPath = $this->savedPath($settingKey);

        try {
            DB::transaction(function () use ($request, $file, $newPath, $settingKey, $details): void {
                SiteSetting::updateOrCreate(
                    ['key' => $settingKey],
                    ['value' => json_encode($newPath, JSON_UNESCAPED_SLASHES)],
                );

                MediaAsset::create([
                    'uploaded_by' => $request->user()->id,
                    'path' => $newPath,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'alt_text' => $details['label'],
                    'caption' => $details['label'],
                ]);

                ActivityLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'perbarui foto '.$details['label'],
                    'detail' => $file->getClientOriginalName(),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newPath);
            throw $exception;
        }

        if ($oldManagedPath && $oldManagedPath !== $newPath) {
            Storage::disk('public')->delete($oldManagedPath);
            MediaAsset::where('path', $oldManagedPath)->delete();
        }

        return redirect()->route('admin.dashboard')->with('success', $details['label'].' berhasil diperbarui.');
    }

    private function placement(string $placement): array
    {
        abort_unless(isset(self::PLACEMENTS[$placement]), 404);

        return self::PLACEMENTS[$placement];
    }

    private function currentPath(string $placement, array $details): ?string
    {
        $savedPath = $this->savedPath($details['setting']);

        if ($savedPath) {
            return $savedPath;
        }

        $entry = ContentEntry::visible()
            ->where('type', $details['type'])
            ->orderBy('sort_order')
            ->orderBy('starts_at')
            ->first();

        if ($placement === 'hero' && ! filled($entry?->image_path)) {
            $entry = ContentEntry::visible()
                ->where('type', 'gallery')
                ->whereNotNull('image_path')
                ->where('image_path', '!=', '')
                ->orderBy('sort_order')
                ->orderBy('starts_at')
                ->first();
        }

        return $entry?->image_path;
    }

    private function savedPath(string $key): ?string
    {
        $rawValue = SiteSetting::whereKey($key)->value('value');

        if (! $rawValue) {
            return null;
        }

        $value = json_decode($rawValue, true);

        return is_string($value) && filled($value) ? $value : null;
    }
}
