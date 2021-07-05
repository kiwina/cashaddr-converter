<?php

namespace Kiwina\CashaddrConverter\Tests;

use Kiwina\CashaddrConverter\CashaddrConverterServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            CashaddrConverterServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
    }
}
