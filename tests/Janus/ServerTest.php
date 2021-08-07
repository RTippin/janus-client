<?php

namespace RTippin\Janus\Tests\Janus;

use RTippin\Janus\Server;
use RTippin\Janus\Tests\JanusTestCase;

class ServerTest extends JanusTestCase
{
    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server = app(Server::class);
    }

    /** @test */
    public function it_returns_class_details()
    {
        $expected = [
            'serverEndpoint' => 'http://janus.test',
            'adminServerEndpoint' => 'http://janus.test/admin',
            'apiSecret' => 'api-secret',
            'selfSigned' => false,
            'latencyStart' => null,
            'latencyEnd' => null,
            'sessionId' => null,
            'handleId' => null,
            'plugin' => null,
            'apiPayload' => null,
            'apiResponse' => null,
        ];

        $this->assertSame($expected, $this->server->getCurrentDetails());
    }

    /** @test */
    public function it_is_not_attached_to_a_plugin()
    {
        $this->assertFalse($this->server->isAttached());
    }

    /** @test */
    public function it_is_attached_to_a_plugin()
    {
        $this->server->setPlugin('plugin')->setHandleId('handle');

        $this->assertTrue($this->server->isAttached());
    }
}
