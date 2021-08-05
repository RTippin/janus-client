<?php

namespace RTippin\Janus;

/**
 * Laravel Janus Media Server REST interface client.
 * Created by: Richard Tippin.
 * @link https://janus.conf.meetecho.com/docs/rest.html.
 */
class Janus
{
    /**
     * @var Server
     */
    private Server $server;

    /**
     * Janus constructor.
     */
    public function __construct(Server $server)
    {
        $this->server = $server;

        $this->server->setServerEndpoint(config('janus.server_endpoint'))
            ->setAdminServerEndpoint(config('janus.server_admin_endpoint'))
            ->setSelfSigned(config('janus.backend_ssl'))
            ->setApiSecret(config('janus.api_secret'));
    }

    /**
     * @return Server
     */
    public function server(): Server
    {
        return $this->server;
    }

    /**
     * Retrieve the janus server instance details.
     *
     * @return array|null
     */
    public function info(): ?array
    {
        return $this->server->get('info');
    }

    /**
     * Ping janus to see if it is alive.
     *
     * @return array
     */
    public function ping(): array
    {
        $this->server->post(['janus' => 'ping']);

        if ($this->server->getApiResponse('janus') === 'pong') {
            return [
                'pong' => true,
                'latency' => $this->server->getEndLatency(),
            ];
        }

        return [
            'pong' => false,
        ];
    }

    /**
     * Connect with janus to set the session ID for this request cycle.
     *
     * @return $this
     */
    public function connect(): self
    {
        $this->server->post(['janus' => 'create']);

        $this->server->setSessionId(
            $this->server->getApiResponse('data')['id'] ?? null
        );

        return $this;
    }

    /**
     * Attach to the janus plugin to get a handle ID. All api calls in this
     * request cycle will go to this plugin unless you call detach.
     *
     * @param string $plugin
     * @return $this
     */
    public function attach(string $plugin): self
    {
        $this->server->setPlugin($plugin)->post([
            'janus' => 'attach',
            'plugin' => $plugin,
        ]);

        $this->server->setHandleId(
            $this->server->getApiResponse('data')['id'] ?? null
        );

        return $this;
    }

    /**
     * Detach from the current plugin/handle.
     *
     * @return $this
     */
    public function detach(): self
    {
        $this->server->post(['janus' => 'detach']);

        $this->server->setPlugin(null)->setHandleId(null);

        return $this;
    }

    /**
     * Disconnect from janus, destroying our session and handle/plugin.
     *
     * @return $this
     */
    public function disconnect(): self
    {
        $this->server->setPlugin(null)->setHandleId(null);

        $this->server->post(['janus' => 'destroy']);

        $this->server->setSessionId(null);

        return $this;
    }

    /**
     * Send janus our message.
     *
     * @param array $message
     * @param string|null $jsep
     * @return $this
     */
    public function message(array $message, ?string $jsep = null): self
    {
        $payload = [
            'janus' => 'message',
            'body' => $message,
        ];

        if (! is_null($jsep)) {
            array_push($payload, ['jsep' => $jsep]);
        }

        $this->server->post($payload);

        return $this;
    }

    /**
     * Send janus our trickle.
     * @param string $candidate
     * @return $this
     */
    public function trickleCandidate(string $candidate): self
    {
        $this->server->post([
            'janus' => 'trickle',
            'candidate' => $candidate,
        ]);

        return $this;
    }
}
