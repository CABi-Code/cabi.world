<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Repository\ServerPingRepository;

class ServerPingController
{
    private ServerPingRepository $repo;
    
    public function __construct()
    {
        $this->repo = new ServerPingRepository();
    }
    
    /**
     * Прокси для пинга сервера с клиента
     */
    public function ping(Request $request): void
    {
        $ip = trim($request->query('ip', ''));
        $port = (int)$request->query('port', 25565);
        
        if (empty($ip)) {
            Response::error('IP required', 400);
            return;
        }
        
        // Валидация IP/домена
        if (!$this->isValidAddress($ip)) {
            Response::error('Invalid address', 400);
            return;
        }
        
        // Пингуем
        $result = $this->pingMinecraftServer($ip, $port);
        Response::json($result);
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
    
    private function pingMinecraftServer(string $ip, int $port): array
    {
        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$socket) {
            return ['online' => false, 'error' => 'Socket creation failed'];
        }
        
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);
        
        $result = @socket_connect($socket, $ip, $port);
        if (!$result) {
            socket_close($socket);
            return ['online' => false, 'error' => 'Connection failed'];
        }
        
        try {
            // Handshake packet
            $protocolVersion = 767; // 1.21
            $handshake = $this->packVarInt(0) // Packet ID
                . $this->packVarInt($protocolVersion)
                . $this->packVarInt(strlen($ip)) . $ip
                . pack('n', $port)
                . $this->packVarInt(1); // Next state: status
            
            $packet = $this->packVarInt(strlen($handshake)) . $handshake;
            socket_write($socket, $packet, strlen($packet));
            
            // Status request
            $statusRequest = $this->packVarInt(1) . $this->packVarInt(0);
            socket_write($socket, $statusRequest, strlen($statusRequest));
            
            // Read response
            $packetLen = $this->readVarInt($socket);
            $packetId = $this->readVarInt($socket);
            $jsonLen = $this->readVarInt($socket);
            
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
                return ['online' => false, 'error' => 'Invalid response'];
            }
            
            return [
                'online' => true,
                'version' => $data['version']['name'] ?? null,
                'protocol' => $data['version']['protocol'] ?? null,
                'players' => [
                    'online' => $data['players']['online'] ?? 0,
                    'max' => $data['players']['max'] ?? 0,
                    'sample' => $data['players']['sample'] ?? []
                ],
                'description' => $data['description'] ?? null,
                'favicon' => isset($data['favicon'])
            ];
        } catch (\Exception $e) {
            socket_close($socket);
            return ['online' => false, 'error' => $e->getMessage()];
        }
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
