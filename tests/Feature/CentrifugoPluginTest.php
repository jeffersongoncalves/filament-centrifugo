<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use JeffersonGoncalves\Filament\Centrifugo\Tests\Fixtures\TestUser;

it('injects the Echo stand-in for a logged-in user, on the panel guard', function (): void {
    $this->actingAs((new TestUser)->forceFill(['id' => 1, 'name' => 'Ada', 'email' => 'ada@example.com']), 'admin')
        ->get('/admin')
        ->assertOk()
        ->assertSee('/centrifugo/echo.js?id=', false)
        ->assertSee('broadcasting/centrifugo-token?guard=admin', false);
});

it('injects nothing for guests', function (): void {
    $this->get('/admin/login')
        ->assertOk()
        ->assertDontSee('centrifugo/echo.js', false);
});

it('lets the panel guard request a connection token', function (): void {
    expect(config('centrifugo.guards'))->toContain('web', 'admin');

    $this->actingAs((new TestUser)->forceFill(['id' => 1]), 'admin')
        ->postJson('/broadcasting/centrifugo-token?guard=admin')
        ->assertOk()
        ->assertJsonStructure(['token']);
});

it('turns database-notification polling off', function (): void {
    $panel = Filament::getPanel('admin');
    $panel->boot();

    expect($panel->getDatabaseNotificationsPollingInterval())->toBeNull();
});

it('keeps polling when asked to', function (): void {
    $panel = Filament::getPanel('admin');
    $panel->getPlugin('filament-centrifugo')->disablePolling(false);
    $panel->databaseNotificationsPolling('30s');
    $panel->boot();

    expect($panel->getDatabaseNotificationsPollingInterval())->toBe('30s');
});
