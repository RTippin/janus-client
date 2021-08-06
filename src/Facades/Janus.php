<?php

namespace RTippin\Janus\Facades;

use Illuminate\Support\Facades\Facade;
use RTippin\Janus\Plugins\VideoRoom;
use RTippin\Janus\Server;

/**
 * @method static Server server()
 * @method static VideoRoom videoRoom()
 * @method static array info()
 * @method static array ping()
 * @method static \RTippin\Janus\Janus connect()
 * @method static \RTippin\Janus\Janus attach(string $plugin)
 * @method static \RTippin\Janus\Janus detach()
 * @method static \RTippin\Janus\Janus disconnect()
 * @method static \RTippin\Janus\Janus message(array $message, ?string $jsep = null)
 * @method static \RTippin\Janus\Janus trickleCandidate(string $candidate)
 *
 * @mixin \RTippin\Janus\Janus
 * @see \RTippin\Janus\Janus
 */
class Janus extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \RTippin\Janus\Janus::class;
    }
}
