<?php

namespace App\Controllers\Api\ServerPingController;

use App\Http\Request;
use App\Http\Response;

trait PingTrait
{
	/**
	 * Нормализация ответа mcsrvstat.us в единый формат
	 */
	private function normalizeMcsrvstatResponse(array $data): array
	{
		$rawPlayers = [];
		if (!empty($data['players']['list'])) {
			foreach ($data['players']['list'] as $p) {
				$rawPlayers[] = [
					'name' => is_string($p) ? $p : ($p['name'] ?? ''),
					'uuid' => is_string($p) ? null : ($p['uuid'] ?? null),
				];
			}
		}

		$playersWithSkins = $this->resolvePlayerSkins($rawPlayers);

		return [
			'online'      => true,
			'ip'          => $data['ip'] ?? null,
			'port'        => $data['port'] ?? 25565,
			'hostname'    => $data['hostname'] ?? null,
			'version'     => $data['version'] ?? null,
			'protocol'    => $data['protocol'] ?? null,
			'players'     => [
				'online' => $data['players']['online'] ?? 0,
				'max'    => $data['players']['max'] ?? 0,
				'list'   => $playersWithSkins,
			],
			'motd'        => [
				'raw'   => $data['motd']['raw'] ?? [],
				'clean' => $data['motd']['clean'] ?? [],
				'html'  => $data['motd']['html'] ?? [],
			],
			'favicon'     => $data['icon'] ?? null,
			'eula_blocked' => $data['eula_blocked'] ?? false,
		];
	}

	/**
	 * Пинг одного сервера (или массовый пинг нескольких)
	 * Одиночный: /api/server-ping?ip=...&port=...
	 * Массовый:  /api/server-ping?servers=[{"ip":"...","port":25565},...]
	 */
	public function ping(Request $request): void
	{
		$serversParam = $request->query('servers', '');

		// Массовый пинг
		if (!empty($serversParam)) {
			$this->bulkPing($serversParam);
			return;
		}

		// Одиночный пинг
		$ip = trim($request->query('ip', ''));
		$port = (int)$request->query('port', 25565);
		$simpl = (bool)$request->query('simpl', true);
		$itemId = (int)$request->query('item_id', 0);

		if (empty($ip)) {
			Response::error('IP required', 400);
			return;
		}

		if (!$this->isValidAddress($ip)) {
			Response::error('Invalid address', 400);
			return;
		}

		$result = $this->pingAndSave($ip, $port, $simpl, $itemId ?: null);
		Response::json($result);
	}

	/**
	 * Массовый пинг серверов
	 * Принимает JSON-массив серверов: [{"ip":"...","port":25565}, ...]
	 */
	private function bulkPing(string $serversJson): void
	{
		$servers = json_decode($serversJson, true);

		if (!is_array($servers) || empty($servers)) {
			Response::error('Invalid servers parameter', 400);
			return;
		}

		// Лимит на количество серверов за раз
		$servers = array_slice($servers, 0, 20);

		$results = [];
		foreach ($servers as $server) {
			$ip = trim($server['ip'] ?? '');
			$port = (int)($server['port'] ?? 25565);

			if (empty($ip) || !$this->isValidAddress($ip)) {
				$results[] = [
					'ip' => $ip,
					'port' => $port,
					'online' => false,
					'error' => 'Invalid address',
				];
				continue;
			}

			$result = $this->pingAndSave($ip, $port, true);
			$results[] = $result;
		}

		Response::json(['servers' => $results]);
	}

	/**
	 * Фоллбэк-пинг через api.mcsrvstat.us
	 */
	private function pingViaMcsrvstat(string $ip, int $port): array
	{
		$address = $port !== 25565 ? "{$ip}:{$port}" : $ip;
		$url = "https://api.mcsrvstat.us/3/{$address}";

		$ctx = stream_context_create([
			'http' => [
				'timeout' => 10,
				'header' => "Accept: application/json\r\nUser-Agent: MCMonitor/1.0\r\n",
			],
		]);

		$raw = @file_get_contents($url, false, $ctx);
		if ($raw === false) {
			return ['online' => false, 'error' => 'mcsrvstat request failed'];
		}

		$data = json_decode($raw, true);
		if (!$data || !isset($data['online'])) {
			return ['online' => false, 'error' => 'mcsrvstat invalid response'];
		}

		if (!$data['online']) {
			return ['online' => false, 'error' => 'Server offline (mcsrvstat)'];
		}

		return $this->normalizeMcsrvstatResponse($data);
	}

	/**
	 * Свой пинг — расширенный формат
	 */
	private function pingMinecraftServer(string $ip, int $port): array
	{
		$resolvedIp = $ip;
		$resolvedPort = $port;
		$hostname = null;

		if (!filter_var($ip, FILTER_VALIDATE_IP)) {
			$hostname = $ip;
			$srv = $this->resolveSrv($ip);
			if ($srv) {
				$resolvedIp = $srv['target'];
				$resolvedPort = $srv['port'];
			}
		}

		$socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if (!$socket) {
			return ['online' => false, 'error' => 'Socket creation failed'];
		}

		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
		socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);

