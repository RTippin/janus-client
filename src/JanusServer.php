<?php

namespace RTippin\Janus;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Janus Media Server REST interface
 * https://janus.conf.meetecho.com/docs/rest.html.
 */
class JanusServer
{
    /**
     * @var string
     */
    private string $janusServerEndpoint;

    /**
     * @var string
     */
    private string $janusAdminServerEndpoint;

    /**
     * @var string
     */
    private string $apiSecret;

    /**
     * @var bool
     */
    private bool $selfSigned;

    /**
     * @var null|int|float
     */
    private $lastLatency = null;

    /**
     * @var null|string
     */
    private ?string $sessionId = null;

    /**
     * @var null|string
     */
    private ?string $handleId = null;

    /**
     * @var null|string
     */
    private ?string $plugin = null;

    /**
     * @var array
     */
    private array $apiResponse = [];

    /**
     * @var array
     */
    private array $pluginPayload = [];

    /**
     * @var array
     */
    private array $pluginResponse = [];

    /**
     * JanusServer constructor.
     */
    public function __construct()
    {
        $this->apiSecret = config('janus.api_secret');
        $this->janusServerEndpoint = config('janus.server_endpoint');
        $this->janusAdminServerEndpoint = config('janus.server_admin_endpoint');
        $this->selfSigned = config('janus.backend_ssl');
    }

    /**
     * Log an API error if logging enabled.
     *
     * @param null $data
     * @param null $route
     * @return void
     */
    private function logApiError($data = null, $route = null): void
    {
//        if ($this->logErrors) {
//            Log::warning('janus.api', [
//                'payload' => $data,
//                'route' => $route,
//                'response' => $this->apiResponse,
//            ]);
//        }
    }

    /**
     * Log error from the loaded plugin method if logging enabled.
     *
     * @param string $action
     * @param array $extra
     * @return void
     */
    public function logPluginError(string $action = '', array $extra = []): void
    {
//        if ($this->logErrors) {
//            Log::warning($this->plugin.' - '.$action, [
//                'payload' => $this->pluginPayload,
//                'response' => $this->apiResponse,
//                'extra' => $extra,
//            ]);
//        }
    }

    /**
     * Set the Janus configs defaulted in the constructor and class properties
     * Use property name as key => value.
     *
     * @param array|null $config
     * @return $this
     */
    public function setConfig(array $config = null): self
    {
        if ($config && count($config)) {
            foreach ($config as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->{$key} = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Return the response from a plugin.
     *
     * @return array
     */
    public function getPluginResponse(): array
    {
        return $this->pluginResponse;
    }

    /**
     * Make POST/GET to janus, append session or handle ID if they exist.
     *
     * @param array $data
     * @param string|null $route
     * @param bool $admin
     * @param bool $post
     * @return $this
     */
    private function janusAPI(array $data = [],
                              string $route = null,
                              bool $admin = false,
                              bool $post = true): self
    {
        if (! $this->janusServerEndpoint) {
            return $this;
        }

        $client = Http::withOptions([
            'verify' => $this->selfSigned,
            'timeout' => 30,
        ]);

        $server = $admin ? $this->janusAdminServerEndpoint : $this->janusServerEndpoint;
        $route = $route ? '/'.$route : '';
        $session = $this->sessionId ? '/'.$this->sessionId : '';
        $handle = $this->handleId ? '/'.$this->handleId : '';
        $uri = $server.$route.$session.$handle;

        try {
            $this->trackServerLatency();

            $response = $post
                ? $client->post($uri, $data)
                : $client->get($uri);

            $this->reportServerLatency();

            $this->apiResponse = $response->successful()
                ? $response->json()
                : [];
        } catch (Throwable $e) {
            report($e);
            $this->apiResponse = [];
        }

        if (! isset($this->apiResponse['janus'])
            || $this->apiResponse['janus'] === 'error') {
            $this->logApiError($data, $uri);
        }

        return $this;
    }

    /**
     * Called after plugin message to extract plugin data response.
     *
     * @return void
     */
    private function setPluginResponse(): void
    {
        if (isset($this->apiResponse['plugindata']['plugin'])
            && $this->apiResponse['plugindata']['plugin'] === $this->plugin
            && isset($this->apiResponse['plugindata']['data'])) {
            $this->pluginResponse = $this->apiResponse['plugindata']['data'];
        } else {
            $this->pluginResponse = [];
        }
    }

    /**
     * Start micro timer for API interaction.
     *
     * @return void
     */
    private function trackServerLatency(): void
    {
        $this->pingPong = microtime(true);
    }

    /**
     * Finish and calculate milliseconds for API call.
     *
     * @return void
     */
    private function reportServerLatency(): void
    {
        if ($this->pingPong) {
            $this->lastLatency = round((microtime(true) - $this->pingPong) * 1000);
            $this->pingPong = null;
        }
    }
}
