<?php

namespace RTippin\Janus;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Server
{
    /**
     * @var string
     */
    private string $serverEndpoint;

    /**
     * @var string
     */
    private string $adminServerEndpoint;

    /**
     * @var string
     */
    private string $apiSecret;

    /**
     * @var bool
     */
    private bool $selfSigned;

    /**
     * @var null|float
     */
    private ?float $latencyStart = null;

    /**
     * @var null|float
     */
    private ?float $latencyEnd = null;

    /**
     * @var null|string
     */
    private ?string $sessionId = null;

    /**
     * @var null|string
     */
    private ?string $handleId = null;

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
     * @param string $serverEndpoint
     * @return $this
     */
    public function setServerEndpoint(string $serverEndpoint): self
    {
        $this->serverEndpoint = $serverEndpoint;

        return $this;
    }

    /**
     * @param string $adminServerEndpoint
     * @return $this
     */
    public function setAdminServerEndpoint(string $adminServerEndpoint): self
    {
        $this->adminServerEndpoint = $adminServerEndpoint;

        return $this;
    }

    /**
     * @param string $apiSecret
     * @return $this
     */
    public function setApiSecret(string $apiSecret): self
    {
        $this->apiSecret = $apiSecret;

        return $this;
    }

    /**
     * @param bool $selfSigned
     * @return $this
     */
    public function setSelfSigned(bool $selfSigned): self
    {
        $this->selfSigned = $selfSigned;

        return $this;
    }

    /**
     * @param string|null $sessionId
     * @return $this
     */
    public function setSessionId(?string $sessionId): self
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * @param string|null $handleId
     * @return $this
     */
    public function setHandleId(?string $handleId): self
    {
        $this->handleId = $handleId;

        return $this;
    }

    /**
     * @param string|null $plugin
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
     * @return array|null
     */
    public function getApiPayload(): ?array
    {
        return $this->apiPayload;
    }

    /**
     * Return the response from a plugin.
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
     * @param string|null $key
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
     * @return bool
     */
    public function isAttached(): bool
    {
        return ! is_null($this->handleId) && ! is_null($this->plugin);
    }

    /**
     * @param array $data
     * @param bool $admin
     * @return array|null
     */
    public function post(array $data, bool $admin = false): ?array
    {
        $this->apiResponse = null;
        $this->apiPayload = array_merge([
            'transaction' => Str::random(12),
            'apisecret' => $this->apiSecret,
        ], $data);
        $uri = $this->generateUri($admin);

        $this->startMicroTime();

        $response = Http::timeout(15)
            ->withOptions(['verify' => $this->selfSigned])
            ->post($uri, $this->apiPayload);

        $this->endMicroTime();

        if ($response->successful()) {
            $this->apiResponse = $response->json();
        }

        $this->checkForResponseError($uri);

        return $this->apiResponse;
    }

    /**
     * @param string|null $route
     * @param bool $admin
     * @return array|null
     */
    public function get(?string $route = null, bool $admin = false): ?array
    {
        $this->apiResponse = null;
        $this->apiPayload = null;
        $uri = $this->generateUri($admin, $route);

        $this->startMicroTime();

        $response = Http::timeout(15)
            ->withOptions(['verify' => $this->selfSigned])
            ->get($uri);

        $this->endMicroTime();

        if ($response->successful()) {
            $this->apiResponse = $response->json();
        }

        $this->checkForResponseError($uri);

        return $this->apiResponse;
    }

    /**
     * @param bool $admin
     * @param string|null $route
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
     * @param string $uri
     */
    private function checkForResponseError(string $uri): void
    {
//        if (! isset($this->apiResponse['janus'])
//            || $this->apiResponse['janus'] === 'error') {
//            //TODO.
//        }
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
}
