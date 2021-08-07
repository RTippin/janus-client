<?php

namespace RTippin\Janus\Tests\Janus;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
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

    /** @test */
    public function it_gets_server_info()
    {
        Http::fake([
            self::Endpoint.'/info' => Http::response(self::SuccessResponse),
        ]);

        $this->assertSame(self::SuccessResponse, $this->janus->info());
    }

    /** @test */
    public function it_gets_success_pong()
    {
        Http::fake([
            self::Endpoint => Http::response(['janus' => 'pong']),
        ]);

        $ping = $this->janus->ping();

        Http::assertSent(function (Request $request) {
            return $request['janus'] === 'ping';
        });
        $this->assertTrue($ping['pong']);
        $this->assertIsFloat($ping['latency']);
    }

    /** @test */
    public function it_gets_failed_pong_if_janus_not_pong()
    {
        Http::fake([
            self::Endpoint => Http::response(['janus' => 'unknown']),
        ]);

        $ping = $this->janus->ping();

        $this->assertFalse($ping['pong']);
    }

    /** @test */
    public function it_gets_failed_pong_if_server_throws_exception()
    {
        Http::fake([
            self::Endpoint => Http::response(null, 500),
        ]);

        $ping = $this->janus->ping();

        $this->assertFalse($ping['pong']);
    }

    /** @test */
    public function it_connects_and_sets_session_id()
    {
        Http::fake([
            self::Endpoint => Http::response(array_merge(self::SuccessResponse, [
                'data' => [
                    'id' => '1234',
                ]
            ])),
        ]);

        $this->janus->connect();

        Http::assertSent(function (Request $request) {
            return $request['janus'] === 'create';
        });
        $this->assertSame('1234', $this->janus->server()->getCurrentDetails()['sessionId']);
    }

    /** @test */
    public function it_connects_but_doesnt_set_missing_session_id()
    {
        Http::fake([
            self::Endpoint => Http::response(array_merge(self::SuccessResponse, [
                'data' => [
                    'invalid' => '1234',
                ]
            ])),
        ]);

        $this->janus->connect();

        $this->assertNull($this->janus->server()->getCurrentDetails()['sessionId']);
    }

    /** @test */
    public function it_attaches_and_sets_plugin_and_handle_id()
    {
        Http::fake([
            self::Endpoint => Http::response(array_merge(self::SuccessResponse, [
                'data' => [
                    'id' => '5678',
                ]
            ])),
        ]);

        $this->janus->attach('plugin');

        Http::assertSent(function (Request $request) {
            return $request['janus'] === 'attach'
                && $request['plugin'] === 'plugin';
        });
        $this->assertSame('5678', $this->janus->server()->getCurrentDetails()['handleId']);
        $this->assertSame('plugin', $this->janus->server()->getCurrentDetails()['plugin']);
    }

    /** @test */
    public function it_attaches_but_doesnt_set_missing_plugin_and_handle_id()
    {
        Http::fake([
            self::Endpoint => Http::response(array_merge(self::SuccessResponse, [
                'data' => [
                    'invalid' => '5678',
                ]
            ])),
        ]);

        $this->janus->attach('plugin');

        $this->assertNull($this->janus->server()->getCurrentDetails()['handleId']);
        $this->assertNull($this->janus->server()->getCurrentDetails()['plugin']);
    }
}
