# Laravel Janus Gateway Client

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Tests][ico-test]][link-test]
[![StyleCI][ico-styleci]][link-styleci]
[![License][ico-license]][link-license]

---

### This package provides a client to fluently interact with your [Janus Gateway Server][link-janus]

## Janus Support
- Core REST API wrapper to interact with janus.
- VideoRoom plugin wrapper.

---
**Fluent, convenient, clean.**
```php
use RTippin\Janus\Facades\Janus;

$ping = Janus::ping(); 

---------------------------------------

['pong' => true]

---------------------------------------

$room = Janus::videoRoom()->create([
    'description' => 'My first room!',
    'publishers' => 4,
]);

---------------------------------------

[
  'videoroom' => 'created',
  'room' => 6663183870503329,
  'permanent' => false,
  'pin' => 'TFQuls',
  'secret' => 'y2WaVehf7cOM',
]
```

---

# Installation

### Via Composer

``` bash
$ composer require rtippin/janus-client
```

### Publish the config file

``` bash
$ php artisan vendor:publish --tag=janus
```

---

# Config

```php
'server_endpoint' => env('JANUS_SERVER_ENDPOINT'),
'admin_server_endpoint' => env('JANUS_ADMIN_SERVER_ENDPOINT'),
'verify_ssl' => env('JANUS_VERIFY_SSL', true),
'debug' => env('JANUS_DEBUG', false),
'admin_secret' => env('JANUS_ADMIN_SECRET'),
'api_secret' => env('JANUS_API_SECRET'),
'video_room_secret' => env('JANUS_VIDEO_ROOM_SECRET'),
```

- `server_endpoint` is the main HTTP endpoint for your janus server.
- `admin_server_endpoint` is the admin HTTP endpoint for your janus server.
- `verify_ssl` enables or disables our `Guzzle HTTP` calls from verifying the SSL.
- `debug` When enabled, each request in a cycle will dump the payload and responses.
- `admin_secret` API secret to access the admin endpoint.
- `api_secret` The general API secret.
- `video_room_secret` Optional video room secret to protect creates.

----

# General Usage

- You may choose to use our provided facade, or dependency injection to access our core `Janus` class.

### Notice, `Janus` is registered as a singleton. Once you instantiate our class, it will be kept in memory with its current state  for that request cycle.

## Obtaining the `Janus` client

**Using Facade**
```php
use RTippin\Janus\Facades\Janus;

$info = Janus::info() || Janus::getInstance()->info();
```
**Using Dependency Injection**
```php
<?php

namespace App\Http\Controllers;

use RTippin\Janus\Janus;

class JanusController
{
    private Janus $janus;

    public function __construct(Janus $janus)
    {
       $this->janus = $janus;
    }
}
```
## Info
- Returns the janus server info array.
```php
Janus::info();
```
## Ping
- Ping will always return an array (even if an exception is thrown), containing `pong` as true|false, along with server latency.
```php
Janus::ping();
```
## Debug
- Enable debugging/dumps on the fly by calling to the `debug` method on `Janus`. This will dump each HTTP call's payload, response, and latency.
```php
use RTippin\Janus\Facades\Janus;

Route::get('test', function(){
    Janus::debug()->ping();
    dump('It dumps inline for each http call!');
});

//OUTPUT

"PAYLOAD"

array:3 [▼
  "transaction" => "q52xpYrZJ6e6"
  "apisecret" => "secret"
  "janus" => "ping"
]

"RESPONSE"

array:2 [▼
  "janus" => "pong"
  "transaction" => "q52xpYrZJ6e6"
]

"LATENCY"

16.0

"It dumps inline for each http call!"
```
## Connect
- Connect will initiate a handshake with janus to set our session ID for any following request. This is a fluent method and can be chained.
```php
Janus::connect();
```
## Attach
- Attach to a janus plugin to set our handle ID. All following API calls in this request cycles will go to this plugin unless you call detach or disconnect. This is a fluent method and can be chained.
```php
Janus::attach('janus.plugin.name');
```
## Detach
- Detach from the current plugin/handle. This is a fluent method and can be chained.
```php
Janus::detach();
```
## Disconnect
- Disconnect from janus, destroying our session and handle/plugin. This is a fluent method and can be chained.
```php
Janus::disconnect();
```
## Message
- Send janus our message. This is usually called once attached to a plugin, and sends commands to janus. This is a fluent method and can be chained.
```php
Janus::message(['request' => 'list']);
```
## Trickle
- Send a trickle candidate to janus. This is a fluent method and can be chained.
```php
Janus::trickle('candidate information');
```
## Server
- Returns the underlying janus server class, allowing you to set configs, or access current payloads/responses in the cycle.
```php
use RTippin\Janus\Facades\Janus;

$server = Janus::server()
    ->setServerEndpoint('http://test.com')
    ->setAdminServerEndpoint('http://test.com/admin')
    ->setApiSecret('secret');
    
Janus::connect();

$response = $server->getApiResponse();
$payload = $server->getApiPayload();
$latency = $server->getEndLatency();
```
## Example Cycle
- Say we want to obtain a list of video rooms, 4 calls must be made. Once attached, we send janus our command message, and disconnect (to remove our session from your janus servers memory).
    - First we connect which sets our session id.
    - Then we want to attach to the video room plugin to set our handle id.
    - Once attached, we send janus our command message to list rooms.
    - If no further calls need to be made, we then disconnect which will reset our session and handle ID's. This also ensures state sessions are not kept in your janus servers memory.

