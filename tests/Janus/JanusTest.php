<?php

namespace RTippin\Janus\Tests\Janus;

use RTippin\Janus\Janus;
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
        $this->assertInstanceOf(Janus::class, \RTippin\Janus\Facades\Janus::getInstance());
        $this->assertNotSame($this->janus, \RTippin\Janus\Facades\Janus::getInstance());
    }
}
