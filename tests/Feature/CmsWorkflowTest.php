<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\ContentEntry;
use App\Models\MediaAsset;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CmsWorkflowTest extends TestCase
{
    public function test_laravel_auth_and_role_access_are_enforced(): void
    {
        $admin=User::factory()->create(['role'=>'admin','active'=>true]);
        $teacher=User::factory()->create(['role'=>'teacher','active'=>true]);
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->post(route('login.store'),['email'=>$admin->email,'password'=>'password'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('admin-preview')->assertSee('site-admin-toggle')
            ->assertDontSee('topline')->assertDontSee('Selamat datang di website sekolah')->assertDontSee('Hubungi kami');
        $this->get(route('admin.users.index'))->assertOk();
        $this->post(route('admin.users.store'),['name'=>'Guru Baru','email'=>'baru@example.test','password'=>'Initial-Teacher-123'])->assertRedirect();
        $this->assertDatabaseHas('users',['email'=>'baru@example.test','role'=>'teacher','active'=>1]);
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $article=Article::create(['author_id'=>$teacher->id,'title'=>'Draf Guru Uji','slug'=>'draf-guru-uji','body'=>'<p>Isi.</p>','status'=>'draft']);
        $this->actingAs($teacher)->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.articles.edit',$article))->assertOk();
        $this->get(route('admin.articles.reviews'))->assertForbidden();
        $inactive=User::factory()->create(['role'=>'teacher','active'=>false]);
        $this->actingAs($inactive)->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_teacher_ownership_and_admin_approval_workflow(): void
    {
        $admin=User::factory()->create(['role'=>'admin','active'=>true]);
        $teacher=User::factory()->create(['role'=>'teacher','active'=>true]);
        $other=User::factory()->create(['role'=>'teacher','active'=>true]);
        Category::create(['name'=>'Berita Uji','slug'=>'berita-uji']);
        $this->actingAs($teacher);
        $response=$this->post(route('admin.articles.store'),['title'=>'Artikel Guru','excerpt'=>'Ringkasan','category_id'=>1,'body'=>'<h2>Aman</h2><script>alert(1)</script>','intent'=>'review']);
        $article=Article::where('title','Artikel Guru')->firstOrFail();
        $response->assertRedirect(route('admin.articles.edit',$article));
        $this->assertSame('pending',$article->status);
        $this->assertStringNotContainsString('<script>',$article->body);
        $foreign=Article::create(['author_id'=>$other->id,'title'=>'Punya Guru Lain','slug'=>'guru-lain','body'=>'<p>Pribadi.</p>','status'=>'draft']);
        $this->put(route('admin.articles.update',$foreign),['title'=>'Diubah','body'=>'<p>x</p>'])->assertForbidden();
        $this->put(route('admin.articles.update',$article),['title'=>'Tidak boleh','body'=>'<p>x</p>'])->assertForbidden();

        $this->actingAs($admin)->post(route('admin.articles.review',$article),['action'=>'revision','note'=>'Tambahkan sumber.'])->assertRedirect();
        $article->refresh();$this->assertSame('revision',$article->status);$this->assertSame('Tambahkan sumber.',$article->revision_note);
        $this->actingAs($teacher)->put(route('admin.articles.update',$article),['title'=>'Artikel Diperbaiki','body'=>'<h2>Diperbaiki</h2><p>Isi lengkap.</p>','intent'=>'review'])->assertRedirect();
        $article->refresh();$this->assertSame('pending',$article->status);
        $this->actingAs($admin)->post(route('admin.articles.review',$article),['action'=>'publish'])->assertRedirect();
        $article->refresh();$this->assertSame('published',$article->status);
        $this->get(route('article.show',$article->slug))->assertOk()->assertSee('Diperbaiki');
        $this->post(route('admin.articles.unpublish',$article))->assertRedirect();
        $article->refresh();$this->assertSame('draft',$article->status);
        $this->assertGreaterThanOrEqual(4,ActivityLog::count());
    }

    public function test_content_settings_contacts_and_media_are_database_backed(): void
    {
        $admin=User::factory()->create(['role'=>'admin','active'=>true]);
        $this->actingAs($admin);
        $this->post(route('admin.content.store','announcement'),['title'=>'Pengumuman ujian','excerpt'=>'Jadwal akan diumumkan.','starts_at'=>'2026-11-10 08:00','published'=>'1'])->assertRedirect();
        $this->assertDatabaseHas('content_entries',['type'=>'announcement','title'=>'Pengumuman ujian','published'=>1]);
        $this->put(route('admin.settings.update'),['settings'=>['school_name'=>'Sekolah Uji','address'=>'Alamat Uji','email'=>'info@uji.test']])->assertRedirect(route('admin.dashboard'))->assertSessionHas('success');
        $this->assertSame('"Sekolah Uji"',SiteSetting::find('school_name')->value);
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Sekolah Uji');
        $this->post(route('contact.store'),['name'=>'Pengirim Uji','email'=>'pengirim@example.test','subject'=>'Tanya','message'=>'Mohon informasi.'])->assertRedirect();
        $this->assertDatabaseHas('contact_messages',['subject'=>'Tanya']);
        $this->get(route('home'))->assertOk()->assertSee('Pengumuman ujian')->assertSee('Sekolah Uji')
            ->assertDontSee('topline')->assertDontSee('Selamat datang di website sekolah')->assertDontSee('Hubungi kami');

        Storage::fake('public');
        $this->post(route('admin.content.store','gallery'),['title'=>'Foto kegiatan','excerpt'=>'Dokumentasi sekolah.','published'=>'1','image'=>UploadedFile::fake()->image('foto.jpg',600,400)])->assertRedirect();
        $gallery=ContentEntry::where('type','gallery')->firstOrFail();
        $this->assertTrue(Storage::disk('public')->exists($gallery->image_path));
        $this->assertDatabaseHas('media_assets',['path'=>$gallery->image_path]);
        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('home'))->assertOk()->assertDontSee('photo-admin-action')->assertDontSee('site-admin-toggle');
        $oldImage=$gallery->image_path;
        $this->put(route('admin.content.update',['gallery',$gallery]),['title'=>'Foto kegiatan','excerpt'=>'Dokumentasi sekolah diperbarui.','published'=>'1','image'=>UploadedFile::fake()->image('foto-baru.jpg',800,600)])->assertRedirect(route('admin.dashboard'))->assertSessionHas('success');
        $gallery->refresh();
        $this->assertNotSame($oldImage,$gallery->image_path);
        $this->assertFalse(Storage::disk('public')->exists($oldImage));
        $this->assertTrue(Storage::disk('public')->exists($gallery->image_path));
        $this->get(route('gallery'))->assertOk()->assertSee(Storage::disk('public')->url($gallery->image_path),false);
        $this->post(route('admin.content.store','gallery'),['title'=>'File tidak sah','published'=>'1','image'=>UploadedFile::fake()->create('fake.svg',10,'image/svg+xml')])->assertSessionHasErrors('image');
        auth()->logout();
        $this->get(route('home'))->assertOk()->assertDontSee('photo-admin-action')->assertDontSee('site-admin-toggle');
        $this->get(route('profile'))->assertOk()->assertSee('Profil sekolah')->assertDontSee('photo-admin-action')->assertDontSee('site-admin-toggle');
        $this->get(route('academics'))->assertOk();$this->get(route('announcements'))->assertOk();$this->get(route('ppdb'))->assertOk();$this->get(route('gallery'))->assertOk()->assertDontSee('site-admin-toggle');$this->get(route('contact'))->assertOk()->assertDontSee('site-admin-toggle');
        $this->assertSame(1,ContactMessage::count());$this->assertSame(1,MediaAsset::count());
    }

    public function test_admin_home_uses_the_public_website_and_management_pages_use_direct_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $this->actingAs($admin);

        foreach (['home', 'profile', 'academics', 'news', 'extracurriculars', 'gallery', 'contact'] as $route) {
            $this->get(route($route))->assertOk()->assertDontSee('site-admin-toggle')->assertDontSee('photo-admin-action');
        }
        $this->get(route('gallery'))->assertSee('Galeri');

        $this->get(route('admin.dashboard'))->assertOk()->assertViewIs('public.home')
            ->assertSee('admin-preview')->assertSee('site-admin-toggle')->assertDontSee('Ringkasan Konten')
            ->assertSee(route('admin.dashboard'), false)
            ->assertSee(route('admin.dashboard', ['section' => 'profil']), false)
            ->assertSee(route('admin.dashboard', ['section' => 'program']), false)
            ->assertSee(route('admin.dashboard', ['section' => 'berita']), false)
            ->assertSee(route('admin.dashboard', ['section' => 'ekskul']), false)
            ->assertSee(route('admin.dashboard', ['section' => 'galeri']), false)
            ->assertSee(route('admin.dashboard', ['section' => 'kontak']), false)
            ->assertSee(route('admin.settings.edit'), false)
            ->assertSee(route('admin.articles.index'), false)
            ->assertSee(route('admin.content.index', 'event'), false)
            ->assertSee(route('admin.content.index', 'gallery'), false)
            ->assertSee(route('admin.spmb.dashboard'), false)
            ->assertSee(route('admin.users.index'), false)
            ->assertSee('Akun Guru');

        $managementPages = [
            ['admin.settings.edit', 'admin.settings'],
            ['admin.articles.index', 'admin.articles.index'],
            ['admin.articles.reviews', 'admin.articles.reviews'],
            ['admin.categories.index', 'admin.categories.index'],
            ['admin.users.index', 'admin.users.index'],
            ['admin.messages.index', 'admin.messages.index'],
            ['admin.profile.edit', 'admin.profile'],
        ];

        foreach ($managementPages as [$routeName, $view]) {
            $response = $this->get(route($routeName));
            $response->assertOk()->assertViewIs($view)
                ->assertSee('id="admin-sidebar"', false)->assertSee('class="active"', false);
            $this->assertSame(1, substr_count($response->getContent(), 'id="admin-sidebar"'));
            $response->assertSee(route('admin.dashboard'), false)->assertSee('Kembali ke Website');
        }

        foreach (array_keys(\App\Http\Controllers\Admin\ContentEntryController::TYPES) as $type) {
            $response = $this->get(route('admin.content.index', $type));
            $response->assertOk()->assertViewIs('admin.content.index')
                ->assertSee('id="admin-sidebar"', false)->assertSee('class="active"', false)
                ->assertSee(route('admin.dashboard'), false)->assertSee('Kembali ke Website');
            $this->assertSame(1, substr_count($response->getContent(), 'id="admin-sidebar"'));
        }

        foreach (['profil', 'program', 'berita', 'ekskul', 'galeri', 'kontak'] as $section) {
            $this->get(route('admin.dashboard', ['section' => $section]))->assertOk()
                ->assertSee('admin-preview')->assertSee('site-admin-toggle')
                ->assertSee('data-scroll-target="'.$section.'"', false);
        }

        foreach (['hero', 'profile'] as $placement) {
            $this->get(route('admin.photos.edit', $placement))->assertOk()
                ->assertSee('Pilih foto baru')->assertSee('Simpan Foto')->assertSee('Batal')
                ->assertDontSee('title"')->assertDontSee('excerpt');
        }

        Storage::fake('public');
        foreach (['hero', 'profile'] as $placement) {
            $this->put(route('admin.photos.update', $placement), [
                'image' => UploadedFile::fake()->image($placement.'.jpg', 640, 420),
            ])->assertRedirect(route('admin.dashboard'))->assertSessionHas('success');
            $key = $placement.'_photo_path';
            $path = json_decode(SiteSetting::findOrFail($key)->value, true);
            $this->assertTrue(Storage::disk('public')->exists($path));
            $this->assertDatabaseHas('media_assets', ['path' => $path]);
            $this->get(route('home'))->assertOk()->assertSee(Storage::disk('public')->url($path), false);
        }

        $heroPath = json_decode(SiteSetting::findOrFail('hero_photo_path')->value, true);
        $profilePath = json_decode(SiteSetting::findOrFail('profile_photo_path')->value, true);
        $this->assertNotSame($heroPath, $profilePath);
        $this->put(route('admin.photos.update', 'hero'), [
            'image' => UploadedFile::fake()->create('invalid.svg', 10, 'image/svg+xml'),
        ])->assertSessionHasErrors('image');
        $this->assertSame($heroPath, json_decode(SiteSetting::findOrFail('hero_photo_path')->value, true));
        $this->get(route('admin.dashboard'))->assertOk();

        $viewer = User::factory()->create(['role' => 'teacher', 'active' => true]);
        $this->actingAs($viewer)->get(route('admin.photos.edit', 'hero'))->assertForbidden();
        $this->get(route('home'))->assertOk()->assertDontSee('site-admin-toggle');
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->get(route('admin.photos.edit', 'hero'))->assertRedirect(route('login'));
        $this->put(route('admin.photos.update', 'hero'), [
            'image' => UploadedFile::fake()->image('tanpa-sesi.jpg'),
        ])->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_docx_import_preserves_text_and_embedded_images_and_rejects_bad_files(): void
    {
        Storage::fake('public');$teacher=User::factory()->create(['role'=>'teacher','active'=>true]);$this->actingAs($teacher);
        $path=base_path('tests/fixtures/sample-with-image.docx');
        $upload=new UploadedFile($path,'sample-with-image.docx','application/vnd.openxmlformats-officedocument.wordprocessingml.document',null,true);
        $response=$this->postJson(route('admin.articles.import'),['file'=>$upload])->assertOk();
        $html=$response->json('html');
        $this->assertStringContainsString('Judul dari Word',$html);$this->assertStringContainsString('Paragraf setelah gambar',$html);
        $this->assertMatchesRegularExpression('~src="/storage/media/[a-f0-9-]+\.png"~',$html);
        $image=MediaAsset::firstOrFail();$this->assertTrue(Storage::disk('public')->exists($image->path));
        $this->post(route('admin.articles.store'),['title'=>'Hasil Impor Word','body'=>$html,'intent'=>'review'])->assertRedirect();
        $article=Article::where('title','Hasil Impor Word')->firstOrFail();
        $this->assertSame('pending',$article->status);$this->assertStringContainsString('/storage/media/',$article->body);
        $admin=User::factory()->create(['role'=>'admin','active'=>true]);
        $this->actingAs($admin)->post(route('admin.articles.review',$article),['action'=>'publish'])->assertRedirect();
        $this->get(route('article.show',$article->slug))->assertOk()->assertSee('/storage/media/');
        $bad=new UploadedFile(tempnam(sys_get_temp_dir(),'bad-docx'),'bad.docx','application/vnd.openxmlformats-officedocument.wordprocessingml.document',null,true);
        $this->postJson(route('admin.articles.import'),['file'=>$bad])->assertUnprocessable();
    }
}
