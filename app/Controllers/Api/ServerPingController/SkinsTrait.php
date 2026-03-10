<?php

namespace App\Controllers\Api\ServerPingController;

use App\Http\Request;
use App\Http\Response;
use App\Repository\ServerPingRepository;

trait SkinsTrait
{
	
	/**
	 * Получение скинов игроков по списку из пинга
	 * Поддержка: Mojang, Ely.by, TLauncher
	 */
	private function resolvePlayerSkins(array $players): array
	{
		if (empty($players)) return [];

		$result = [];
		$curlHandles = [];
		$mh = curl_multi_init();

		foreach ($players as $i => $player) {
			$name = $player['name'] ?? '';
			$uuid = $player['uuid'] ?? $player['id'] ?? null;
			if ($uuid) $uuid = str_replace('-', '', $uuid);
			if (!$name && !$uuid) continue;

			$result[$i] = [
				'name' => $name,
				'uuid' => $uuid,
				'head' => null,
				'skin' => null,
				'source' => null,
			];

			// Подготавливаем параллельные запросы
			$handles = [];

			// По UUID — Mojang session server
			if ($uuid) {
				$handles['mojang_uuid'] = $this->createCurl(
					"https://sessionserver.mojang.com/session/minecraft/profile/{$uuid}"
				);
			}

			// По нику — Mojang API
			if ($name) {
				$handles['mojang_name'] = $this->createCurl(
					"https://api.mojang.com/users/profiles/minecraft/{$name}"
				);
			}

			// Ely.by — по нику (основной для пиратских лаунчеров)
			if ($name) {
				$handles['elyby'] = $this->createCurl(
					"https://authserver.ely.by/api/users/profiles/minecraft/{$name}"
				);
			}

			// TLauncher (TLauncher Skins API)
			if ($name) {
				$handles['tlauncher'] = $this->createCurl(
					"https://auth.tlauncher.org/skin/profile/texture/login/{$name}"
				);
			}

			$curlHandles[$i] = $handles;
			foreach ($handles as $ch) {
				curl_multi_add_handle($mh, $ch);
			}
		}

		// Выполняем все параллельно
		$running = null;
		do {
			curl_multi_exec($mh, $running);
			curl_multi_select($mh);
		} while ($running > 0);

		// Обрабатываем результаты
		foreach ($curlHandles as $i => $handles) {
			$skinByUuid = null;
			$skinByName = null;
			$skinElyby = null;
			$skinTlauncher = null;

			// Mojang UUID
			if (isset($handles['mojang_uuid'])) {
				$skinByUuid = $this->extractMojangSkin($handles['mojang_uuid']);
				curl_multi_remove_handle($mh, $handles['mojang_uuid']);
				curl_close($handles['mojang_uuid']);
			}

			// Mojang Name → получаем UUID → потом текстуры
			if (isset($handles['mojang_name'])) {
				$nameData = json_decode(curl_multi_getcontent($handles['mojang_name']), true);
				curl_multi_remove_handle($mh, $handles['mojang_name']);
				curl_close($handles['mojang_name']);

				if ($nameData && !empty($nameData['id'])) {
					$nameUuid = $nameData['id'];
					// Дополнительный запрос за текстурами
					$skinByName = $this->fetchMojangSkinByUuid($nameUuid);
				}
			}

			// Ely.by
			if (isset($handles['elyby'])) {
				$elyData = json_decode(curl_multi_getcontent($handles['elyby']), true);
				curl_multi_remove_handle($mh, $handles['elyby']);
				curl_close($handles['elyby']);

				if ($elyData && !empty($elyData['id'])) {
					$skinElyby = $this->fetchElybySkin($elyData['id']);
				}
			}

			// TLauncher
			if (isset($handles['tlauncher'])) {
				$tlData = json_decode(curl_multi_getcontent($handles['tlauncher']), true);
				curl_multi_remove_handle($mh, $handles['tlauncher']);
				curl_close($handles['tlauncher']);

				if ($tlData && !empty($tlData['SKIN']['url'])) {
					$skinTlauncher = $this->downloadAndProcessSkin($tlData['SKIN']['url'], 'tlauncher');
				}
			}

			// Приоритетный выбор скина
			$chosen = $this->chooseBestSkin($skinByUuid, $skinByName, $skinElyby, $skinTlauncher);

			if ($chosen) {
				$result[$i]['head'] = $chosen['head'];
				$result[$i]['skin'] = $chosen['skin'];
				$result[$i]['source'] = $chosen['source'];
			}
		}

		curl_multi_close($mh);

		return array_values($result);
	}
		
