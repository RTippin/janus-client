<?php

namespace RTippin\MessengerBots\Tests;

use Orchestra\Testbench\TestCase;

class JanusTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $config = $app->get('config');

        $config->set('janus.enabled', true);
    }
}
