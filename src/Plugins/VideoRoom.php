<?php

namespace RTippin\Janus\Plugins;

use Illuminate\Support\Str;
use RTippin\Janus\Janus;

class VideoRoom
{
    /**
     * String name of janus plugin we attach to
     * https://janus.conf.meetecho.com/docs/videoroom.html.
     */
    const PLUGIN = 'janus.plugin.videoroom';

    /**
     * @var Janus
     */
    private Janus $janus;

    /**
     * @var string|null
     */
    private ?string $adminKey;

    /**
     * @var bool
     */
    private bool $shouldDisconnect = true;

    /**
     * VideoRoom constructor.
     *
     * @param Janus $janus
     */
    public function __construct(Janus $janus)
    {
        $this->janus = $janus;
        $this->setAdminKey(config('janus.video_room_secret'));
    }

    /**
     * @return Janus
     */
    public function janus(): Janus
    {
        return $this->janus;
    }

    /**
     * @param string|null $adminKey
     * @return $this
     */
    public function setAdminKey(?string $adminKey): self
    {
        $this->adminKey = $adminKey;

        return $this;
    }

    /**
     * If you want to call to multiple methods within one request cycle, this
     * disables automatically disconnecting, resulting in less request in the
     * cycle to the janus server. When you are done, you must manually call to
     * the disconnect method with force set to true.
     *
     * @return $this
     */
    public function withoutDisconnect(): self
    {
        $this->shouldDisconnect = false;

        return $this;
    }

    /**
     * List all Video Rooms we have in this janus server.
     *
     * @return array|null
     */
    public function list(): ?array
    {
        $this->emit(['request' => 'list']);

        if (! $this->isValidPluginResponse()) {
            //TODO.
        }

        $list = $this->janus->server()->getPluginResponse('list');

        $this->disconnect();

        return $list;
    }

    /**
     * Check if janus has a video room with ID.
     *
     * @param int $room
     * @return bool
     */
    public function exists(int $room): bool
    {
        $this->emit([
            'request' => 'exists',
            'room' => $room,
        ]);

        if (! $this->isValidPluginResponse()) {
            //TODO.
        }

        $exists = $this->janus->server()->getPluginResponse('exists') ?? false;

        $this->disconnect();

        return $exists;
    }

    /**
     * Create a new video room, overriding properties you want to set.
     *
     * @param array $params
     * @param bool $usePin
     * @param bool $useSecret
     * @return array|null
     */
    public function create(array $params = [],
                           bool $usePin = true,
                           bool $useSecret = true): ?array
    {
        $payload = array_merge([
            'request' => 'create',
            'publishers' => 2,
            'description' => Str::random(10),
            'audiolevel_event' => true,
            'audiolevel_ext' => true,
            'audio_active_packets' => 50,
            'audio_level_average' => 25,
            'notify_joining' => true,
            'bitrate' => 600000,
            'pin' => $usePin ? Str::random(6) : '',
            'secret' => $useSecret ? Str::random(12) : '',
            'admin_key' => $this->adminKey,
        ], $params);

        $this->emit($payload);

        $room = $this->janus->server()->getPluginResponse('room');
        $success = $this->isValidPluginResponse('created');

        if (! $success) {
            //TODO.
        }

        $this->disconnect();

        return $success ? [
                'room' => $room,
                'pin' => $payload['pin'] ?: null,
                'secret' => $payload['secret'] ?: null,
            ] : null;
    }

    /**
     * Edit an existing video room's properties.
     *
     * @param int $room
     * @param array $params
     * @param string|null $secret
     * @return bool
     */
    public function edit(int $room, array $params, ?string $secret = null): bool
    {
        $payload = array_merge([
            'request' => 'edit',
            'room' => $room,
            'secret' => $secret ?: '',
        ], $params);

        $this->emit($payload);

        $success = $this->isValidPluginResponse('edited');

        if (! $success) {
            //TODO.
        }

        $this->disconnect();

        return $success;
    }

    /**
     * Configure whether to check tokens or add/remove people who can join a room.
     *
     * @param int $room
     * @param string $action
     * @param array $allowed
     * @param string|null $secret
     * @return array|null
     */
    public function allowed(int $room,
                            string $action,
                            array $allowed,
                            ?string $secret = null): ?array
    {
        $this->emit([
            'request' => 'allowed',
            'room' => $room,
            'action' => $action,
            'secret' => $secret ?: '',
            'allowed' => $allowed,
        ]);

        $response = $this->janus->server()->getPluginResponse();

        if (! $this->isValidPluginResponse()) {
            //TODO.
        }

        $this->disconnect();

        return $response;
    }

    /**
     * Kick a participant from a room using their private janus participant ID.
     *
     * @param int $room
     * @param int $participantID
     * @param string|null $secret
     * @return bool
     */
    public function kick(int $room,
                         int $participantID,
                         ?string $secret = null): bool
    {
        $this->emit([
            'request' => 'kick',
            'room' => $room,
            'secret' => $secret ?: '',
            'id' => $participantID,
        ]);

        $success = $this->isValidPluginResponse();

        if (! $success) {
            //TODO.
        }

        return $success;
    }

    /**
     * Get a list of the participants in a specific room.
     *
     * @param int $room
     * @return array|null
     */
    public function listParticipants(int $room): ?array
    {
        $this->emit([
            'request' => 'listparticipants',
            'room' => $room,
        ]);

        $participants = $this->janus->server()->getPluginResponse('participants');

        if (! $this->isValidPluginResponse('participants')) {
            //TODO.
        }

        $this->disconnect();

        return $participants;
    }

    /**
     * Destroy a room given the room ID and optional secret.
     *
     * @param int $room
     * @param string|null $secret
     * @return bool
     */
    public function destroy(int $room, ?string $secret = null): bool
    {
        $this->emit([
            'request' => 'destroy',
            'room' => $room,
            'secret' => $secret ?: '',
        ]);

        $success = $this->isValidPluginResponse('destroyed');

        if (! $success) {
            //TODO.
        }

        $this->disconnect();

        return $success;
    }

    /**
     * Disconnect from the server if enabled or forced.
     *
     * @param bool $force
     * @return $this
     */
    public function disconnect(bool $force = false): self
    {
        if ($this->shouldDisconnect || $force) {
            $this->janus->disconnect();
        }

        return $this;
    }

    /**
     * Emit our message, initiating a connection/attachment if needed.
     *
     * @param array $message
     */
    private function emit(array $message): void
    {
        if ($this->janus->server()->isAttached()
            && $this->janus->server()->getPlugin() === self::PLUGIN) {
            $this->janus->message($message);

            return;
        }

        $this->janus
            ->connect()
            ->attach(self::PLUGIN)
            ->message($message);
    }

    /**
     * Check if the plugin response we expect is valid.
     *
     * @param string $success
     * @return bool
     */
    private function isValidPluginResponse(string $success = 'success'): bool
    {
        return $this->janus->server()->getPluginResponse('videoroom') === $success;
    }
}