	/**
	 * Извлечение URL скина из ответа Mojang session server
	 */
	private function extractMojangSkin(\CurlHandle $ch): ?array
	{
		$body = curl_multi_getcontent($ch);
		$data = json_decode($body, true);

		if (!$data || empty($data['properties'])) return null;

		foreach ($data['properties'] as $prop) {
			if ($prop['name'] === 'textures') {
				$decoded = json_decode(base64_decode($prop['value']), true);
				$skinUrl = $decoded['textures']['SKIN']['url'] ?? null;
				if ($skinUrl) {
					return $this->downloadAndProcessSkin($skinUrl, 'mojang');
				}
			}
		}

		return null;
	}

	/**
	 * Отдельный запрос к Mojang за скином по UUID
	 */
	private function fetchMojangSkinByUuid(string $uuid): ?array
	{
		$ch = curl_init("https://sessionserver.mojang.com/session/minecraft/profile/{$uuid}");
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 5,
			CURLOPT_USERAGENT => 'MCMonitor/1.0',
		]);
		$body = curl_exec($ch);
		curl_close($ch);

		$data = json_decode($body, true);
		if (!$data || empty($data['properties'])) return null;

		foreach ($data['properties'] as $prop) {
			if ($prop['name'] === 'textures') {
				$decoded = json_decode(base64_decode($prop['value']), true);
				$skinUrl = $decoded['textures']['SKIN']['url'] ?? null;
				if ($skinUrl) {
					return $this->downloadAndProcessSkin($skinUrl, 'mojang');
				}
			}
		}

		return null;
	}

	/**
	 * Скин с Ely.by
	 */
	private function fetchElybySkin(string $elyId): ?array
	{
		// Ely.by texture endpoint
		$ch = curl_init("https://authserver.ely.by/api/users/profiles/minecraft/" . urlencode($elyId));
		// Попробуем прямой URL скина
		$skinUrl = "https://skinsystem.ely.by/skins/{$elyId}.png";
		return $this->downloadAndProcessSkin($skinUrl, 'elyby');
	}

	/**
	 * Скачать скин, вырезать голову, вернуть оба в base64
	 */
	private function downloadAndProcessSkin(string $url, string $source): ?array
	{
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 5,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_USERAGENT => 'MCMonitor/1.0',
		]);
		$imgData = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($httpCode !== 200 || !$imgData) return null;

		$skin = @imagecreatefromstring($imgData);
		if (!$skin) return null;

		$skinW = imagesx($skin);
		$skinH = imagesy($skin);

		// Размер головы в текстуре: 8x8 пикселей, начиная с (8, 8)
		// Оверлей головы: 8x8, начиная с (40, 8)
		$headSize = 8;
		$head = imagecreatetruecolor($headSize, $headSize);
		imagesavealpha($head, true);
		imagealphablending($head, false);
		$transparent = imagecolorallocatealpha($head, 0, 0, 0, 127);
		imagefill($head, 0, 0, $transparent);
		imagealphablending($head, true);

		// Основной слой головы
		imagecopy($head, $skin, 0, 0, 8, 8, $headSize, $headSize);

		// Оверлей (шляпа) — если есть
		if ($skinW >= 48 && $skinH >= 16) {
			imagecopy($head, $skin, 0, 0, 40, 8, $headSize, $headSize);
		}

		// Масштабируем голову до 64x64 для чёткости
		$headScaled = imagecreatetruecolor(64, 64);
		imagesavealpha($headScaled, true);
		imagealphablending($headScaled, false);
		imagefill($headScaled, 0, 0, $transparent);
		imagealphablending($headScaled, true);
		imagecopyresampled($headScaled, $head, 0, 0, 0, 0, 64, 64, $headSize, $headSize);

		// Голова → base64
		ob_start();
		imagepng($headScaled);
		$headBase64 = 'data:image/png;base64,' . base64_encode(ob_get_clean());

		// Полный скин → base64
		ob_start();
		imagepng($skin);
		$skinBase64 = 'data:image/png;base64,' . base64_encode(ob_get_clean());

		imagedestroy($skin);
		imagedestroy($head);
		imagedestroy($headScaled);

		return [
			'head' => $headBase64,
			'skin' => $skinBase64,
			'source' => $source,
		];
	}

	/**
	 * Выбор лучшего скина из нескольких источников
	 */
	private function chooseBestSkin(?array $byUuid, ?array $byName, ?array $elyby, ?array $tlauncher): ?array
	{
		// Если есть оба Mojang — сравниваем
		if ($byUuid && $byName) {
			// Одинаковые → по нику (он каноничнее)
			if ($byUuid['skin'] === $byName['skin']) {
				return $byName;
			}
			// Разные → по UUID (более надёжный идентификатор)
			return $byUuid;
		}

		// Один из Mojang
		if ($byUuid) return $byUuid;
		if ($byName) return $byName;

		// Альтернативные источники
		if ($elyby) return $elyby;
		if ($tlauncher) return $tlauncher;

		return null;
	}

}