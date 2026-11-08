<?php

declare(strict_types=1);

namespace JeffersonGoncalves\Filament\Centrifugo\Tests;

use Filament\Facades\Filament;
use JeffersonGoncalves\Filament\Centrifugo\Tests\Fixtures\TestPanelProvider;
use JeffersonGoncalves\Filament\Centrifugo\Tests\Fixtures\TestUser;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    // Filament, Livewire and laravel-centrifugo providers come from package discovery,
    // so this file is identical on every Filament major (1.x/2.x/3.x).
    protected $enablesPackageDiscoveries = true;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->withoutVite();
    }

    protected function getPackageProviders($app): array
    {
        return [
            TestPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('auth.providers.users.model', TestUser::class);
        $app['config']->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'users']);
        $app['config']->set('centrifugo.token_secret', 'secret');
    }
}