		$connectIp = filter_var($resolvedIp, FILTER_VALIDATE_IP)
			? $resolvedIp
			: gethostbyname($resolvedIp);

		$result = @socket_connect($socket, $connectIp, $resolvedPort);
		if (!$result) {
			socket_close($socket);
			return ['online' => false, 'error' => 'Connection failed'];
		}

		try {
			$pingStart = microtime(true);

			$protocolVersion = 767;
			$handshakeHost = $hostname ?? $ip;
			$handshake = $this->packVarInt(0x00)
				. $this->packVarInt($protocolVersion)
				. $this->packVarInt(strlen($handshakeHost)) . $handshakeHost
				. pack('n', $resolvedPort)
				. $this->packVarInt(1);

			$packet = $this->packVarInt(strlen($handshake)) . $handshake;
			socket_write($socket, $packet, strlen($packet));

			$statusRequest = $this->packVarInt(1) . $this->packVarInt(0x00);
			socket_write($socket, $statusRequest, strlen($statusRequest));

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

			$latency = round((microtime(true) - $pingStart) * 1000);
			socket_close($socket);

			$data = json_decode($response, true);
			if (!$data) {
				return ['online' => false, 'error' => 'Invalid response'];
			}

			$motd = $this->parseMotd($data['description'] ?? '');

			$playersList = [];
			if (!empty($data['players']['sample'])) {
				foreach ($data['players']['sample'] as $p) {
					$playersList[] = [
						'name' => $p['name'] ?? 'Unknown',
						'uuid' => $p['id'] ?? null,
					];
				}
			}

			$playersWithSkins = $this->resolvePlayerSkins($playersList);
			return [
				'online'       => true,
				'ip'           => $connectIp,
				'port'         => $resolvedPort,
				'hostname'     => $hostname,
				'version'      => $data['version']['name'] ?? null,
				'protocol'     => $data['version']['protocol'] ?? null,
				'players'      => [
					'online' => $data['players']['online'] ?? 0,
					'max'    => $data['players']['max'] ?? 0,
					'list'   => $playersWithSkins,
				],
				'motd'         => $motd,
				'favicon'      => $data['favicon'] ?? null,
				'latency'      => $latency,
				'eula_blocked' => false,
			];
		} catch (\Exception $e) {
			socket_close($socket);
			return ['online' => false, 'error' => $e->getMessage()];
		}
	}

	private function SimplifiedPingMinecraftServer(string $ip, int $port): array
	{
		$resolvedIp = $ip;
		$resolvedPort = $port;
		$hostname = null;

		if (!filter_var($ip, FILTER_VALIDATE_IP)) {
			$hostname = $ip;
			$srv = $this->resolveSrv($ip);
			if ($srv) {
				$resolvedIp = $srv['target'];
				$resolvedPort = $srv['port'];
			}
		}

		$socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if (!$socket) {
			return ['online' => false];
		}

		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
		socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);

		$connectIp = filter_var($resolvedIp, FILTER_VALIDATE_IP)
			? $resolvedIp
			: gethostbyname($resolvedIp);

		$result = @socket_connect($socket, $connectIp, $resolvedPort);
		if (!$result) {
			socket_close($socket);
			return ['online' => false];
		}

		try {
			$protocolVersion = 767;
			$handshakeHost = $hostname ?? $ip;
			$handshake = $this->packVarInt(0x00)
				. $this->packVarInt($protocolVersion)
				. $this->packVarInt(strlen($handshakeHost)) . $handshakeHost
				. pack('n', $resolvedPort)
				. $this->packVarInt(1);

			$packet = $this->packVarInt(strlen($handshake)) . $handshake;
			socket_write($socket, $packet, strlen($packet));

			$statusRequest = $this->packVarInt(1) . $this->packVarInt(0x00);
			socket_write($socket, $statusRequest, strlen($statusRequest));

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
				return ['online' => false];
			}

			return [
				'online'		=> true,
				'ip'			=> $connectIp,
				'port'			=> $resolvedPort,
				'hostname'		=> $hostname,
				'version'		=> $data['version']['name'] ?? null,
				'protocol'		=> $data['version']['protocol'] ?? null,
				'players'		=> [
					'online'	=> $data['players']['online'] ?? 0,
					'max'		=> $data['players']['max'] ?? 0,
				],
				'favicon'		=> $data['favicon'] ?? null,
			];
		} catch (\Exception $e) {
			socket_close($socket);
			return ['online' => false, 'error' => $e->getMessage()];
		}
	}
}
