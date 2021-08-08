<?php

namespace RTippin\Janus\Tests;

use Orchestra\Testbench\TestCase;
use RTippin\Janus\JanusServiceProvider;

class JanusTestCase extends TestCase
{
    const Endpoint = 'http://janus.test';
    const AdminEndpoint = 'http://janus.test/admin';
    const AdminSecret = 'admin-secret';
    const ApiSecret = 'api-secret';
    const SuccessResponse = ['janus' => 'success'];

    protected function getPackageProviders($app): array
    {
        return [
            JanusServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $config = $app->get('config');

        $config->set('janus.server_endpoint', self::Endpoint);
        $config->set('janus.admin_server_endpoint', self::AdminEndpoint);
        $config->set('janus.verify_ssl', false);
        $config->set('janus.debug', false);
        $config->set('janus.admin_secret', self::AdminSecret);
        $config->set('janus.api_secret', self::ApiSecret);
        $config->set('janus.video_room_secret', 'video-room-secret');
    }
}
