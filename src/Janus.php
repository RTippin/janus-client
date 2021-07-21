<?php

namespace RTippin\Janus;

use Illuminate\Support\Str;

class Janus
{
    /**
     * @var JanusServer
     */
    private JanusServer $server;

    /**
     * Janus constructor.
     */
    public function __construct(JanusServer $server)
    {
        $this->server = $server;
    }

    /**
     * Retrieve the janus server instance details.
     *
     * @return array
     */
    public function serverInfo(): array
    {
        $this->janusAPI(null, 'info', false, false);

        return $this->apiResponse;
    }

    /**
     * Ping janus to see if it is alive.
     *
     * @return array
     */
    public function serverPing(): array
    {
        $this->janusAPI([
            'janus' => 'ping',
            'transaction' => Str::random(12),
        ], null, true);

        if (isset($this->apiResponse['janus'])
            && $this->apiResponse['janus'] === 'pong') {
            return [
                'pong' => true,
                'latency' => $this->lastLatency,
                'message' => $this->lastLatency.' milliseconds',
            ];
        }

        return [
            'pong' => false,
        ];
    }

    /**
     * Connect with janus to set the session ID for this cycle.
     *
     * @return $this
     */
    public function connect(): self
    {
        $this->janusAPI([
            'janus' => 'create',
            'transaction' => Str::random(12),
            'apisecret' => $this->apiSecret,
        ]);

        $this->sessionId = $this->apiResponse['data']['id'] ?? null;

        return $this;
    }

    /**
     * Attach to the janus plugin to get a handle ID. All request
     * in this cycle will go to this plugin unless you call detach.
     *
     * @param string $plugin
     * @return $this
     */
    public function attach(string $plugin): self
    {
        $this->plugin = $plugin;

        if (! $this->sessionId || $this->handleId) {
            return $this;
        }

        $this->janusAPI([
            'janus' => 'attach',
            'plugin' => $plugin,
            'transaction' => Str::random(12),
            'apisecret' => $this->apiSecret,
        ]);

        if (isset($this->apiResponse['data']['id'])) {
            $this->handleId = $this->apiResponse['data']['id'];
        } else {
            $this->handleId = null;
        }

        return $this;
    }

    /**
     * Detach from the current plugin/handle.
     *
     * @return $this
     */
    public function detach(): self
    {
        if (! $this->handleId) {
            return $this;
        }

        $this->janusAPI([
            'janus' => 'detach',
            'transaction' => Str::random(12),
            'apisecret' => $this->apiSecret,
        ]);

        $this->handleId = null;

        return $this;
    }

    /**
     * Disconnect from janus, destroying our session and handle/plugin.
     *
     * @return $this
     */
    public function disconnect(): self
    {
        $this->handleId = null;

        if (! $this->sessionId) {
            return $this;
        }

        $this->janusAPI([
            'janus' => 'destroy',
            'transaction' => Str::random(12),
            'apisecret' => $this->apiSecret,
        ]);

        $this->sessionId = null;

        return $this;
    }

    /**
     * Send janus our message to the plugin.
     *
     * @param array $message
     * @param string|null $jsep
     * @return $this
     */
    public function sendMessage(array $message, string $jsep = null): self
    {
        $this->pluginPayload = [
            'janus' => 'message',
            'body' => $message,
            'transaction' => Str::random(12),
            'apisecret' => $this->apiSecret,
        ];

        if ($jsep) {
            array_push($this->pluginPayload, ['jsep' => $jsep]);
        }

        if (! $this->sessionId
            || ! $this->handleId
            || ! $this->plugin) {
            $this->pluginResponse = [];

            return $this;
        }

        $this->janusAPI($this->pluginPayload)->setPluginResponse();

        return $this;
    }

    /**
     * Send janus our trickle.
     * @param string $candidate
     * @return $this
     */
    public function sendTrickleCandidate(string $candidate): self
    {
        if (! $this->sessionId || ! $this->handleId) {
            $this->pluginResponse = [];
            $this->pluginPayload = [];

            return $this;
        }

        $this->pluginPayload = [
            'janus' => 'trickle',
            'candidate' => $candidate,
            'transaction' => Str::random(12),
            'apisecret' => $this->apiSecret,
        ];

        $this->janusAPI($this->pluginPayload)->setPluginResponse();

        return $this;
    }
}
