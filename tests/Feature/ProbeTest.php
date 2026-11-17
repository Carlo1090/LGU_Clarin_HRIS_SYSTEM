<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProbeTest extends TestCase
{
    public function test_probe(): void
    {
        fwrite(STDERR, "\nPROBE env=".app()->environment()
            .' db='.config('database.default')
            .' session='.config('session.driver')
            .' console='.(app()->runningInConsole() ? 'yes' : 'no')
            .' getenv='.getenv('APP_ENV')
            .' _ENV='.($_ENV['APP_ENV'] ?? 'unset')
            .' _SERVER='.($_SERVER['APP_ENV'] ?? 'unset')
            .' configcache='.(file_exists(base_path('bootstrap/cache/config.php')) ? 'yes' : 'no')
            ."\n");

        $this->assertTrue(true);
    }
}