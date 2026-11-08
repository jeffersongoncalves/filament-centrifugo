<?php

namespace JeffersonGoncalves\Filament\Centrifugo;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CentrifugoServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-centrifugo')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations();
    }
}