```php
use RTippin\Janus\Facades\Janus;

//Send our command for the results we want.    
Janus::connect()
    ->attach('janus.plugin.videoroom')
    ->message(['request' => 'list']);

//Set the results from the last command sent.
$rooms = Janus::getApiResponse();

//Disconnect and reset all janus values.
Janus::disconnect();
```

----

# Video Room

### For full docs relating to the video room plugin and its responses, please check the [Official Docs][link-videoroom]

- You may access the video room plugin through the core `Janus` class/facade, or dependency injection of the core `VideoRoom` class.
- All videoroom methods will return the plugin response from janus directly. It is up to you to check the arrays for the data you need.
- Each method completes a full cycle (connect, attach, message, disconnect). If you with to use many commands in one cycle, you may

**Using Facade**
```php
use RTippin\Janus\Facades\Janus;

$videoRoom = Janus::videoRoom();
```
**Using Dependency Injection**
```php
<?php

namespace App\Http\Controllers;

use RTippin\Janus\Plugins\VideoRoom;

class VideoRoomController
{
    private VideoRoom $videoRoom;

    public function __construct(VideoRoom $videoRoom)
    {
       $this->videoRoom = $videoRoom;
    }
}
```
## List
- Returns a list of the available rooms (excluded those configured or created as private rooms).
```php
$list = Janus::videoRoom()->list();
```
## Exists
- Check whether a room exists.
```php
$exists = Janus::videoRoom()->exists(12345678);
```
## List
- Returns a list of the available rooms (excluded those configured or created as private rooms).
```php
$list = Janus::videoRoom()->list();
```
## List
- Returns a list of the available rooms (excluded those configured or created as private rooms).
```php
$list = Janus::videoRoom()->list();
```
## List
- Returns a list of the available rooms (excluded those configured or created as private rooms).
```php
$list = Janus::videoRoom()->list();
```

## WIP VideoRoom Docs

---

## Credits - Richard Tippin

## License - MIT

### Please see the [license file](LICENSE.md) for more information.

[link-author]: https://github.com/rtippin
[ico-version]: https://img.shields.io/packagist/v/rtippin/janus-client.svg?style=plastic&cacheSeconds=3600
[ico-downloads]: https://img.shields.io/packagist/dt/rtippin/janus-client.svg?style=plastic&cacheSeconds=3600
[link-test]: https://github.com/RTippin/janus-client/actions
[ico-test]: https://img.shields.io/github/workflow/status/rtippin/janus-client/tests?style=plastic
[ico-styleci]: https://styleci.io/repos/387571926/shield?style=plastic&cacheSeconds=3600
[ico-license]: https://img.shields.io/github/license/RTippin/janus-client?style=plastic
[link-packagist]: https://packagist.org/packages/rtippin/janus-client
[link-downloads]: https://packagist.org/packages/rtippin/janus-client
[link-license]: https://packagist.org/packages/rtippin/janus-client
[link-styleci]: https://styleci.io/repos/387571926
[link-janus]: https://janus.conf.meetecho.com/docs/index.html
[link-videoroom]: https://janus.conf.meetecho.com/docs/videoroom.html