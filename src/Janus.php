<?php

namespace RTippin\Janus;

use Illuminate\Contracts\Container\BindingResolutionException;
use RTippin\Janus\Exceptions\JanusApiException;
use RTippin\Janus\Plugins\VideoRoom;

/**
 * Laravel Janus Client - General purpose WebRTC server.
 * Created by: Richard Tippin.
 * @link https://janus.conf.meetecho.com/docs/
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
    }

    /**
     * Get the server client instance.
     *
     * @return Server
     */
    public function server(): Server
    {
        return $this->server;
    }

    /**
     * Get the current instance of Janus.
     *
     * @return $this
     */
    public function getInstance(): self
    {
        return $this;
    }

    /**
     * Get the VideoRoom plugin client instance.
     *
     * @return VideoRoom
     * @throws BindingResolutionException
     */
    public function videoRoom(): VideoRoom
    {
        return app()->makeWith(VideoRoom::class, ['janus' => $this]);
    }

    /**
     * Retrieve the janus server instance details.
     *
     * @return array
     * @throws JanusApiException
     */
    public function info(): array
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
        try {
            $this->server->post(['janus' => 'ping']);
        } catch (JanusApiException $e) {
            return [
                'pong' => false,
            ];
        }

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
     * @throws JanusApiException
     */
    public function connect(): self
    {
        $this->server->setSessionId(null)->post(['janus' => 'create']);

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
     * @throws JanusApiException
     */
    public function attach(string $plugin): self
    {
        $this->server->post([
            'janus' => 'attach',
            'plugin' => $plugin,
        ]);

        $handle = $this->server->getApiResponse('data')['id'] ?? null;

        $this->server
            ->setPlugin($handle ? $plugin : null)
            ->setHandleId($handle);

        return $this;
    }

    /**
     * Detach from the current plugin/handle.
     *
     * @return $this
     * @throws JanusApiException
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
     * @throws JanusApiException
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
     * @param string|array|null $jsep
     * @return $this
     * @throws JanusApiException
     */
    public function message(array $message, $jsep = null): self
    {
        $payload = [
            'janus' => 'message',
            'body' => $message,
        ];

        if (! is_null($jsep)) {
            $payload['jsep'] = $jsep;
        }

        $this->server->post($payload);

        return $this;
    }

    /**
     * Send janus our trickle.
     *
     * @param string|array $candidate
     * @return $this
     * @throws JanusApiException
     */
    public function trickleCandidate($candidate): self
    {
        $this->server->post([
            'janus' => 'trickle',
            'candidate' => $candidate,
        ]);

        return $this;
    }
}
