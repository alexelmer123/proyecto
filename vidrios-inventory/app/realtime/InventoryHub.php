<?php
declare(strict_types=1);

namespace Vitralia\Realtime;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;
use Throwable;

/**
 * Hub WebSocket: mantiene la lista de clientes conectados y difunde a todos
 * los eventos que llegan por el endpoint HTTP interno.
 *
 * Es 100% pasivo: los clientes son listeners. Cualquier mensaje entrante por
 * WS se descarta — esto evita que un navegador hostil envíe eventos falsos al
 * resto. La única fuente de eventos legítima es el endpoint /push interno,
 * autenticado con `X-Realtime-Secret`.
 */
final class InventoryHub implements MessageComponentInterface
{
    /** @var SplObjectStorage<ConnectionInterface, array<string,mixed>> */
    private SplObjectStorage $clients;

    public function __construct()
    {
        $this->clients = new SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn, [
            'connected_at' => time(),
            'remote'       => $this->remoteAddr($conn),
        ]);
        $this->log(sprintf(
            'open  ← %s (total=%d)',
            $this->remoteAddr($conn),
            $this->clients->count()
        ));

        $welcome = json_encode([
            'event' => 'welcome',
            'data'  => [
                'connected' => $this->clients->count(),
            ],
            'ts'    => date('c'),
        ], JSON_UNESCAPED_UNICODE);
        if (is_string($welcome)) {
            $conn->send($welcome);
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);
        $this->log(sprintf('close → %s (total=%d)', $this->remoteAddr($conn), $this->clients->count()));
    }

    public function onError(ConnectionInterface $conn, Exception $e): void
    {
        $this->log('error: ' . $e->getMessage());
        $conn->close();
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        // No-op: los clientes son pasivos. Ignoramos cualquier mensaje entrante.
    }

    /** Difunde el payload JSON a todos los clientes conectados. */
    public function broadcast(string $payload): int
    {
        $delivered = 0;
        $detach = [];
        foreach ($this->clients as $client) {
            try {
                $client->send($payload);
                $delivered++;
            } catch (Throwable $e) {
                $detach[] = $client;
            }
        }
        foreach ($detach as $c) {
            $this->clients->detach($c);
        }
        return $delivered;
    }

    public function size(): int
    {
        return $this->clients->count();
    }

    private function remoteAddr(ConnectionInterface $conn): string
    {
        return property_exists($conn, 'remoteAddress')
            ? (string) $conn->remoteAddress
            : 'unknown';
    }

    private function log(string $msg): void
    {
        fwrite(STDOUT, '[' . date('H:i:s') . '] ' . $msg . PHP_EOL);
    }
}
