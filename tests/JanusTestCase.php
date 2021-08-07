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
        $config = $app->get('config');

        $config->set('janus.server_endpoint', 'http://janus.test');
        $config->set('janus.server_admin_endpoint', 'http://janus.test/admin');
        $config->set('janus.backend_ssl', false);
        $config->set('janus.admin_secret', 'admin-secret');
        $config->set('janus.api_secret', 'api-secret');
        $config->set('janus.video_room_secret', 'video-room-secret');
    }
}
