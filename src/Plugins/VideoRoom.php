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
     * Create a new video room, overriding params you want to set.
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

        if (! $this->isValidPluginResponse('created')) {
            //TODO.

            $this->disconnect();

            return null;
        }

        $this->disconnect();

        return [
            'room' => $this->janus->server()->getPluginResponse('room'),
            'pin' => $payload['pin'] ?: null,
            'secret' => $payload['secret'] ?: null,
        ];
    }

    /**
     * Edit params of a given video room.
     *
     * @param int $room
     * @param array $params
     * @return bool
     */
    public function edit(int $room, array $params = []): bool
    {
        $edit = [
            'request' => 'edit',
            'room' => $room,
            'secret' => '',
        ];

        if (count($params)) {
            foreach ($params as $key => $value) {
                $edit[$key] = $value;
            }
        }

        $this->setup()->sendMessage($edit)->disconnect();

        if ($this->isValidPluginResponse('edited')) {
            return true;
        }

        $this->janus->logPluginError('edit');

        return false;
    }

    /**
     * Configure whether to check tokens or add/remove people who can join a room.
     *
     * @param int $room
     * @param string $action
     * @param array $allowed
     * @param string|null $secret
     * @return array
     */
    public function allowed(int $room,
                            string $action,
                            array $allowed = [],
                            string $secret = null): array
    {
        $this->setup()->sendMessage([
            'request' => 'allowed',
            'room' => $room,
            'action' => $action,
            'secret' => $secret ?? '',
            'allowed' => $allowed,
        ])
        ->disconnect();

        if ($this->isValidPluginResponse()) {
            return $this->janus->getPluginResponse();
        }

        $this->janus->logPluginError('allowed');

        return [];
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
                         string $secret = null): bool
    {
        $this->setup()->sendMessage([
            'request' => 'kick',
            'room' => $room,
            'secret' => $secret ?? '',
            'id' => $participantID,
        ])->disconnect();

        if ($this->isValidPluginResponse()) {
            return true;
        }

        $this->janus->logPluginError('kick');

        return false;
    }

    /**
     * Get a list of the participants in a specific room.
     * @param int $room
     * @return array
     */
    public function listParticipants(int $room): array
    {
        $this->setup()->sendMessage([
            'request' => 'listparticipants',
            'room' => $room,
        ])->disconnect();

        if ($this->isValidPluginResponse('participants')) {
            return $this->janus->getPluginResponse()['participants'];
        }

        $this->janus->logPluginError('listparticipants');

        return [];
    }

    /**
     * Tell janus to destroy a room given the room ID and secret.
     *
     * @param int|null $room
     * @param string|null $secret
     * @return bool
     */
    public function destroy(int $room = null, ?string $secret = null): bool
    {
        $this->setup()->sendMessage([
            'request' => 'destroy',
            'room' => $room,
            'secret' => $secret ?: '',
        ])->disconnect();

        if ($this->isValidPluginResponse('destroyed')) {
            return true;
        }

        $this->janus->logPluginError('destroy', [
            'room' => $room,
            'secret' => $secret,
        ]);

        return false;
    }

    /**
     * Create our initial session and handle for this plugin.
     *
     * @return Janus
     */
    private function setup(): Janus
    {
        return $this->janus->connect()->attach(self::PLUGIN);
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
