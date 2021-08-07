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

    /** @test */
    public function it_detaches_and_removes_plugin_and_handle_id()
    {
        $this->janus
            ->server()
            ->setPlugin('plugin')
            ->setSessionId('1234')
            ->setHandleId('5678');
        Http::fake([
            self::Endpoint.'/1234/5678' => Http::response(self::SuccessResponse),
        ]);

        $this->janus->detach();

        Http::assertSent(function (Request $request) {
            return $request['janus'] === 'detach';
        });
        $this->assertSame('1234', $this->janus->server()->getCurrentDetails()['sessionId']);
        $this->assertNull($this->janus->server()->getCurrentDetails()['handleId']);
        $this->assertNull($this->janus->server()->getCurrentDetails()['plugin']);
    }

    /** @test */
    public function it_disconnects_and_removes_plugin_session_and_handle_id()
    {
        $this->janus
            ->server()
            ->setPlugin('plugin')
            ->setSessionId('1234')
            ->setHandleId('5678');
        Http::fake([
            self::Endpoint.'/1234' => Http::response(self::SuccessResponse),
        ]);

        $this->janus->disconnect();

        Http::assertSent(function (Request $request) {
            return $request['janus'] === 'destroy';
        });
        $this->assertNull($this->janus->server()->getCurrentDetails()['sessionId']);
        $this->assertNull($this->janus->server()->getCurrentDetails()['handleId']);
        $this->assertNull($this->janus->server()->getCurrentDetails()['plugin']);
    }

    /** @test */
    public function it_sends_message()
    {
        Http::fake([
            self::Endpoint => Http::response(self::SuccessResponse),
        ]);

        $this->janus->message(['message' => 'test']);

        Http::assertSent(function (Request $request) {
            return $request['janus'] === 'message'
                && $request['body']['message'] === 'test'
                && ! isset($request['jsep']);
        });
        $this->assertSame(self::SuccessResponse, $this->janus->server()->getApiResponse());
    }

    /** @test */
    public function it_sends_message_with_jsep()
    {
        Http::fake([
            self::Endpoint => Http::response(self::SuccessResponse),
        ]);

        $this->janus->message(['message' => 'test'], ['extra' => true]);

        Http::assertSent(function (Request $request) {
            return $request['janus'] === 'message'
                && $request['body']['message'] === 'test'
                && $request['jsep']['extra'] === true;
        });
        $this->assertSame(self::SuccessResponse, $this->janus->server()->getApiResponse());
    }

    /** @test */
    public function it_sends_trickle_candidate()
    {
        Http::fake([
            self::Endpoint => Http::response(self::SuccessResponse),
        ]);

        $this->janus->trickleCandidate('candidate');

        Http::assertSent(function (Request $request) {
            return $request['janus'] === 'trickle'
                && $request['candidate'] === 'candidate';
        });
        $this->assertSame(self::SuccessResponse, $this->janus->server()->getApiResponse());
    }
}
