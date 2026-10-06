<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
