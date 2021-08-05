<?php

namespace RTippin\Janus\Tests;

use Orchestra\Testbench\TestCase;
use RTippin\Janus\JanusServiceProvider;

class JanusTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            JanusServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        //
    }
}
