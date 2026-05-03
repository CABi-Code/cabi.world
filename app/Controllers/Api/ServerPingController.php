<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Repository\ServerPingRepository;
use App\Repository\GlobalServerRepository;

class ServerPingController
{
	use ServerPingController\SkinsTrait;
	use ServerPingController\PingTrait;
	use ServerPingController\MotdTrait;

    private ServerPingRepository $repo;
    private GlobalServerRepository $globalRepo;

    public function __construct()
    {
        $this->repo = new ServerPingRepository();
        $this->globalRepo = new GlobalServerRepository();
    }

    private function createCurl(string $url): \CurlHandle
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'MCMonitor/1.0',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        return $ch;
    }

    /**
     * Резолв SRV-записи _minecraft._tcp.<domain>
     */
    private function resolveSrv(string $domain): ?array
    {
        $records = @dns_get_record("_minecraft._tcp.{$domain}", DNS_SRV);
        if (!$records || empty($records)) {
            return null;
        }

        usort($records, fn($a, $b) => $a['pri'] <=> $b['pri']);

        return [
            'target' => $records[0]['target'],
            'port'   => $records[0]['port'],
        ];
    }

    /**
     * Получить историю статуса сервера
     */
    public function history(Request $request): void
    {
        $itemId = (int)$request->query('item_id');
        $serverId = (int)$request->query('server_id');
        $hours = max(0, (int)$request->query('hours', 24));

        // Ограничиваем максимум для обычных запросов
        if ($hours > 0) {
            $hours = min(8760, $hours); // макс 1 год
        }

        if (!$itemId && !$serverId) {
            Response::error('item_id or server_id required', 400);
            return;
        }

        if ($serverId) {
            $history = $this->repo->getHistory($serverId, $hours);
        } else {
            $history = $this->repo->getHistoryByItemId($itemId, $hours);
        }

        Response::json(['history' => $history]);
    }

    private function isValidAddress(string $address): bool
    {
        if (filter_var($address, FILTER_VALIDATE_IP)) {
            if (filter_var($address, FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
            return true;
        }

        if (preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z]{2,})+$/', $address)) {
            $blocked = ['localhost', 'local', 'internal', '127.0.0.1'];
            foreach ($blocked as $b) {
                if (stripos($address, $b) !== false) return false;
            }
            return true;
        }

        return false;
    }

    private function packVarInt(int $value): string
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

    private function readVarInt($socket): int
    {
        $value = 0;
        $size = 0;
        while (true) {
            $byte = socket_read($socket, 1);
            if ($byte === false || $byte === '') {
                throw new \Exception('Failed to read VarInt');
            }
            $byte = ord($byte);
            $value |= ($byte & 0x7F) << (7 * $size);
            $size++;
            if (!($byte & 0x80)) break;
        }
        return $value;
    }

    /**
     * Пинг одного сервера и сохранение в global_servers + историю
     */
    private function pingAndSave(string $ip, int $port, bool $simpl = true, ?int $itemId = null): array
    {
        // Выполняем пинг
        if ($simpl) {
            $result = $this->SimplifiedPingMinecraftServer($ip, $port);
        } else {
            $result = $this->pingMinecraftServer($ip, $port);
        }

        // Фоллбэк на mcsrvstat
        if (!$result['online']) {
            $fallback = $this->pingViaMcsrvstat($ip, $port);
            if ($fallback['online']) {
                $fallback['source'] = 'mcsrvstat';
                $result = $fallback;
            } else {
                $result['source'] = 'self';
            }
        } else {
            $result['source'] = 'self';
        }

        // Сохраняем в global_servers и историю
        $globalServer = $this->globalRepo->findOrCreate($ip, $port);
        $this->repo->saveStatus($globalServer['id'], [
            'online' => $result['online'],
            'players_online' => $result['players']['online'] ?? 0,
            'players_max' => $result['players']['max'] ?? 0,
            'players_sample' => $result['players']['list'] ?? [],
            'version' => $result['version'] ?? null,
            'favicon' => $result['favicon'] ?? null,
            'source' => $result['source'] ?? 'server',
        ], $itemId);

        // Обновляем MOTD и favicon в global_servers если есть
        if ($result['online']) {
            $updateData = [
                'online' => true,
                'players_online' => $result['players']['online'] ?? 0,
                'players_max' => $result['players']['max'] ?? 0,
                'players_sample' => $result['players']['list'] ?? [],
                'version' => $result['version'] ?? null,
                'favicon' => $result['favicon'] ?? null,
            ];
            if (!empty($result['motd'])) {
                $updateData['motd_raw'] = $result['motd']['raw'] ?? [];
                $updateData['motd_html'] = $result['motd']['html'] ?? [];
            }
            $this->globalRepo->updateStatus($globalServer['id'], $updateData);
        }

        $result['server_id'] = $globalServer['id'];
        return $result;
    }
}
