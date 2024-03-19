
<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\App;

require __DIR__ . '/../vendor/autoload.php';


$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new App()
        )
    ),
    1080
);

$server->run();

