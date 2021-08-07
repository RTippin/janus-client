<?php

namespace RTippin\Janus\Tests\Plugins;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RTippin\Janus\Exceptions\JanusPluginException;
use RTippin\Janus\Janus;
use RTippin\Janus\Plugins\VideoRoom;
use RTippin\Janus\Tests\JanusTestCase;

class VideoRoomTest extends JanusTestCase
{
    private VideoRoom $videoRoom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->videoRoom = app(VideoRoom::class);
    }

    /** @test */
    public function it_returns_janus_instance()
    {
        $this->assertInstanceOf(Janus::class, $this->videoRoom->janus());
    }

    /** @test */
    public function it_can_disconnect()
    {
        Http::fake([
            self::Endpoint => Http::response(self::SuccessResponse),
        ]);

        $this->videoRoom->disconnect();

        Http::assertSent(function (Request $request) {
            return $request['janus'] === 'destroy';
        });
    }

    /** @test */
    public function it_can_make_calls_without_disconnecting()
    {
        Http::fake();

        $this->videoRoom->withoutDisconnect()->disconnect();

        Http::assertNothingSent();
    }

    /** @test */
    public function it_can_force_disconnect()
    {
        Http::fake([
            self::Endpoint => Http::response(self::SuccessResponse),
        ]);

        $this->videoRoom->withoutDisconnect()->disconnect(true);

        Http::assertSent(function (Request $request) {
            return $request['janus'] === 'destroy';
        });
    }

    /** @test */
    public function it_list_video_rooms()
    {
        $plugin = [
            'plugindata' => [
                'data' => [
                    'videoroom' => 'success',
                    'list' => [1, 2, 3],
                ],
            ],
        ];
        Http::fake([
            self::Endpoint => Http::sequence()
                ->push(self::SuccessResponse) //connect
                ->push(self::SuccessResponse) //attach
                ->push(array_merge(self::SuccessResponse, $plugin)) //message
                ->push(self::SuccessResponse), //disconnect
        ]);

        $this->assertSame([1, 2, 3], $this->videoRoom->list());
    }

    /** @test */
    public function it_throws_exception_if_invalid_list_response()
    {
        $plugin = [
            'plugindata' => [
                'data' => [
                    'videoroom' => 'error',
                ],
            ],
        ];
        Http::fake([
            self::Endpoint => Http::sequence()
                ->push(self::SuccessResponse) //connect
                ->push(self::SuccessResponse) //attach
                ->push(array_merge(self::SuccessResponse, $plugin)), //message
        ]);

        $this->expectException(JanusPluginException::class);

        $this->videoRoom->list();
    }
}
