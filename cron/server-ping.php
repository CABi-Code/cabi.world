<?php
/**
 * Крон-скрипт для пинга серверов
 * Запускать каждую минуту: * * * * * php /path/to/cron/server-ping.php
 *
 * Пингует все global_servers, на которые ссылается хотя бы один folder item.
 * Результаты пишутся в server_ping_history по global_servers.id и
 * обновляют витринные поля в global_servers.
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/app/Core/Database.php';

use App\Repository\ServerPingRepository;
use App\Repository\GlobalServerRepository;

$pingRepo = new ServerPingRepository();
$globalRepo = new GlobalServerRepository();

$servers = $globalRepo->getServersForPing();

echo "[" . date('Y-m-d H:i:s') . "] Found " . count($servers) . " global servers to ping\n";

foreach ($servers as $server) {
    $serverId = (int)$server['id'];
    $ip = $server['address'];
    $port = (int)$server['port'];

    echo "Pinging #{$serverId} {$ip}:{$port}... ";

    $result = pingMinecraftServer($ip, $port);

    if ($result['online']) {
        echo "ONLINE ({$result['players']['online']}/{$result['players']['max']})\n";
    } else {
        echo "OFFLINE\n";
    }

    $pingRepo->saveStatus($serverId, [
        'online' => $result['online'],
        'players_online' => $result['players']['online'] ?? 0,
        'players_max' => $result['players']['max'] ?? 0,
        'players_sample' => $result['players']['sample'] ?? [],
        'version' => $result['version'] ?? null,
        'favicon' => $result['favicon'] ?? null,
        'source' => 'cron',
    ]);

    if ($result['online']) {
        $globalRepo->updateStatus($serverId, [
            'online' => true,
            'players_online' => $result['players']['online'] ?? 0,
            'players_max' => $result['players']['max'] ?? 0,
            'players_sample' => $result['players']['sample'] ?? [],
            'version' => $result['version'] ?? null,
            'favicon' => $result['favicon'] ?? null,
        ]);
    }

    usleep(100000); // 100ms между пингами
}

// Очистка старых записей (раз в сутки)
if (date('H:i') === '03:00') {
    $deleted = $pingRepo->cleanup(7);
    echo "Cleaned up {$deleted} old records\n";
}

echo "Done!\n";

/**
 * Пинг Minecraft сервера
 */
function pingMinecraftServer(string $ip, int $port): array
{
    $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if (!$socket) {
        return ['online' => false];
    }
    
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
    socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);
    
    $result = @socket_connect($socket, $ip, $port);
    if (!$result) {
        socket_close($socket);
        return ['online' => false];
    }
    
    try {
        // Handshake
        $protocolVersion = 767;
        $handshake = packVarInt(0)
            . packVarInt($protocolVersion)
            . packVarInt(strlen($ip)) . $ip
            . pack('n', $port)
            . packVarInt(1);
        
        $packet = packVarInt(strlen($handshake)) . $handshake;
        socket_write($socket, $packet, strlen($packet));
        
        // Status request
        $statusRequest = packVarInt(1) . packVarInt(0);
        socket_write($socket, $statusRequest, strlen($statusRequest));
        
        // Read response
        $packetLen = readVarInt($socket);
        $packetId = readVarInt($socket);
        $jsonLen = readVarInt($socket);
        
        $response = '';
        $remaining = $jsonLen;
        while ($remaining > 0) {
            $chunk = socket_read($socket, min($remaining, 4096));
            if ($chunk === false) break;
            $response .= $chunk;
            $remaining -= strlen($chunk);
        }
        
        socket_close($socket);
        
        $data = json_decode($response, true);
        if (!$data) {
            return ['online' => false];
        }
        
        return [
            'online' => true,
            'version' => $data['version']['name'] ?? null,
            'favicon' => $data['favicon'] ?? null,
            'players' => [
                'online' => $data['players']['online'] ?? 0,
                'max' => $data['players']['max'] ?? 0,
                'sample' => $data['players']['sample'] ?? []
            ]
        ];
    } catch (Exception $e) {
        socket_close($socket);
        return ['online' => false];
    }
}

function packVarInt(int $value): string
{
    $buffer = '';
    while (true) {
        $byte = $value & 0x7F;
        $value >>= 7;
        $buffer .= chr($byte | ($value ? 0x80 : 0));
        if ($value === 0) break;
    }
    return $buffer;
}

function readVarInt($socket): int
{
    $value = 0;
    $size = 0;
    while (true) {
        $byte = socket_read($socket, 1);
        if ($byte === false || $byte === '') {
            throw new Exception('Failed to read VarInt');
        }
        $byte = ord($byte);
        $value |= ($byte & 0x7F) << (7 * $size);
        $size++;
        if (!($byte & 0x80)) break;
    }
    return $value;
}
