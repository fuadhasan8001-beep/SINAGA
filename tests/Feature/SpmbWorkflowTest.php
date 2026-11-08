<?php

namespace Tests\Feature;

use App\Models\SpmbAnnouncement;
use App\Models\SpmbContact;
use App\Models\SpmbSetting;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpmbWorkflowTest extends TestCase
{
    public function test_spmb_routes_require_an_active_admin_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $teacher = User::factory()->create(['role' => 'teacher', 'active' => true]);

        $this->get(route('admin.spmb.dashboard'))->assertRedirect(route('login'));
        $this->put(route('admin.spmb.information.update'), ['title' => 'Pendaftaran Sekolah', 'status' => 'open'])->assertRedirect(route('login'));
        $this->actingAs($teacher)->get(route('admin.spmb.dashboard'))->assertForbidden();
        $this->put(route('admin.spmb.information.update'), ['title' => 'Pendaftaran Sekolah', 'status' => 'open'])->assertForbidden();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee(route('admin.spmb.dashboard'), false);
        foreach ([
            'admin.spmb.dashboard', 'admin.spmb.information.edit', 'admin.spmb.schedule.edit',
            'admin.spmb.requirements.edit', 'admin.spmb.poster.edit', 'admin.spmb.contacts.index',
            'admin.spmb.announcements.index', 'admin.spmb.photos.index', 'admin.spmb.links.index',
        ] as $routeName) {
            $response = $this->get(route($routeName))->assertOk()->assertSee('id="admin-sidebar"', false);
            if ($routeName !== 'admin.spmb.dashboard') {
                $response->assertSee('Kembali ke Dashboard SPMB');
            }
        }
        $this->get(route('admin.spmb.information.edit'))->assertSee(route('admin.dashboard'), false)->assertSee('Kembali ke Website');
        $this->get(route('admin.spmb.dashboard'))->assertSee(route('admin.dashboard'), false)->assertSee('Kembali ke Website');

        $dashboard = $this->get(route('admin.spmb.dashboard'))->assertOk()
            ->assertSee('Dashboard SPMB')->assertSee('Kelola informasi penerimaan murid baru website sekolah.')
            ->assertSee('Poster SPMB')->assertSee('Kelola poster informasi penerimaan murid baru.')
            ->assertSee('Pengumuman SPMB')->assertSee('Tambah, edit, dan kelola pengumuman SPMB.')
            ->assertSee('Tautan Penting')->assertSee('Kelola link pendaftaran, panduan, dan informasi penting lainnya.')
            ->assertSee('Contact Person')->assertSee('Kelola informasi kontak dan WhatsApp petugas SPMB.')
            ->assertDontSee('Status informasi')->assertDontSee('Jadwal tersimpan')
            ->assertDontSee('Pengumuman terbaru')->assertDontSee('Dokumentasi foto');
        foreach ([
            'admin.spmb.poster.edit', 'admin.spmb.announcements.index',
            'admin.spmb.links.index', 'admin.spmb.contacts.index',
        ] as $routeName) {
            $dashboard->assertSee(route($routeName), false);
        }
    }

    public function test_admin_can_manage_spmb_and_only_published_content_is_public(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $this->actingAs($admin);

        $this->put(route('admin.spmb.information.update'), [
            'title' => 'Penerimaan Murid Baru', 'school_year' => '2027/2028',
            'introduction' => 'Pendaftaran resmi telah dibuka.', 'status' => 'open',
            'quota' => 120, 'fee_information' => 'Tidak dipungut biaya.',
            'registration_url' => 'https://example.test/daftar', 'published' => '1',
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('spmb_settings', ['title' => 'Penerimaan Murid Baru', 'status' => 'open', 'published' => 1]);

        $this->put(route('admin.spmb.schedule.update'), ['schedule_text' => "1 Mei 2027 | Pendaftaran | Isi formulir\n10 Juni 2027 | Pengumuman"])->assertRedirect();
        $this->put(route('admin.spmb.requirements.update'), [
            'requirements_text' => "Kartu keluarga\nAkta kelahiran", 'steps_text' => "Isi formulir\nVerifikasi berkas", 'documents_text' => 'Rapor terakhir',
        ])->assertRedirect();

        Storage::fake('public');
        $this->post(route('admin.spmb.poster.update'), ['poster' => UploadedFile::fake()->image('spmb.jpg', 600, 800)])->assertRedirect();
        $oldPosterPath = SpmbSetting::firstOrFail()->poster_path;
        $this->post(route('admin.spmb.poster.update'), [
            'poster' => UploadedFile::fake()->image('spmb-baru.png', 720, 1000),
            'poster_caption' => 'Poster resmi tahun ajaran 2027/2028.',
        ])->assertRedirect();
        $posterPath = SpmbSetting::firstOrFail()->poster_path;
        $this->assertNotSame($oldPosterPath, $posterPath);
        Storage::disk('public')->assertMissing($oldPosterPath);
        $this->assertTrue(Storage::disk('public')->exists($posterPath));
        $this->assertDatabaseMissing('media_assets', ['path' => $oldPosterPath]);

        $this->post(route('admin.spmb.poster.update'), [
            'poster' => UploadedFile::fake()->create('bukan-gambar.txt', 20, 'text/plain'),
        ])->assertSessionHasErrors('poster');
        $this->assertSame($posterPath, SpmbSetting::firstOrFail()->poster_path);
        Storage::disk('public')->assertExists($posterPath);

        $this->post(route('admin.spmb.photos.store'), [
            'title' => 'Pengenalan sekolah', 'caption' => 'Dokumentasi kegiatan pengenalan.', 'image' => UploadedFile::fake()->image('dokumentasi.jpg', 800, 600), 'published' => '1',
        ])->assertRedirect(route('admin.spmb.photos.index'));
        $photo = \App\Models\SpmbPhoto::firstOrFail();
        $this->put(route('admin.spmb.photos.update', $photo), [
            'title' => 'Kegiatan SPMB', 'caption' => 'Foto kegiatan resmi.', 'published' => '1',
        ])->assertRedirect(route('admin.spmb.photos.index'));

        $this->post(route('admin.spmb.contacts.store'), [
            'name' => 'Panitia SPMB', 'role' => 'Admin pendaftaran', 'phone' => '081234567890', 'published' => '1',
        ])->assertRedirect(route('admin.spmb.contacts.index'));
        $contact = SpmbContact::firstOrFail();
        $this->put(route('admin.spmb.contacts.update', $contact), [
            'name' => 'Panitia SPMB Utama', 'phone' => '081234567890', 'published' => '1',
        ])->assertRedirect(route('admin.spmb.contacts.index'));

        $this->post(route('admin.spmb.links.store'), [
            'title' => 'Panduan pendaftaran', 'url' => 'https://example.test/panduan',
            'description' => 'Petunjuk lengkap untuk calon murid.', 'category' => 'guide', 'published' => '1', 'sort_order' => 2,
        ])->assertRedirect()->assertSessionHas('success');
        $link = SpmbSetting::firstOrFail()->important_links[0];
        $this->post(route('admin.spmb.links.store'), [
            'title' => 'Hasil seleksi', 'url' => 'https://example.test/hasil', 'category' => 'results', 'published' => '0',
        ])->assertRedirect();
        $draftLink = SpmbSetting::firstOrFail()->important_links[1];
        $this->put(route('admin.spmb.links.update', $link['id']), [
            'title' => 'Panduan SPMB resmi', 'url' => 'https://example.test/panduan-resmi',
            'description' => 'Panduan terbaru.', 'category' => 'guide', 'published' => '1', 'sort_order' => 1,
        ])->assertRedirect()->assertSessionHas('success');
        $this->put(route('admin.spmb.links.update', $draftLink['id']), [
            'title' => 'Tautan tidak aman', 'url' => 'javascript:alert(1)', 'category' => 'results', 'published' => '1',
        ])->assertSessionHasErrors('url');

        $this->post(route('admin.spmb.announcements.store'), [
            'title' => 'Pendaftaran dibuka', 'body' => 'Silakan mendaftar melalui tautan resmi.', 'starts_at' => '2027-05-01 08:00', 'published' => '1',
        ])->assertRedirect(route('admin.spmb.announcements.index'));
        $announcement = SpmbAnnouncement::firstOrFail();
        $this->put(route('admin.spmb.announcements.update', $announcement), [
            'title' => 'Jadwal pendaftaran', 'body' => 'Pendaftaran telah dibuka.', 'published' => '1',
        ])->assertRedirect(route('admin.spmb.announcements.index'));

        $this->get(route('spmb.public'))->assertOk()->assertSee('Penerimaan Murid Baru')->assertSee('2027/2028')
            ->assertSee('Kartu keluarga')->assertSee('Panitia SPMB Utama')->assertSee('Jadwal pendaftaran')
            ->assertSee('Kegiatan SPMB')->assertSee('Foto kegiatan resmi.')
            ->assertSee(Storage::disk('public')->url($posterPath), false)->assertSee('Poster resmi tahun ajaran 2027/2028.')
            ->assertSee('https://example.test/daftar')->assertSee('https://example.test/panduan-resmi')
            ->assertSee('noopener noreferrer')->assertDontSee('https://example.test/hasil')
            ->assertSee('https://wa.me/6281234567890');
        $this->get(route('ppdb'))->assertOk()->assertSee('Penerimaan Murid Baru');

        $this->put(route('admin.spmb.information.update'), [
            'title' => 'Draf SPMB', 'school_year' => '2028/2029', 'status' => 'preparation',
        ])->assertRedirect();
        $this->get(route('spmb.public'))->assertOk()->assertDontSee('Draf SPMB')->assertDontSee('Pendaftaran dibuka');

        $this->delete(route('admin.spmb.contacts.destroy', $contact))->assertRedirect();
        $this->delete(route('admin.spmb.announcements.destroy', $announcement))->assertRedirect();
        $this->delete(route('admin.spmb.links.destroy', $link['id']))->assertRedirect();
        $this->delete(route('admin.spmb.links.destroy', $draftLink['id']))->assertRedirect();
        $this->delete(route('admin.spmb.poster.destroy'))->assertRedirect();
        $this->delete(route('admin.spmb.photos.destroy', $photo))->assertRedirect();
        Storage::disk('public')->assertMissing($posterPath);
        Storage::disk('public')->assertMissing($photo->image_path);
        $this->assertDatabaseMissing('spmb_contacts', ['id' => $contact->id]);
        $this->assertDatabaseMissing('spmb_announcements', ['id' => $announcement->id]);
        $this->assertSame([], SpmbSetting::firstOrFail()->important_links);
    }

    public function test_public_spmb_page_has_no_admin_controls(): void
    {
        SpmbSetting::create(['title' => 'Informasi SPMB', 'published' => true, 'status' => 'closed']);
        $this->get(route('spmb.public'))->assertOk()->assertSee('Informasi SPMB')
            ->assertDontSee('admin-sidebar')->assertDontSee('Tambah')->assertDontSee('Hapus')->assertDontSee('Kelola SPMB')
            ->assertDontSee('Jadwal pendaftaran')->assertDontSee('Persyaratan')->assertDontSee('Tautan penting')
            ->assertDontSee('Contact person')->assertDontSee('Dokumentasi');
    }
}
