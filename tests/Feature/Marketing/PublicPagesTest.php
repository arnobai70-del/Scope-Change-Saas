<?php

namespace Tests\Feature\Marketing;

use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_pages_render(): void
    {
        foreach ([
            '/', '/pricing', '/features', '/how-it-works', '/security', '/docs', '/status', '/subprocessors',
            '/templates/change-request', '/examples/client-approval', '/for/freelancers', '/for/agencies', '/for/consultants',
            '/blog', '/blog/what-is-scope-creep', '/contact', '/legal/terms', '/legal/privacy', '/legal/dpa', '/legal/refund',
            '/tools/change-request-generator', '/tools/scope-creep-calculator',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_unknown_pages_return_not_found(): void
    {
        $this->get('/for/dentists')->assertNotFound();
        $this->get('/blog/nope')->assertNotFound();
        $this->get('/legal/cookies')->assertNotFound();
    }

    public function test_generator_produces_a_message(): void
    {
        $this->get('/tools/change-request-generator?'.http_build_query([
            'client' => 'Sam',
            'project' => 'Brand refresh',
            'change' => 'Social media templates <script>alert(1)</script>',
            'price' => '400',
            'currency' => 'EUR',
            'days' => 3,
            'payment' => 'before_start',
        ]))
            ->assertOk()
            ->assertSee('Hi Sam')
            ->assertSee('€400.00')
            ->assertSee('+3 days')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_calculator_computes_yearly_cost(): void
    {
        $this->get('/tools/scope-creep-calculator?rate=100&hours=2&requests=3&projects=10')
            ->assertOk()
            ->assertSee('600.00')
            ->assertSee('6,000.00');
    }

    public function test_sitemap_and_robots(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee('/pricing');
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /c/');
    }

    public function test_contact_form_creates_ticket(): void
    {
        $this->post('/contact', ['name' => 'Ana', 'email' => 'Ana@Example.com', 'subject' => 'Hello', 'body' => 'Question'])->assertRedirect();

        $this->assertDatabaseHas('support_tickets', ['email' => 'ana@example.com', 'subject' => 'Hello']);
    }

    public function test_contact_honeypot_rejects_bots(): void
    {
        $this->post('/contact', ['name' => 'Bot', 'email' => 'bot@example.com', 'subject' => 'x', 'body' => 'y', 'website' => 'spam'])->assertSessionHasErrors('website');

        $this->assertSame(0, SupportTicket::query()->count());
    }

    public function test_security_headers_are_present(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options');
    }
}
