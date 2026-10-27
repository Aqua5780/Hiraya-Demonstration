<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_is_disabled_in_production(): void
    {
        $this->app->instance('env', 'production');

        $this->app->make(DatabaseSeeder::class)->run();

        $this->assertDatabaseCount('users', 0);
    }
}
