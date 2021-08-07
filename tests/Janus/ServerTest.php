<?php

namespace RTippin\Janus\Tests\Janus;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RTippin\Janus\Exceptions\JanusApiException;
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
        $this->assertNull($this->server->getPlugin());
    }

    /** @test */
    public function it_is_attached_to_a_plugin()
    {
        $this->server->setPlugin('plugin')->setHandleId('handle');

        $this->assertTrue($this->server->isAttached());
        $this->assertSame('plugin', $this->server->getPlugin());
    }

    /** @test */
    public function it_throws_exception_if_post_fails()
    {
        Http::fake([
            self::Endpoint => Http::response(null, 500),
        ]);

        $this->expectException(JanusApiException::class);
        $this->expectExceptionMessage('Janus POST failed');

        $this->server->post([]);
    }

    /** @test */
    public function it_throws_exception_if_get_fails()
    {
        Http::fake([
            self::Endpoint => Http::response(null, 500),
        ]);

        $this->expectException(JanusApiException::class);
        $this->expectExceptionMessage('Janus GET failed');

        $this->server->get();
    }

    /** @test */
    public function it_throws_exception_if_post_response_missing_janus()
    {
        Http::fake([
            self::Endpoint => Http::response([]),
        ]);

        $this->expectException(JanusApiException::class);

        $this->server->post([]);
    }

    /** @test */
    public function it_throws_exception_if_get_response_missing_janus()
    {
        Http::fake([
            self::Endpoint => Http::response([]),
        ]);

        $this->expectException(JanusApiException::class);

        $this->server->get();
    }

    /** @test */
    public function it_throws_exception_if_post_response_janus_errors()
    {
        Http::fake([
            self::Endpoint => Http::response(['janus' => 'error']),
        ]);

        $this->expectException(JanusApiException::class);

        $this->server->post([]);
    }

    /** @test */
    public function it_throws_exception_if_get_response_janus_errors()
    {
        Http::fake([
            self::Endpoint => Http::response(['janus' => 'error']),
        ]);

        $this->expectException(JanusApiException::class);

        $this->server->get();
    }

    /** @test */
    public function it_post_and_returns_set_data()
    {
        Http::fake([
            self::Endpoint => Http::response(self::SuccessResponse),
        ]);

        $response = $this->server->post(['data' => true]);

        $this->assertSame(self::SuccessResponse, $this->server->getApiResponse());
        $this->assertSame(self::SuccessResponse, $response);
        $this->assertArrayHasKey('data', $this->server->getApiPayload());
    }

    /** @test */
    public function it_get_request_and_returns_set_data()
    {
        Http::fake([
            self::Endpoint => Http::response(self::SuccessResponse),
        ]);

        $response = $this->server->get();

        $this->assertSame(self::SuccessResponse, $this->server->getApiResponse());
        $this->assertSame(self::SuccessResponse, $response);
        $this->assertNull($this->server->getApiPayload());
    }

    /** @test */
    public function it_post_and_merges_api_secret_and_transaction_with_data()
    {
        Http::fake([
            self::Endpoint => Http::response(self::SuccessResponse),
        ]);

        $this->server->post(['data' => true]);

        Http::assertSent(function (Request $request) {
            return $request['apisecret'] === self::ApiSecret
                && strlen($request['transaction']) === 12
                && $request['data'] === true;
        });
        $this->assertSame(self::SuccessResponse, $this->server->getApiResponse());
    }

    /** @test */
    public function it_can_post_to_admin_endpoint()
    {
        Http::fake([
            self::AdminEndpoint => Http::response(self::SuccessResponse),
        ]);

        $this->server->post(['data' => true], true);

        $this->assertSame(self::SuccessResponse, $this->server->getApiResponse());
    }

    /** @test */
    public function it_can_get_request_specified_endpoint()
    {
        Http::fake([
            self::Endpoint.'/test' => Http::response(self::SuccessResponse),
        ]);

        $this->server->get('test');

        $this->assertSame(self::SuccessResponse, $this->server->getApiResponse());
    }

    /** @test */
    public function it_can_get_request_to_admin_endpoint()
    {
        Http::fake([
            self::AdminEndpoint => Http::response(self::SuccessResponse),
        ]);

        $this->server->get(null, true);

        $this->assertSame(self::SuccessResponse, $this->server->getApiResponse());
    }

    /** @test */
    public function it_sets_latency_on_post_request()
    {
        Http::fake([
            self::Endpoint => Http::response(self::SuccessResponse),
        ]);

        $this->server->post([]);

        $this->assertIsFloat($this->server->getEndLatency());
    }

    /** @test */
    public function it_sets_latency_on_get_request()
    {
        Http::fake([
            self::Endpoint => Http::response(self::SuccessResponse),
        ]);

        $this->server->get();

        $this->assertIsFloat($this->server->getEndLatency());
    }
}
