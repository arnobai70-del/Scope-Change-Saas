<?php

namespace Tests\Feature\Admin;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'two_factor_confirmed_at' => now()]);
    }

    public function test_page_meta_overrides_apply_to_public_pages(): void
    {
        $this->actingAs($this->admin())->put('/admin/seo/meta', [
            'default_description' => 'Fallback description',
            'og_image' => 'https://cdn.example.com/og.png',
            'google_verification' => 'abc123_XYZ',
            'discourage_indexing' => false,
            'pages' => [
                '/pricing' => ['title' => 'Simple pricing for agencies', 'description' => 'Custom pricing description'],
                '/not-a-page' => ['title' => 'Ignored', 'description' => ''],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(1, AuditEvent::query()->where('event', 'admin.seo_updated')->count());

        auth()->logout();

        $this->get('/pricing')->assertOk()
            ->assertSee('<title>Simple pricing for agencies · '.config('app.name').'</title>', false)
            ->assertSee('<meta name="description" content="Custom pricing description">', false)
            ->assertSee('<meta property="og:image" content="https://cdn.example.com/og.png">', false)
            ->assertSee('<meta name="google-site-verification" content="abc123_XYZ">', false);

        // Pages without an override keep their own text.
        $this->get('/features')->assertOk()->assertSee('<title>Features · ', false);
    }

    public function test_google_analytics_loads_only_on_public_pages_when_set(): void
    {
        $this->get('/')->assertDontSee('googletagmanager.com');

        $this->actingAs($this->admin())->put('/admin/seo/analytics', ['ga_id' => 'bad-id'])->assertSessionHasErrors('ga_id');
        $this->put('/admin/seo/analytics', ['ga_id' => 'G-AB12CD34EF'])->assertSessionHasNoErrors();

        auth()->logout();

        $this->get('/')->assertOk()->assertSee('googletagmanager.com/gtag/js?id=G-AB12CD34EF', false);
        $this->get('/legal/privacy')->assertSee('Google Analytics cookies');
    }

    public function test_custom_robots_and_reset(): void
    {
        $this->actingAs($this->admin())->put('/admin/seo/robots', ['custom' => "User-agent: *\r\nDisallow: /private/"])->assertSessionHasNoErrors();
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /private/')->assertDontSee('Disallow: /c/');

        $this->put('/admin/seo/robots', ['custom' => null]);
        $this->get('/robots.txt')->assertSee('Disallow: /c/');
    }

    public function test_sitemap_exclusions_and_extra_urls(): void
    {
        $this->actingAs($this->admin())->put('/admin/seo/sitemap', [
            'include_segments' => true,
            'include_posts' => false,
            'extra_urls' => ['/landing/offer', 'https://example.com/partner'],
            'exclude' => ['/pricing'],
        ])->assertSessionHasNoErrors();

        $this->get('/sitemap.xml')->assertOk()
            ->assertDontSee(url('/pricing').'<', false)
            ->assertDontSee('/blog/what-is-scope-creep')
            ->assertSee('/for/agencies')
            ->assertSee(url('/landing/offer'))
            ->assertSee('https://example.com/partner');

        $this->put('/admin/seo/sitemap', ['include_segments' => true, 'include_posts' => true, 'exclude' => ['/nope']])->assertSessionHasErrors('exclude.0');
    }

    public function test_hiding_the_site_blocks_crawlers(): void
    {
        $this->actingAs($this->admin())->put('/admin/seo/meta', ['discourage_indexing' => true])->assertSessionHasNoErrors();

        auth()->logout();

        $this->get('/robots.txt')->assertSee("Disallow: /\n", false);
        $this->get('/')->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_non_admins_cannot_change_seo(): void
    {
        $this->actingAs(User::factory()->create())->put('/admin/seo/analytics', ['ga_id' => 'G-AB12CD34EF'])->assertNotFound();
        $this->get('/admin/seo')->assertNotFound();
    }
}
