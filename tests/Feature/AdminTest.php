<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'two_factor_confirmed_at' => now()]);
    }

    public function test_non_admins_get_not_found(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertNotFound();
    }

    public function test_admins_must_enable_two_factor(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user)->get('/admin')->assertRedirect(route('security.edit'));
    }

    public function test_admin_pages_render(): void
    {
        $this->actingAs($this->admin());

        foreach (['/admin' => 'admin/Dashboard', '/admin/users' => 'admin/Users', '/admin/workspaces' => 'admin/Workspaces', '/admin/webhooks' => 'admin/Webhooks', '/admin/tickets' => 'admin/Tickets', '/admin/flags' => 'admin/Flags', '/admin/audit' => 'admin/Audit'] as $url => $component) {
            $this->get($url)->assertOk()->assertInertia(fn ($page) => $page->component($component));
        }
    }

    public function test_suspending_a_workspace_is_audited(): void
    {
        $admin = $this->admin();
        $workspace = Workspace::factory()->create();

        $this->actingAs($admin)->put("/admin/workspaces/{$workspace->id}/suspension", ['suspend' => true, 'reason' => 'Abuse report'])->assertRedirect();

        $this->assertTrue($workspace->refresh()->isSuspended());
        $this->assertTrue(AuditEvent::query()->where('event', 'admin.workspace_suspended')->where('actor_id', $admin->id)->exists());
    }

    public function test_admin_cannot_suspend_themselves(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put("/admin/users/{$admin->id}/status", ['status' => 'suspended', 'reason' => 'oops'])->assertStatus(422);
    }

    public function test_admin_can_grant_and_revoke_admin_access(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin)->put("/admin/users/{$user->id}/admin", ['is_admin' => true])->assertRedirect();
        $this->assertTrue($user->refresh()->is_admin);
        $this->assertTrue(AuditEvent::query()->where('event', 'admin.granted')->where('actor_id', $admin->id)->exists());

        $this->put("/admin/users/{$user->id}/admin", ['is_admin' => false])->assertRedirect();
        $this->assertFalse($user->refresh()->is_admin);
    }

    public function test_admin_cannot_change_their_own_admin_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put("/admin/users/{$admin->id}/admin", ['is_admin' => false])->assertStatus(422);
        $this->assertTrue($admin->refresh()->is_admin);
    }

    public function test_admin_can_verify_a_user_and_send_a_reset_link(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $user = User::factory()->unverified()->create();

        $this->actingAs($admin)->post("/admin/users/{$user->id}/verify")->assertRedirect();
        $this->assertNotNull($user->refresh()->email_verified_at);

        $this->post("/admin/users/{$user->id}/password-reset")->assertRedirect();
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_admin_can_extend_a_trial(): void
    {
        $admin = $this->admin();
        $workspace = Workspace::factory()->create(['trial_ends_at' => now()->subDay()]);

        $this->actingAs($admin)->put("/admin/workspaces/{$workspace->id}/trial", ['days' => 14])->assertRedirect();

        $this->assertTrue($workspace->refresh()->onTrial());
        $this->assertTrue($workspace->trial_ends_at->isAfter(now()->addDays(13)));
        $this->assertTrue(AuditEvent::query()->where('event', 'admin.trial_extended')->exists());
    }

    public function test_non_admins_cannot_use_admin_actions(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->put("/admin/users/{$other->id}/admin", ['is_admin' => true])->assertNotFound();
        $this->assertFalse($other->refresh()->is_admin);
    }

    public function test_make_admin_command_promotes_an_existing_user(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);

        $this->artisan('app:make-admin', ['email' => 'owner@example.com'])->assertSuccessful();

        $this->assertTrue($user->refresh()->is_admin);
    }

    public function test_make_admin_command_creates_a_new_admin(): void
    {
        $this->artisan('app:make-admin', ['email' => 'boss@example.com', '--name' => 'Boss'])
            ->expectsQuestion('Password (input hidden)', 'a-very-long-password-123')
            ->assertSuccessful();

        $user = User::query()->where('email', 'boss@example.com')->firstOrFail();
        $this->assertTrue($user->is_admin);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->current_workspace_id);
    }

    public function test_suspending_a_user_and_toggling_a_flag_are_audited(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin)->put("/admin/users/{$user->id}/status", ['status' => 'suspended', 'reason' => 'Spam'])->assertRedirect();
        $this->assertSame('suspended', $user->refresh()->status);

        $this->put('/admin/flags', ['key' => 'new_editor', 'enabled' => true])->assertRedirect();

        $this->assertTrue(AuditEvent::query()->where('event', 'admin.user_suspended')->exists());
        $this->assertTrue(AuditEvent::query()->where('event', 'admin.flag_toggled')->exists());
    }
}
