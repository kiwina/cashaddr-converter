<?php

namespace Kiwina\CashaddrConverter;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class CashaddrConverterServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CashaddrConverter::class, function () {
            return new CashaddrConverter();
        });
    }

    public function provides()
    {
        return [CashaddrConverter::class];
    }
}
