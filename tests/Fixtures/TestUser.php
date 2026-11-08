<?php

declare(strict_types=1);

namespace JeffersonGoncalves\Filament\Centrifugo\Tests\Fixtures;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User;

class TestUser extends User implements FilamentUser
{
    protected $guarded = [];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
