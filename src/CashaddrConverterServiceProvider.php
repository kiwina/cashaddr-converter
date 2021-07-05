<?php

namespace Kiwina\CashaddrConverter;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Kiwina\CashaddrConverter\Commands\CashaddrConverterCommand;

class CashaddrConverterServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('cashaddr-converter');
    }
}
