<?php
declare(strict_types=1);

/**
 * Daemon WebSocket de Vitralia.
 *
 * Levanta DOS sockets sobre un único event loop ReactPHP:
 *   - WS público (clientes/navegadores) en REALTIME_WS_HOST:REALTIME_WS_PORT
 *   - HTTP interno (controladores PHP) en 127.0.0.1:REALTIME_PUSH_PORT
 *
 * Arranque:
 *   php bin/ws-server.php
 *
 * Detener: Ctrl+C. En Windows con Laragon puedes dejarlo en una consola
 * dedicada; en producción usa NSSM (Windows) o systemd (Linux).
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este daemon debe arrancarse desde la línea de comandos.\n");
    exit(1);
}

define('ROOT', dirname(__DIR__));

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/realtime.php';

use Psr\Http\Message\ServerRequestInterface;
use Ratchet\Http\HttpServer as RatchetHttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Http\HttpServer as ReactHttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;
use Vitralia\Realtime\InventoryHub;

$loop = Loop::get();
$hub  = new InventoryHub();

// --- (a) WebSocket público ---------------------------------------------------
$wsBind   = REALTIME_WS_HOST . ':' . REALTIME_WS_PORT;
$wsSocket = new SocketServer($wsBind, [], $loop);
new IoServer(
    new RatchetHttpServer(new WsServer($hub)),
    $wsSocket,
    $loop
);

// --- (b) Bridge HTTP interno --------------------------------------------------
$pushBind   = REALTIME_PUSH_HOST . ':' . REALTIME_PUSH_PORT;
$pushSocket = new SocketServer($pushBind, [], $loop);

$pushHttp = new ReactHttpServer(static function (ServerRequestInterface $req) use ($hub): Response {
    if ($req->getMethod() === 'GET' && $req->getUri()->getPath() === '/health') {
        return new Response(200, ['Content-Type' => 'application/json'],
            (string) json_encode(['ok' => true, 'connected' => $hub->size()]));
    }

    if ($req->getMethod() !== 'POST') {
        return new Response(405, [], 'method-not-allowed');
    }

    $secret = $req->getHeaderLine('X-Realtime-Secret');
    if (!hash_equals(REALTIME_PUSH_SECRET, $secret)) {
        return new Response(401, [], 'bad-secret');
    }

    $body = (string) $req->getBody();
    if ($body === '') {
        return new Response(400, [], 'empty-body');
    }
    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        return new Response(400, [], 'bad-json');
    }

    $delivered = $hub->broadcast($body);
    return new Response(
        200,
        ['Content-Type' => 'application/json'],
        (string) json_encode(['delivered' => $delivered])
    );
});
$pushHttp->listen($pushSocket);

fwrite(STDOUT, sprintf(
    "[Vitralia WS] WebSocket escuchando en %s · push interno en %s · PID=%d%s",
    $wsBind,
    $pushBind,
    getmypid(),
    PHP_EOL
));

$loop->run();
