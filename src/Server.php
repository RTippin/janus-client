<?php

namespace RTippin\Janus;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RTippin\Janus\Exceptions\JanusApiException;
use Throwable;

/**
 * @link https://janus.conf.meetecho.com/docs/rest.html
 */
class Server
{
    /**
     * @var string|null
     */
    private ?string $serverEndpoint;

    /**
     * @var string|null
     */
    private ?string $adminServerEndpoint;

    /**
     * @var string|null
     */
    private ?string $apiSecret;

    /**
     * @var bool
     */
    private bool $verifySSL;

    /**
     * @var bool
     */
    private bool $debug;

    /**
     * @var null|float
     */
    private ?float $latencyStart = null;

    /**
     * @var null|float
     */
    private ?float $latencyEnd = null;

    /**
     * @var null|int
     */
    private ?int $sessionId = null;

    /**
     * @var null|int
     */
    private ?int $handleId = null;

    /**
     * @var string|null
     */
    private ?string $plugin = null;

    /**
     * @var array|null
     */
    private ?array $apiPayload = null;

    /**
     * @var array|null
     */
    private ?array $apiResponse = null;

    /**
     * Server Constructor.
     */
    public function __construct()
    {
        $this->setServerEndpoint(config('janus.server_endpoint'))
            ->setAdminServerEndpoint(config('janus.admin_server_endpoint'))
            ->setVerifySSL(config('janus.verify_ssl'))
            ->setDebug(config('janus.debug'))
            ->setApiSecret(config('janus.api_secret'));
    }

