<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Notifications\DatabaseNotification;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Event;
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

it('refreshes the bell for every stored Filament notification, flag or not', function (): void {
    Event::fake([DatabaseNotificationsSent::class]);
    $user = (new TestUser)->forceFill(['id' => 1]);

    event(new NotificationSent($user, new DatabaseNotification(['format' => 'filament']), 'database'));

    Event::assertDispatched(DatabaseNotificationsSent::class, 1);
});

it('leaves other channels and non-Filament notifications alone', function (): void {
    Event::fake([DatabaseNotificationsSent::class]);
    $user = (new TestUser)->forceFill(['id' => 1]);

    event(new NotificationSent($user, new DatabaseNotification([]), 'mail'));
    event(new NotificationSent($user, new class extends Notification {}, 'database'));

    Event::assertNotDispatched(DatabaseNotificationsSent::class);
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
