<?php

namespace Tests\Feature;

use Tests\TestCase;

class TrustedProxyHttpsTest extends TestCase
{
    public function test_https_forwarded_by_render_is_used_for_asset_urls(): void
    {
        config([
            'app.url' => 'http://localhost',
            'app.asset_url' => null,
        ]);

        $response = $this->get('/', [
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'lgu-clarin-hris-system-1.onrender.com',
        ]);

        $response->assertOk();
        $response->assertSee(
            'https://lgu-clarin-hris-system-1.onrender.com/images/clarin_logo.png',
            false,
        );
    }
}
