<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\ContentEntry;
use App\Models\User;
use Tests\TestCase;

class ResponsiveAdminListsTest extends TestCase
{
    public function test_extracurricular_list_renders_mobile_cards_with_existing_edit_and_delete_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $entry = ContentEntry::create([
            'author_id' => $admin->id,
            'type' => 'extracurricular',
            'title' => 'Pramuka',
            'slug' => 'pramuka',
            'excerpt' => 'Kegiatan kepramukaan sekolah.',
            'published' => true,
            'sort_order' => 3,
        ]);

        $this->actingAs($admin)->get(route('admin.content.index', 'extracurricular'))
            ->assertOk()
            ->assertSee('extracurricular-record-list', false)
            ->assertSee('Pramuka')
            ->assertSee('Tayang')
            ->assertSee('Urutan tampil')
            ->assertSee(route('admin.content.edit', ['extracurricular', $entry]), false)
            ->assertSee(route('admin.content.destroy', ['extracurricular', $entry]), false);
    }

    public function test_contact_message_cards_keep_read_and_delete_actions_and_wrap_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $message = ContactMessage::create([
            'name' => 'Orang Tua Siswa',
            'email' => 'alamat-email-panjang@example.test',
            'subject' => 'Pertanyaan kegiatan sekolah',
            'message' => str_repeat('Mohon informasi kegiatan sekolah. ', 10),
        ]);

        $this->actingAs($admin)->get(route('admin.messages.index'))
            ->assertOk()
            ->assertSee('message-card-list', false)
            ->assertSee('Orang Tua Siswa')
            ->assertSee('alamat-email-panjang@example.test')
            ->assertSee('Pertanyaan kegiatan sekolah')
            ->assertSee('Belum dibaca')
            ->assertSee(route('admin.messages.read', $message), false)
            ->assertSee(route('admin.messages.destroy', $message), false);
    }
}
