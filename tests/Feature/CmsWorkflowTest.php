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
        $this->get(route('admin.users.index'))->assertOk();
        $this->post(route('logout'))->assertRedirect(route('login'));

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
        $this->put(route('admin.settings.update'),['settings'=>['school_name'=>'Sekolah Uji','address'=>'Alamat Uji','email'=>'info@uji.test']])->assertRedirect();
        $this->assertSame('"Sekolah Uji"',SiteSetting::find('school_name')->value);
        $this->post(route('contact.store'),['name'=>'Pengirim Uji','email'=>'pengirim@example.test','subject'=>'Tanya','message'=>'Mohon informasi.'])->assertRedirect();
        $this->assertDatabaseHas('contact_messages',['subject'=>'Tanya']);
        $this->get(route('home'))->assertOk()->assertSee('Pengumuman ujian')->assertSee('Sekolah Uji');

        Storage::fake('public');
        $this->post(route('admin.content.store','gallery'),['title'=>'Foto kegiatan','excerpt'=>'Dokumentasi sekolah.','published'=>'1','image'=>UploadedFile::fake()->image('foto.jpg',600,400)])->assertRedirect();
        $gallery=ContentEntry::where('type','gallery')->firstOrFail();
        $this->assertTrue(Storage::disk('public')->exists($gallery->image_path));
        $this->assertDatabaseHas('media_assets',['path'=>$gallery->image_path]);
        $this->post(route('admin.content.store','gallery'),['title'=>'File tidak sah','published'=>'1','image'=>UploadedFile::fake()->create('fake.svg',10,'image/svg+xml')])->assertSessionHasErrors('image');
        $this->get(route('profile'))->assertOk()->assertSee('Profil sekolah');
        $this->get(route('academics'))->assertOk();$this->get(route('announcements'))->assertOk();$this->get(route('ppdb'))->assertOk();$this->get(route('gallery'))->assertOk();$this->get(route('contact'))->assertOk();
        $this->assertSame(1,ContactMessage::count());$this->assertSame(1,MediaAsset::count());
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
        $bad=new UploadedFile(tempnam(sys_get_temp_dir(),'bad-docx'),'bad.docx','application/vnd.openxmlformats-officedocument.wordprocessingml.document',null,true);
        $this->postJson(route('admin.articles.import'),['file'=>$bad])->assertUnprocessable();
    }
}
