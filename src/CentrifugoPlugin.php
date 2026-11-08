<?php

declare(strict_types=1);

namespace JeffersonGoncalves\Filament\Centrifugo;

use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Notifications\DatabaseNotification;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;

/**
 * Realtime notifications for a Filament panel over Centrifugo
 * (jeffersongoncalves/laravel-centrifugo): injects the Echo stand-in for
 * logged-in users, connecting as the panel's auth guard, and turns off
 * database-notification polling.
 */
class CentrifugoPlugin implements Plugin
{
    protected bool $disablePolling = true;

    public function getId(): string
    {
        return 'filament-centrifugo';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    /**
     * Keep polling as well (e.g. while the websocket rolls out).
     */
    public function disablePolling(bool $condition = true): static
    {
        $this->disablePolling = $condition;

        return $this;
    }

    public function register(Panel $panel): void
    {
        static::refreshBellOnDatabaseNotifications();

        // Runs on every request (not only panel ones), so the connection-token
        // route accepts this panel's guard without editing config/centrifugo.php.
        /** @var list<string> $guards */
        $guards = config('centrifugo.guards', ['web']);
        config(['centrifugo.guards' => array_values(array_unique([...$guards, $panel->getAuthGuard()]))]);

        $panel->renderHook(
            PanelsRenderHook::SCRIPTS_AFTER,
            // Logged-in only: guests (login page) can't get a token.
            fn (): string => Filament::auth()->check()
                ? Blade::render('<x-centrifugo::scripts :guard="$guard" />', ['guard' => $panel->getAuthGuard()])
                : '',
        );
    }

    /**
     * Polling is off, so a sendToDatabase() without `isEventDispatched: true`
     * would never reach an open bell. Dispatch the refresh event for every
     * stored Filament notification instead. Callers that already pass the flag
     * get a second, harmless refresh.
     */
    protected static function refreshBellOnDatabaseNotifications(): void
    {
        // Once per application, even with the plugin on several panels.
        if (app()->bound('filament-centrifugo.listening')) {
            return;
        }

        app()->instance('filament-centrifugo.listening', true);

        Event::listen(NotificationSent::class, static function (NotificationSent $event): void {
            if ($event->channel === 'database' && $event->notification instanceof DatabaseNotification) {
                DatabaseNotificationsSent::dispatch($event->notifiable);
            }
        });
    }

    public function boot(Panel $panel): void
    {
        if ($this->disablePolling) {
            $panel->databaseNotificationsPolling(null);
        }
    }
}
