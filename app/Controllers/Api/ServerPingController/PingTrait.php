<?php

namespace App\Controllers\Api\ServerPingController;

use App\Http\Request;
use App\Http\Response;
use App\Repository\ServerPingRepository;

trait PingTrait
{

	/**
	 * Нормализация ответа mcsrvstat.us в единый формат
	 */
	private function normalizeMcsrvstatResponse(array $data): array
	{
		// Формируем список игроков
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
	 * Прокси для пинга сервера с клиента
	 */
	public function ping(Request $request): void
	{
		$ip = trim($request->query('ip', ''));
		$port = (int)$request->query('port', 25565);
		$simpl = (bool)$request->query('simpl', true);
		
		$result;
		
		if (empty($ip)) {
			Response::error('IP required', 400);
			return;
		}

		if (!$this->isValidAddress($ip)) {
			Response::error('Invalid address', 400);
			return;
		}
		
		// Сначала пробуем свой пинг
		if ($simpl === true)
			$result = $this->SimplifiedPingMinecraftServer($ip, $port);
		else
			$result = $this->pingMinecraftServer($ip, $port);
		
		

		// Если свой пинг не сработал — фоллбэк на mcsrvstat.us
		if (!$result['online']) {
			$fallback = $this->pingViaMcsrvstat($ip, $port);
			if ($fallback['online']) {
				$fallback['source'] = 'mcsrvstat';
				Response::json($fallback);
				return;
			}
			// Оба не сработали — отдаём ошибку своего пинга
			$result['source'] = 'self';
			Response::json($result);
			return;
		}

		$result['source'] = 'self';
		Response::json($result);
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
	 * Свой пинг — теперь возвращает расширенный формат, аналогичный mcsrvstat
	 */
	private function pingMinecraftServer(string $ip, int $port): array
	{
		// Резолвим SRV-запись для доменов
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

		// Для доменов резолвим IP через gethostbyname
		$connectIp = filter_var($resolvedIp, FILTER_VALIDATE_IP)
			? $resolvedIp
			: gethostbyname($resolvedIp);

		$result = @socket_connect($socket, $connectIp, $resolvedPort);
		if (!$result) {
			socket_close($socket);
			return ['online' => false, 'error' => 'Connection failed'];
		}

		try {
			// Замеряем латентность
			$pingStart = microtime(true);

			// Handshake packet (protocol 767 = 1.21)
			$protocolVersion = 767;
			$handshakeHost = $hostname ?? $ip;
			$handshake = $this->packVarInt(0x00)
				. $this->packVarInt($protocolVersion)
				. $this->packVarInt(strlen($handshakeHost)) . $handshakeHost
				. pack('n', $resolvedPort)
				. $this->packVarInt(1); // Next state: status

			$packet = $this->packVarInt(strlen($handshake)) . $handshake;
			socket_write($socket, $packet, strlen($packet));

			// Status request
			$statusRequest = $this->packVarInt(1) . $this->packVarInt(0x00);
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

			$latency = round((microtime(true) - $pingStart) * 1000);

			socket_close($socket);

			$data = json_decode($response, true);
			if (!$data) {
				return ['online' => false, 'error' => 'Invalid response'];
			}

			// Парсим MOTD
			$motd = $this->parseMotd($data['description'] ?? '');

			// Парсим список игроков
			$playersList = [];
			if (!empty($data['players']['sample'])) {
				foreach ($data['players']['sample'] as $p) {
					$playersList[] = [
						'name' => $p['name'] ?? 'Unknown',
						'uuid' => $p['id'] ?? null,
					];
				}
			}
		
			// Резолвим скины игроков
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
		// Резолвим SRV-запись для доменов
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

		// Для доменов резолвим IP через gethostbyname
		$connectIp = filter_var($resolvedIp, FILTER_VALIDATE_IP)
			? $resolvedIp
			: gethostbyname($resolvedIp);

		$result = @socket_connect($socket, $connectIp, $resolvedPort);
		if (!$result) {
			socket_close($socket);
			return ['online' => false];
		}

		try {
			// Handshake packet (protocol 767 = 1.21)
			$protocolVersion = 767;
			$handshakeHost = $hostname ?? $ip;
			$handshake = $this->packVarInt(0x00)
				. $this->packVarInt($protocolVersion)
				. $this->packVarInt(strlen($handshakeHost)) . $handshakeHost
				. pack('n', $resolvedPort)
				. $this->packVarInt(1); // Next state: status

			$packet = $this->packVarInt(strlen($handshake)) . $handshake;
			socket_write($socket, $packet, strlen($packet));

			// Status request
			$statusRequest = $this->packVarInt(1) . $this->packVarInt(0x00);
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