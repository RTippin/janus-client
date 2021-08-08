<?php

namespace RTippin\Janus\Contracts;

use RTippin\Janus\Exceptions\JanusApiException;
use RTippin\Janus\Janus;

interface JanusPlugin
{
    /**
     * Get the fully qualified janus plugin name,
     * used for attaching request.
     *
     * @return string
     */
    public function getPluginName(): string;

    /**
     * Get the plugin's short name, used to
     * access plugin data responses.
     *
     * @return string
     */
    public function getPluginShortName(): string;

    /**
     * @return Janus
     */
    public function janus(): Janus;

    /**
     * @param string|null $key
     * @return mixed|null
     */
    public function getPluginPayload(?string $key = null);

    /**
     * Get the response from a plugin.
     *
     * @return mixed|null
     */
    public function getPluginResponse(?string $key = null);

    /**
     * If you want to call to multiple methods within one request cycle, this
     * disables automatically disconnecting, resulting in less request in the
     * cycle to the janus server. When you are done, you must manually call to
     * the disconnect method with force set to true.
     *
     * @return $this
     */
    public function withoutDisconnect();

    /**
     * Disconnect from the server if enabled or forced.
     *
     * @param bool $force
     * @return $this
     * @throws JanusApiException
     */
    public function disconnect(bool $force = false);

    /**
     * Emit our message, initiating a connection/attachment if needed.
     *
     * @param array $message
     * @return $this
     * @throws JanusApiException
     */
    public function emit(array $message);
}
