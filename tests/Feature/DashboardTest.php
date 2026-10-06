<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_new_users_are_sent_to_onboarding_first(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('onboarding.show'));
        $this->assertNotNull($user->refresh()->current_workspace_id);
    }

    public function test_onboarding_completes_and_unlocks_the_dashboard(): void
    {
        $workspace = Workspace::factory()->notOnboarded()->create();
        $user = $workspace->owner;

        $this->actingAs($user)->get('/app/onboarding')->assertOk()->assertInertia(fn ($page) => $page->component('onboarding/Wizard'));

        $this->post('/app/onboarding', [
            'name' => 'Northwind Studio',
            'currency' => 'GBP',
            'timezone' => 'Europe/London',
            'service_type' => 'Web design',
        ])->assertRedirect(route('clients.index', ['new' => 1]));

        $this->assertSame('GBP', $workspace->refresh()->currency);
        $this->get(route('dashboard'))->assertOk()->assertInertia(fn ($page) => $page->component('Dashboard')->has('metrics'));
    }

    public function test_suspended_workspace_sees_suspension_page(): void
    {
        $workspace = Workspace::factory()->create(['suspended_at' => now(), 'suspension_reason' => 'Payment dispute']);

        $this->actingAs($workspace->owner)
            ->get(route('dashboard'))
            ->assertForbidden()
            ->assertInertia(fn ($page) => $page->component('errors/Suspended')->where('reason', 'Payment dispute'));
    }

    public function test_legacy_dashboard_url_redirects(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertRedirect('/app/dashboard');
    }
}
