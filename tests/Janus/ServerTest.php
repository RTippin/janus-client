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
    public function it_returns_default_server_details()
    {
        $expected = [
            'serverEndpoint' => 'http://janus.test',
            'adminServerEndpoint' => 'http://janus.test/admin',
            'apiSecret' => 'api-secret',
            'verifySSL' => false,
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
    public function it_is_not_connected()
    {
        $this->assertFalse($this->server->isConnected());
    }

    /** @test */
    public function it_is_connected()
    {
        $this->server->setSessionId('session');

        $this->assertTrue($this->server->isConnected());
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
        $this->server
            ->setPlugin('plugin')
            ->setSessionId('session')
            ->setHandleId('handle');

        $this->assertTrue($this->server->isAttached());
        $this->assertSame('plugin', $this->server->getPlugin());
    }

    /** @test */
    public function it_has_null_api_payload_by_default()
    {
        $this->assertNull($this->server->getApiPayload());
    }

    /** @test */
    public function it_has_null_api_response_by_default()
    {
        $this->assertNull($this->server->getApiResponse());
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
    public function it_sends_post_and_returns_set_data()
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
    public function it_sends_get_request_and_returns_set_data()
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
    public function it_sends_post_and_merges_api_secret_and_transaction_with_data()
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
    public function it_can_send_post_to_admin_endpoint()
    {
        Http::fake([
            self::AdminEndpoint => Http::response(self::SuccessResponse),
        ]);

        $this->server->post(['data' => true], true);

        $this->assertSame(self::SuccessResponse, $this->server->getApiResponse());
    }

    /** @test */
    public function it_can_send_get_request_specified_endpoint()
    {
        Http::fake([
            self::Endpoint.'/test' => Http::response(self::SuccessResponse),
        ]);

        $this->server->get('test');

        $this->assertSame(self::SuccessResponse, $this->server->getApiResponse());
    }

    /** @test */
    public function it_can_send_get_request_to_admin_endpoint()
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

    /** @test */
    public function it_uses_session_in_uri_when_set()
    {
        $this->server->setSessionId('1234');
        Http::fake([
            self::Endpoint.'/1234' => Http::response(self::SuccessResponse),
        ]);

        $this->server->post([]);

        Http::assertSent(function (Request $request) {
            return $request->url() === self::Endpoint.'/1234';
        });
    }

    /** @test */
    public function it_uses_session_and_handle_in_uri_when_set()
    {
        $this->server->setSessionId('1234')->setHandleId('5678');
        Http::fake([
            self::Endpoint.'/1234/5678' => Http::response(self::SuccessResponse),
        ]);

        $this->server->post([]);

        Http::assertSent(function (Request $request) {
            return $request->url() === self::Endpoint.'/1234/5678';
        });
    }

    /** @test */
    public function it_returns_entire_api_response()
    {
        $response = array_merge(self::SuccessResponse, ['data' => 'test']);
        Http::fake([
            self::Endpoint => Http::response($response),
        ]);

        $this->server->post([]);

        $this->assertSame($response, $this->server->getApiResponse());
    }

    /** @test */
    public function it_returns_api_response_key_data()
    {
        $response = array_merge(self::SuccessResponse, ['data' => 'test']);
        Http::fake([
            self::Endpoint => Http::response($response),
        ]);

        $this->server->post([]);

        $this->assertSame('test', $this->server->getApiResponse('data'));
    }

    /** @test */
    public function it_returns_null_api_response_if_data_key_missing()
    {
        $response = array_merge(self::SuccessResponse, ['data' => 'test']);
        Http::fake([
            self::Endpoint => Http::response($response),
        ]);

        $this->server->post([]);

        $this->assertNull($this->server->getApiResponse('unknown'));
    }

    /** @test */
    public function it_returns_entire_api_payload()
    {
        $payload = ['testing' => true];
        Http::fake([
            self::Endpoint => Http::response(self::SuccessResponse),
        ]);

        $this->server->post($payload);

        $this->assertArrayHasKey('testing', $this->server->getApiPayload());
    }

    /** @test */
    public function it_returns_api_payload_key_data()
    {
        $payload = ['testing' => 'janus'];
        Http::fake([
            self::Endpoint => Http::response(self::SuccessResponse),
        ]);

        $this->server->post($payload);

        $this->assertSame('janus', $this->server->getApiPayload('testing'));
    }

    /** @test */
    public function it_returns_null_api_payload_if_data_key_missing()
    {
        $payload = ['testing' => 'janus'];
        Http::fake([
            self::Endpoint => Http::response(self::SuccessResponse),
        ]);

        $this->server->post($payload);

        $this->assertNull($this->server->getApiPayload('unknown'));
    }

    /** @test */
    public function it_returns_entire_plugin_response()
    {
        $plugin = [
            'plugin' => 'success',
            'test' => true,
        ];
        $response = array_merge(self::SuccessResponse, [
            'data' => 'test',
            'plugindata' => [
                'data' => $plugin,
            ],
        ]);
        Http::fake([
            self::Endpoint => Http::response($response),
        ]);

        $this->server->post([]);

        $this->assertSame($plugin, $this->server->getPluginResponse());
    }

    /** @test */
    public function it_returns_plugin_response_key_data()
    {
        $response = array_merge(self::SuccessResponse, [
            'data' => 'test',
            'plugindata' => [
                'data' => [
                    'plugin' => 'success',
                    'test' => true,
                ],
            ],
        ]);
        Http::fake([
            self::Endpoint => Http::response($response),
        ]);

        $this->server->post([]);

        $this->assertSame('success', $this->server->getPluginResponse('plugin'));
    }

    /** @test */
    public function it_returns_null_plugin_response_if_key_missing()
    {
        $response = array_merge(self::SuccessResponse, [
            'data' => 'test',
            'plugindata' => [
                'data' => [
                    'plugin' => 'success',
                    'test' => true,
                ],
            ],
        ]);
        Http::fake([
            self::Endpoint => Http::response($response),
        ]);

        $this->server->post([]);

        $this->assertNull($this->server->getPluginResponse('unknown'));
    }

    /** @test */
    public function it_returns_updated_server_details()
    {
        $payload = ['data' => true];
        $response = array_merge(self::SuccessResponse, ['data' => 'test']);
        $this->server
            ->setSessionId('1234')
            ->setHandleId('5678')
            ->setPlugin('plugin');
        Http::fake([
            self::Endpoint.'/1234/5678' => Http::response($response),
        ]);

        $this->server->post($payload);

        $this->assertSame('1234', $this->server->getCurrentDetails()['sessionId']);
        $this->assertSame('5678', $this->server->getCurrentDetails()['handleId']);
        $this->assertSame('plugin', $this->server->getCurrentDetails()['plugin']);
        $this->assertArrayHasKey('data', $this->server->getCurrentDetails()['apiPayload']);
        $this->assertSame($response, $this->server->getCurrentDetails()['apiResponse']);
    }
}
