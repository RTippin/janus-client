<?php

namespace RTippin\Janus\Plugins;

use Illuminate\Support\Str;
use RTippin\Janus\Exceptions\JanusApiException;
use RTippin\Janus\Exceptions\JanusPluginException;
use RTippin\Janus\Janus;

/**
 * Janus Videoroom Plugin.
 *
 * @link https://janus.conf.meetecho.com/docs/videoroom.html
 */
class VideoRoom extends BasePlugin
{
    /**
     * @var string|null
     */
    private ?string $adminKey;

    /**
     * VideoRoom constructor.
     *
     * @param  Janus  $janus
     */
    public function __construct(Janus $janus)
    {
        parent::__construct($janus);

        $this->setAdminKey(config('janus.video_room_secret'));
    }

    /**
     * @inheritDoc
     */
    public function getPluginName(): string
    {
        return 'janus.plugin.videoroom';
    }

    /**
     * @inheritDoc
     */
    public function getPluginShortName(): string
    {
        return 'videoroom';
    }

    /**
     * @param  string|null  $adminKey
     * @return $this
     */
    public function setAdminKey(?string $adminKey): self
    {
        $this->adminKey = $adminKey;

        return $this;
    }

    /**
     * List all Video Rooms we have in this janus server.
     *
     * @return array
     *
     * @throws JanusApiException|JanusPluginException
     */
    public function list(): array
    {
        $this->emit(['request' => 'list'])->bailIfInvalidPluginResponse();

        $list = $this->getPluginResponse();

        $this->disconnect();

        return $list;
    }

    /**
     * Check if janus has a video room with ID.
     *
     * @param  int  $room
     * @return array
     *
     * @throws JanusApiException|JanusPluginException
     */
    public function exists(int $room): array
    {
        $this->emit([
            'request' => 'exists',
            'room' => $room,
        ])->bailIfInvalidPluginResponse();

        $exists = $this->getPluginResponse();

        $this->disconnect();

        return $exists;
    }

    /**
     * Create a new video room, overriding properties you want to set.
     *
     * @param  array  $params
     * @param  bool  $usePin
     * @param  bool  $useSecret
     * @return array
     *
     * @throws JanusApiException|JanusPluginException
     */
    public function create(array $params = [],
                           bool $usePin = true,
                           bool $useSecret = true): array
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

        $this->emit($payload)->bailIfInvalidPluginResponse('created');

        $create = $this->getPluginResponse();

        $this->disconnect();

        return array_merge($create, [
            'pin' => $payload['pin'] ?: null,
            'secret' => $payload['secret'] ?: null,
        ]);
    }

    /**
     * Edit an existing video room's properties.
     *
     * @param  int  $room
     * @param  array  $params
     * @param  string|null  $secret
     * @return array
     *
     * @throws JanusApiException|JanusPluginException
     */
    public function edit(int $room,
                         array $params,
                         ?string $secret = null): array
    {
        $payload = array_merge([
            'request' => 'edit',
            'room' => $room,
            'secret' => $secret ?: '',
        ], $params);

        $this->emit($payload)->bailIfInvalidPluginResponse('edited');

        $edit = $this->getPluginResponse();

        $this->disconnect();

        return $edit;
    }

    /**
     * Configure whether to check tokens or add/remove people who can join a room.
     *
     * @param  int  $room
     * @param  string  $action
     * @param  array|null  $allowed
     * @param  string|null  $secret
     * @return array
     *
     * @throws JanusApiException|JanusPluginException
     */
    public function allowed(int $room,
                            string $action,
                            ?array $allowed = null,
                            ?string $secret = null): array
    {
        $this->emit([
            'request' => 'allowed',
            'room' => $room,
            'action' => $action,
            'secret' => $secret ?: '',
            'allowed' => $allowed ?: '',
        ])->bailIfInvalidPluginResponse();

        $allowed = $this->getPluginResponse();

        $this->disconnect();

        return $allowed;
    }

    /**
     * Kick a participant from a room using their private janus participant ID.
     *
     * @param  int  $room
     * @param  int  $participantID
     * @param  string|null  $secret
     * @return array
     *
     * @throws JanusApiException|JanusPluginException
     */
    public function kick(int $room,
                         int $participantID,
                         ?string $secret = null): array
    {
        $this->emit([
            'request' => 'kick',
            'room' => $room,
            'secret' => $secret ?: '',
            'id' => $participantID,
        ])->bailIfInvalidPluginResponse();

        $kick = $this->getPluginResponse();

        $this->disconnect();

        return $kick;
    }

    /**
     * Get a list of the participants in a specific room.
     *
     * @param  int  $room
     * @return array
     *
     * @throws JanusApiException|JanusPluginException
     */
    public function listParticipants(int $room): array
    {
        $this->emit([
            'request' => 'listparticipants',
            'room' => $room,
        ])->bailIfInvalidPluginResponse('participants');

        $participants = $this->getPluginResponse();

        $this->disconnect();

        return $participants;
    }

    /**
     * List all the forwarders in a specific room.
     *
     * @param  int  $room
     * @param  string|null  $secret
     * @return array
     *
     * @throws JanusApiException|JanusPluginException
     */
    public function listForwarders(int $room, ?string $secret = null): array
    {
        $this->emit([
            'request' => 'listforwarders',
            'room' => $room,
            'secret' => $secret ?: '',
        ])->bailIfInvalidPluginResponse('forwarders');

        $list = $this->getPluginResponse();

        $this->disconnect();

        return $list;
    }

    /**
     * Destroy a room given the room ID and optional secret.
     *
     * @param  int  $room
     * @param  string|null  $secret
     * @return array
     *
     * @throws JanusApiException|JanusPluginException
     */
    public function destroy(int $room, ?string $secret = null): array
    {
        $this->emit([
            'request' => 'destroy',
            'room' => $room,
            'secret' => $secret ?: '',
        ])->bailIfInvalidPluginResponse('destroyed');

        $destroy = $this->getPluginResponse();

        $this->disconnect();

        return $destroy;
    }

    /**
     * Moderate by muting/un-muting a participants stream.
     *
     * @param  int  $room
     * @param  int  $participantID
     * @param  bool  $mute
     * @param  string|null  $mid
     * @param  string|null  $secret
     * @return array
     *
     * @throws JanusApiException|JanusPluginException
     */
    public function moderate(int $room,
                             int $participantID,
                             bool $mute,
                             ?string $mid = null,
                             ?string $secret = null): array
    {
        $this->emit([
            'request' => 'moderate',
            'room' => $room,
            'id' => $participantID,
            'mid' => $mid ?: '',
            'mute' => $mute,
            'secret' => $secret ?: '',
        ])->bailIfInvalidPluginResponse();

        $moderate = $this->getPluginResponse();

        $this->disconnect();

        return $moderate;
    }

    /**
     * Enable or disable recording on all participants for the given room.
     *
     * @param  int  $room
     * @param  bool  $record
     * @param  string|null  $secret
     * @param  string|null  $pin
     * @return array
     *
     * @throws JanusApiException|JanusPluginException
     */
    public function enableRecording(int $room,
                                    bool $record,
                                    ?string $secret = null,
                                    ?string $pin = null): array
    {
        $this->emit([
            'request' => 'enable_recording',
            'room' => $room,
            'record' => $record,
            'secret' => $secret ?: '',
            'pin' => $pin ?: '',
        ])->bailIfInvalidPluginResponse();

        $recording = $this->getPluginResponse();

        $this->disconnect();

        return $recording;
    }
}
