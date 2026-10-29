<?php

namespace Tests\Feature;

use App\Models\SpmbAnnouncement;
use App\Models\SpmbContact;
use App\Models\SpmbSetting;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpmbWorkflowTest extends TestCase
{
    public function test_seeded_admin_username_login_hash_logout_and_cache_controls(): void
    {
        config([
            'sinaga.admin.name'=>'Administrator SINAGA',
            'sinaga.admin.username'=>'admin.sinaga',
            'sinaga.admin.email'=>'admin@sinaga.test',
            'sinaga.admin.password'=>'sinagajaya',
        ]);
        $this->seed(AdminUserSeeder::class);
        $admin=User::where('username','admin.sinaga')->firstOrFail();
        $this->assertTrue(Hash::check('sinagajaya',$admin->password));
        $this->assertNotSame('sinagajaya',$admin->password);
        $this->get(route('login'))->assertOk()->assertDontSee('admin.sinaga')->assertDontSee('sinagajaya');
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->post(route('login.store'),['username'=>'admin.sinaga','password'=>'salah'])->assertSessionHasErrors('username');
        $this->post(route('login.store'),['username'=>'admin.sinaga','password'=>'sinagajaya'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        $adminResponse=$this->get(route('admin.spmb.dashboard'))->assertOk();
        $this->assertStringContainsString('no-store',$adminResponse->headers->get('Cache-Control'));
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->get(route('admin.spmb.dashboard'))->assertRedirect(route('login'));
    }

    public function test_spmb_crud_upload_publication_and_public_page_are_separate(): void
    {
        $this->get(route('spmb.public'))->assertOk()->assertSee('belum diterbitkan');
        $this->get(route('admin.spmb.dashboard'))->assertRedirect(route('login'));
        $teacher=User::factory()->create(['role'=>'teacher','active'=>true]);
        $this->actingAs($teacher)->get(route('admin.spmb.dashboard'))->assertForbidden();
        $this->put(route('admin.spmb.information.update'),['title'=>'Tidak boleh'])->assertForbidden();

        $admin=User::factory()->create(['role'=>'admin','active'=>true]);
        $this->actingAs($admin);
        $this->get(route('admin.spmb.dashboard'))->assertOk()->assertViewIs('admin.spmb.dashboard');
        $this->put(route('admin.spmb.information.update'),[
            'title'=>'Penerimaan Murid Baru SINAGA',
            'school_year'=>'2027/2028',
            'introduction'=>'Informasi resmi penerimaan murid baru.',
            'quota'=>'120',
            'fee_information'=>'',
            'registration_url'=>'https://example.test/daftar',
            'published'=>'1',
        ])->assertRedirect();
        $this->put(route('admin.spmb.schedule.update'),['schedule_text'=>"2 Mei 2027 | Pendaftaran | Isi formulir resmi\n10 Mei 2027 | Pengumuman | Cek hasil"])->assertRedirect();
        $this->put(route('admin.spmb.requirements.update'),[
            'requirements_text'=>"Syarat pertama\nSyarat kedua",
            'steps_text'=>"Isi formulir\nTunggu pengumuman",
            'documents_text'=>"Kartu keluarga\nAkta kelahiran",
        ])->assertRedirect();
        $settings=SpmbSetting::firstOrFail();
        $this->assertSame('Syarat pertama',$settings->requirements[0]);
        $this->assertSame('Pendaftaran',$settings->schedule[0]['stage']);

        Storage::fake('public');
        $this->post(route('admin.spmb.poster.update'),['poster'=>UploadedFile::fake()->image('poster.jpg',800,1100)])->assertRedirect();
        $settings->refresh();$oldPoster=$settings->poster_path;
        $this->assertTrue(Storage::disk('public')->exists($oldPoster));
        $this->from(route('admin.spmb.poster.edit'))->post(route('admin.spmb.poster.update'),['poster'=>UploadedFile::fake()->create('poster.svg',10,'image/svg+xml')])->assertSessionHasErrors('poster');
        $this->assertTrue(Storage::disk('public')->exists($oldPoster));
        $this->post(route('admin.spmb.poster.update'),['poster'=>UploadedFile::fake()->image('poster-baru.png',900,1200)])->assertRedirect();
        $settings->refresh();$newPoster=$settings->poster_path;
        $this->assertNotSame($oldPoster,$newPoster);
        $this->assertFalse(Storage::disk('public')->exists($oldPoster));
        $this->assertTrue(Storage::disk('public')->exists($newPoster));

        $this->post(route('admin.spmb.contacts.store'),[
            'name'=>'Panitia Penerimaan',
            'role'=>'Panitia SPMB',
            'phone'=>'081234567890',
            'office_hours'=>'Senin sampai Jumat',
            'published'=>'1',
        ])->assertRedirect(route('admin.spmb.contacts.index'));
        $contact=SpmbContact::firstOrFail();
        $this->put(route('admin.spmb.contacts.update',$contact),['name'=>'Panitia SPMB Resmi','role'=>'Panitia Penerimaan','phone'=>'+6281234567890','office_hours'=>'Senin sampai Jumat','published'=>'1'])->assertRedirect(route('admin.spmb.contacts.index'));
        $this->post(route('admin.spmb.announcements.store'),['title'=>'Draf internal','body'=>'RAHASIA DRAF'])->assertRedirect(route('admin.spmb.announcements.index'));
        $this->post(route('admin.spmb.announcements.store'),['title'=>'Pengumuman SPMB','body'=>'Pengumuman resmi','published'=>'1'])->assertRedirect(route('admin.spmb.announcements.index'));
        $announcement=SpmbAnnouncement::where('title','Pengumuman SPMB')->firstOrFail();
        $this->put(route('admin.spmb.announcements.update',$announcement),['title'=>'Pengumuman SPMB terbaru','body'=>'Informasi resmi terbaru','published'=>'1'])->assertRedirect(route('admin.spmb.announcements.index'));

        $this->get(route('spmb.public'))->assertOk()
            ->assertSee('Penerimaan Murid Baru SINAGA')
            ->assertSee('2027/2028')
            ->assertSee('Syarat pertama')
            ->assertSee('2 Mei 2027')
            ->assertSee('Informasi resmi terbaru')
            ->assertSee('Panitia SPMB Resmi')
            ->assertSee('https://wa.me/6281234567890')
            ->assertSee(Storage::disk('public')->url($newPoster))
            ->assertDontSee('RAHASIA DRAF');
        $this->get(route('home'))->assertOk()->assertDontSee('RAHASIA DRAF')->assertDontSee('Pengumuman resmi');

        $announcement=SpmbAnnouncement::where('published',true)->firstOrFail();
        $this->delete(route('admin.spmb.contacts.destroy',$contact))->assertRedirect();
        $this->delete(route('admin.spmb.announcements.destroy',$announcement))->assertRedirect();
        $this->assertDatabaseMissing('spmb_contacts',['id'=>$contact->id]);
        $this->assertDatabaseMissing('spmb_announcements',['id'=>$announcement->id]);
    }
}