    /**
     * Returns all properties and their current values.
     *
     * @return array
     */
    public function getCurrentDetails(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param  string|null  $serverEndpoint
     * @return $this
     */
    public function setServerEndpoint(?string $serverEndpoint): self
    {
        $this->serverEndpoint = rtrim($serverEndpoint, '/');

        return $this;
    }

    /**
     * @param  string|null  $adminServerEndpoint
     * @return $this
     */
    public function setAdminServerEndpoint(?string $adminServerEndpoint): self
    {
        $this->adminServerEndpoint = rtrim($adminServerEndpoint, '/');

        return $this;
    }

    /**
     * @param  string|null  $apiSecret
     * @return $this
     */
    public function setApiSecret(?string $apiSecret): self
    {
        $this->apiSecret = $apiSecret;

        return $this;
    }

    /**
     * @param  bool  $verifySSL
     * @return $this
     */
    public function setVerifySSL(bool $verifySSL): self
    {
        $this->verifySSL = $verifySSL;

        return $this;
    }

    /**
     * @param  bool  $debug
     * @return Server
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * @param  int|null  $sessionId
     * @return $this
     */
    public function setSessionId(?int $sessionId): self
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * @param  int|null  $handleId
     * @return $this
     */
    public function setHandleId(?int $handleId): self
    {
        $this->handleId = $handleId;

        return $this;
    }

    /**
     * @param  string|null  $plugin
     * @return $this
     */
    public function setPlugin(?string $plugin): self
    {
        $this->plugin = $plugin;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPlugin(): ?string
    {
        return $this->plugin;
    }

    /**
     * @return float|null
     */
    public function getEndLatency(): ?float
    {
        return $this->latencyEnd;
    }

    /**
     * @param  string|null  $key
     * @return mixed|null
     */
    public function getApiPayload(?string $key = null)
    {
        if (! is_null($key)) {
            return $this->apiPayload[$key] ?? null;
        }

        return $this->apiPayload;
    }

    /**
     * Get the response from a plugin.
     *
     * @return mixed|null
     */
    public function getPluginResponse(?string $key = null)
    {
        if (! is_null($key)) {
            return $this->apiResponse['plugindata']['data'][$key] ?? null;
        }

        return $this->apiResponse['plugindata']['data'] ?? null;
    }

    /**
     * Get the full api response.
     *
     * @param  string|null  $key
     * @return mixed|null
     */
    public function getApiResponse(?string $key = null)
    {
        if (! is_null($key)) {
            return $this->apiResponse[$key] ?? null;
        }

        return $this->apiResponse;
    }

    /**
     * Determine if we have an active session established.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return ! is_null($this->sessionId);
    }

    /**
     * Are we attached to a plugin?
     *
     * @return bool
     */
    public function isAttached(): bool
    {
        return $this->isConnected()
            && ! is_null($this->handleId)
            && ! is_null($this->plugin);
    }

    /**
     * Send a POST request to the janus gateway.
     *
     * @param  array  $data
     * @param  bool  $admin
     * @return array
     *
     * @throws JanusApiException
     */
    public function post(array $data, bool $admin = false): array
    {
        $this->apiResponse = null;
        $uri = $this->generateUri($admin);
        $this->apiPayload = array_merge([
            'transaction' => Str::random(12),
            'apisecret' => $this->apiSecret,
        ], $data);

        $this->startMicroTime();

        try {
            $response = Http::timeout(15)
                ->withOptions(['verify' => $this->verifySSL])
                ->post($uri, $this->apiPayload)
                ->throw()
                ->json();
        } catch (Throwable $e) {
            throw new JanusApiException('Janus POST failed', 0, $e);
        }

        $this->endMicroTime();

        $this->dumpIfDebugEnabled($response);

        $this->bailIfResponseHasJanusError($response, $uri);

        return $this->apiResponse = $response;
    }

    /**
     * Send a GET request to the janus gateway.
     *
     * @param  string|null  $route
     * @param  bool  $admin
     * @return array
     *
     * @throws JanusApiException
     */
    public function get(?string $route = null, bool $admin = false): array
    {
        $this->apiResponse = null;
        $this->apiPayload = null;
        $uri = $this->generateUri($admin, $route);

        $this->startMicroTime();

        try {
            $response = Http::timeout(15)
                ->withOptions(['verify' => $this->verifySSL])
                ->get($uri)
                ->throw()
                ->json();
        } catch (Throwable $e) {
            throw new JanusApiException('Janus GET failed', 0, $e);
        }

        $this->endMicroTime();

        $this->dumpIfDebugEnabled($response);

        $this->bailIfResponseHasJanusError($response, $uri);

        return $this->apiResponse = $response;
    }

    /**
     * Generate the URI we will use for this
     * request cycle to the janus gateway.
     *
     * @param  bool  $admin
     * @param  string|null  $route
     * @return string
     */
    private function generateUri(bool $admin = false, ?string $route = null): string
    {
        $server = $admin ? $this->adminServerEndpoint : $this->serverEndpoint;
        $route = $route ? '/'.$route : '';
        $session = $this->sessionId ? '/'.$this->sessionId : '';
        $handle = $this->handleId ? '/'.$this->handleId : '';

        return  $server.$route.$session.$handle;
    }

    /**
     * If janus gateway call succeeded, we will check the response
     * for janus specific errors, and bail if errors are found.
     *
     * @param  array  $response
     * @param  string  $uri
     *
     * @throws JanusApiException
     */
    private function bailIfResponseHasJanusError(array $response, string $uri): void
    {
        if (! isset($response['janus']) || $response['janus'] === 'error') {
            $response['api_payload'] = $this->apiPayload;

            throw new JanusApiException("Janus Error | $uri | ".json_encode($response));
        }
    }

    /**
     * Start micro timer for an API call.
     *
     * @return void
     */
    private function startMicroTime(): void
    {
        $this->latencyStart = microtime(true);
    }

    /**
     * Finish timer for an API call.
     *
     * @return void
     */
    private function endMicroTime(): void
    {
        $this->latencyEnd = round((microtime(true) - $this->latencyStart) * 1000);
    }

    /**
     * @param  array|null  $response
     */
    private function dumpIfDebugEnabled(?array $response): void
    {
        if ($this->debug) {
            dump('PAYLOAD', $this->apiPayload);
            dump('RESPONSE', $response);
            dump('LATENCY', $this->latencyEnd);
        }
    }
}
