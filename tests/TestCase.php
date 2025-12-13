<?php

declare(strict_types=1);

namespace Blamodex\Consent\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Blamodex\Consent\ConsentServiceProvider;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ConsentServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Create test table for DummyConsentUser
        Schema::create('consent_users', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('consent_users');
        Schema::dropIfExists('consents');
        Schema::dropIfExists('consent_types');
        Schema::dropIfExists('consent_sources');

        parent::tearDown();
    }
}
