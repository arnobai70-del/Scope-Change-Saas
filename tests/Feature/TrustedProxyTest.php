<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    public function test_forwarded_https_is_ignored_without_trusted_proxies(): void
    {
        Route::get('/_proxy-check', fn (Request $request) => $request->getSchemeAndHttpHost());

        $this->get('/_proxy-check', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'demo.example.com'])
            ->assertSeeText('http://localhost');
    }
}
