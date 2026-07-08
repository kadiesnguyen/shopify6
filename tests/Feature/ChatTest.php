<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('member');

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');

        $this->member = User::factory()->create([
            'status' => 'active',
            'name' => 'Member User',
            'email' => 'member@test.com',
        ]);
        $this->member->assignRole('member');
    }

    public function test_member_can_send_and_read_chat_messages(): void
    {
        $this->actingAs($this->member)
            ->post(route('member.chat.messages.store'), ['body' => 'Hello support'])
            ->assertOk()
            ->assertJsonPath('message.body', 'Hello support');

        $this->actingAs($this->member)
            ->get(route('member.chat.messages.index'))
            ->assertOk()
            ->assertJsonCount(1, 'messages');
    }

    public function test_admin_can_view_conversations_and_reply(): void
    {
        $conversation = ChatConversation::query()->create([
            'user_id' => $this->member->id,
            'last_message_at' => now(),
        ]);

        ChatMessage::query()->create([
            'conversation_id' => $conversation->id,
            'sender_role' => ChatMessage::ROLE_USER,
            'sender_user_id' => $this->member->id,
            'body' => 'Need help',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.chat.conversations', ['filter' => 'unread']))
            ->assertOk()
            ->assertJsonPath('conversations.0.unread_count', 1);

        $this->actingAs($this->admin)
            ->get(route('admin.chat.show', $conversation))
            ->assertOk()
            ->assertJsonPath('messages.0.body', 'Need help');

        $this->actingAs($this->admin)
            ->post(route('admin.chat.messages.store', $conversation), ['body' => 'We can help'])
            ->assertOk();

        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversation->id,
            'sender_role' => ChatMessage::ROLE_ADMIN,
            'body' => 'We can help',
        ]);
    }

    public function test_admin_can_set_display_name_and_delete_user_messages(): void
    {
        $conversation = ChatConversation::query()->create([
            'user_id' => $this->member->id,
            'last_message_at' => now(),
        ]);

        $msg = ChatMessage::query()->create([
            'conversation_id' => $conversation->id,
            'sender_role' => ChatMessage::ROLE_USER,
            'sender_user_id' => $this->member->id,
            'body' => 'Delete me',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.chat.display-name', $conversation), [
                'admin_display_name' => 'VIP Client',
            ])
            ->assertOk()
            ->assertJsonPath('conversation.admin_label', 'VIP Client');

        $this->assertEquals('Member User', $this->member->fresh()->name);

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.chat.messages.destroy', $conversation), [
                'message_ids' => [$msg->id],
            ])
            ->assertOk()
            ->assertJsonPath('deleted', 1);

        $this->assertSoftDeleted('chat_messages', ['id' => $msg->id]);
    }

    public function test_admin_can_delete_conversations_from_list(): void
    {
        $conversation = ChatConversation::query()->create([
            'user_id' => $this->member->id,
            'last_message_at' => now(),
        ]);

        $guestConversation = ChatConversation::query()->create([
            'guest_token' => 'guest-token-1',
            'guest_label' => 'guest@test.com',
            'last_message_at' => now(),
        ]);

        ChatMessage::query()->create([
            'conversation_id' => $conversation->id,
            'sender_role' => ChatMessage::ROLE_USER,
            'sender_user_id' => $this->member->id,
            'body' => 'Hello',
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.chat.conversations.destroy'), [
                'conversation_ids' => [$conversation->id, $guestConversation->id],
            ])
            ->assertOk()
            ->assertJsonPath('deleted', 2);

        $this->assertDatabaseMissing('chat_conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('chat_conversations', ['id' => $guestConversation->id]);
        $this->assertDatabaseMissing('chat_messages', ['conversation_id' => $conversation->id]);

        $this->assertEquals('Member User', $this->member->fresh()->name);
    }

    public function test_member_can_send_image(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('proof.png', 100, 'image/png');

        $this->actingAs($this->member)
            ->post(route('member.chat.messages.store'), ['image' => $file])
            ->assertOk()
            ->assertJsonPath('message.image_url', fn ($url) => is_string($url) && $url !== '');
    }

    public function test_guest_can_chat_via_session(): void
    {
        $this->postJson(route('guest.chat.messages.store'), ['body' => 'Guest hello'])
            ->assertOk();

        $this->getJson(route('guest.chat.messages.index'))
            ->assertOk()
            ->assertJsonCount(1, 'messages');
    }

    public function test_admin_chat_page_loads(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.chat.index'))
            ->assertOk()
            ->assertSee(__('chat.admin_title'))
            ->assertSee(__('chat.support_display_name'))
            ->assertSee(__('chat.support_welcome_message'))
            ->assertSee(__('chat.save_settings'))
            ->assertSee('admin-chat-shell', false)
            ->assertSee('adminChat', false);
    }

    public function test_admin_can_update_chat_support_settings(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('admin.chat.settings.update'), [
                'chat_support_title' => 'Hotline VIP',
                'chat_support_welcome_message' => 'Xin chào! Tôi có thể giúp gì cho bạn?',
                'chat_support_avatar' => UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg'),
            ])
            ->assertRedirect(route('admin.chat.index'))
            ->assertSessionHas('status', __('chat.settings_saved'));

        $this->assertSame('Hotline VIP', SiteSettings::chatSupportTitle());
        $this->assertSame('Xin chào! Tôi có thể giúp gì cho bạn?', SiteSettings::chatSupportWelcomeMessage());
        $this->assertNotNull(SiteSettings::get(SiteSettings::KEY_CHAT_SUPPORT_AVATAR));
        Storage::disk('public')->assertExists(SiteSettings::get(SiteSettings::KEY_CHAT_SUPPORT_AVATAR));

        $this->actingAs($this->member)
            ->get(route('member.chat.index'))
            ->assertOk()
            ->assertSee('Hotline VIP')
            ->assertSee('Xin chào! Tôi có thể giúp gì cho bạn?');
    }

    public function test_member_chat_page_is_full_screen(): void
    {
        $this->actingAs($this->member)
            ->get(route('member.chat.index'))
            ->assertOk()
            ->assertSee(__('chat.placeholder'))
            ->assertSee(__('chat.send'))
            ->assertSee(__('member.back'));
    }
}
