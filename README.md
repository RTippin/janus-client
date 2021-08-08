# Laravel Janus Gateway Client

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Tests][ico-test]][link-test]
[![StyleCI][ico-styleci]][link-styleci]
[![License][ico-license]][link-license]

---

### This package provides a client that allows you to fluently interact with your [Janus Gateway Server][link-janus]

## Support
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

## Info
- Returns the janus server info array.
```php
use RTippin\Janus\Facades\Janus;

public function info()
{
   return Janus::info();
}
```
```php
use RTippin\Janus\Janus;

public function info(Janus $janus)
{
   return $janus->info();
}
```
## Ping
- Ping will always return an array, containing `pong` as true|false, along with server latency.
```php
Janus::ping();
```
## Debug
- Enable debugging/dumps on the fly by calling to the `debug` method on `Janus`. This will dump each HTTP call's payload, response, and latency.
```php
use RTippin\Janus\Facades\Janus;

public function debug()
{
   $test = Janus::debug()->connect()->message(['test' => true]);
   
   dump($test);
   
   return true;
}
```
## Connect
- Connect will initiate a handshake with janus to set our session ID for any following request. This is a fluent method and can be chained.
```php
Janus::connect();
```
## Attach
- Attach to a janus plugin to set our handle ID. All following API calls in this request cycles will go to this plugin unless you call detach. This is a fluent method and can be chained.
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
Janus::message([]);
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
```
## Example Cycle
- To obtain a list of video rooms, we must connect, attach, message, and disconnect (to remove our session from your janus servers memory).
```php
use RTippin\Janus\Facades\Janus;
    
Janus::connect()
    ->attach('janus.plugin.videoroom')
    ->message(['request' => 'list']);

$rooms = Janus::getApiResponse();

Janus::disconnect();
```

----

# Video Room

- You may access the video room plugin through the core `Janus` class/facade, or dependency injection of the core `VideoRoom` class.
```php
use RTippin\Janus\Facades\Janus;

public function videoRoom()
{
   return Janus::videoRoom();
}
```
```php
use RTippin\Janus\Plugins\VideoRoom;

public function videoRoom(VideoRoom $videoRoom)
{
   return $videoRoom;
}
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
[ico-styleci]: https://styleci.io/repos/371539005/shield?style=plastic&cacheSeconds=3600
[ico-license]: https://img.shields.io/github/license/RTippin/janus-client?style=plastic
[link-packagist]: https://packagist.org/packages/rtippin/janus-client
[link-downloads]: https://packagist.org/packages/rtippin/janus-client
[link-license]: https://packagist.org/packages/rtippin/janus-client
[link-styleci]: https://styleci.io/repos/371539005
[link-janus]: https://janus.conf.meetecho.com/docs/index.html