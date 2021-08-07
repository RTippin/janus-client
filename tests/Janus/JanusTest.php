<?php

namespace RTippin\Janus\Tests\Janus;

use RTippin\Janus\Janus;
use RTippin\Janus\Plugins\VideoRoom;
use RTippin\Janus\Server;
use RTippin\Janus\Tests\JanusTestCase;

class JanusTest extends JanusTestCase
{
    private Janus $janus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->janus = app(Janus::class);
    }

    /** @test */
    public function janus_facade_resolves_new_instance()
    {
        $instance = \RTippin\Janus\Facades\Janus::getInstance();

        $this->assertInstanceOf(Janus::class, $instance);
        $this->assertNotSame($this->janus, $instance);
    }

    /** @test */
    public function janus_alias_resolves_new_instance()
    {
        $instance = app('janus');

        $this->assertInstanceOf(Janus::class, $instance);
        $this->assertNotSame($this->janus, $instance);
    }

    /** @test */
    public function it_resolves_server()
    {
        $this->assertInstanceOf(Server::class, $this->janus->server());
    }

    /** @test */
    public function it_resolves_videoroom()
    {
        $this->assertInstanceOf(VideoRoom::class, $this->janus->videoRoom());
    }
}
