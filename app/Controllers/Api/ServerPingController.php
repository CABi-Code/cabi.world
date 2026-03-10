<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Repository\ServerPingRepository;

class ServerPingController
{
	use ServerPingController\SkinsTrait;
	use ServerPingController\PingTrait;
	use ServerPingController\MotdTrait;

    private ServerPingRepository $repo;
    
    public function __construct()
    {
        $this->repo = new ServerPingRepository();
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

    // Берём запись с наименьшим приоритетом
    usort($records, fn($a, $b) => $a['pri'] <=> $b['pri']);

    return [
        'target' => $records[0]['target'],
        'port'   => $records[0]['port'],
    ];
}

    
    /**
     * Получение отчета от клиента о статусе сервера
     */
    public function report(Request $request): void
    {
        $itemId = (int)$request->get('item_id');
        $online = (bool)$request->get('online');
        $playersOnline = (int)$request->get('players_online', 0);
        $playersMax = (int)$request->get('players_max', 0);
        $playersSample = $request->get('players_sample', []);
        $version = $request->get('version');
        
        if (!$itemId) {
            Response::error('item_id required', 400);
            return;
        }
        
        // Сохраняем статус
        $this->repo->saveStatus($itemId, [
            'online' => $online,
            'players_online' => $playersOnline,
            'players_max' => $playersMax,
            'players_sample' => $playersSample,
            'version' => $version,
            'source' => 'client'
        ]);
        
        Response::json(['success' => true]);
    }
    
    /**
     * Получить историю статуса сервера
     */
    public function history(Request $request): void
    {
        $itemId = (int)$request->query('item_id');
        $hours = min(168, max(1, (int)$request->query('hours', 24)));
        
        if (!$itemId) {
            Response::error('item_id required', 400);
            return;
        }
        
        $history = $this->repo->getHistory($itemId, $hours);
        Response::json(['history' => $history]);
    }
    
    private function isValidAddress(string $address): bool
    {
        // Проверяем IP
        if (filter_var($address, FILTER_VALIDATE_IP)) {
            // Запрещаем локальные адреса
            if (filter_var($address, FILTER_VALIDATE_IP, 
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
            return true;
        }
        
        // Проверяем домен
        if (preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z]{2,})+$/', $address)) {
            // Запрещаем localhost и внутренние домены
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
}
