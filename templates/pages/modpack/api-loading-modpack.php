<?php

use React\EventLoop\Loop;
use React\Http\Browser;

// Модпак ещё не в базе - покажем загрузку и загрузим с API
$isLoading = true;
$browser = new Browser();
$apiData = null;

if ($platform === 'modrinth') {
	$browser->get("https://api.modrinth.com/v2/project/{$slug}", ['User-Agent' => 'CabiWorld/1.0'])->then(
		function ($response) use (&$apiData) {
			$apiData = json_decode((string)$response->getBody(), true);
		},
		function ($e) {}
	);
} else {
	$apiKey = $config['curseforge_api_key'];
	if ($apiKey) {
		$browser->get("https://api.curseforge.com/v1/mods/search?gameId=432&slug={$slug}", [
			'User-Agent' => 'CabiWorld/1.0', 'x-api-key' => $apiKey
		])->then(
			function ($response) use (&$apiData) {
				$data = json_decode((string)$response->getBody(), true);
				$apiData = $data['data'][0] ?? null;
			},
			function ($e) {}
		);
	}
}

Loop::run();

if ($apiData) {
	$mpData = $platform === 'modrinth' ? [
		'external_id' => $apiData['id'], 'slug' => $apiData['slug'], 'name' => $apiData['title'],
		'description' => $apiData['description'], 'icon_url' => $apiData['icon_url'] ?? null,
		'author' => $apiData['team'] ?? 'Unknown', 'downloads' => $apiData['downloads'] ?? 0,
		'follows' => $apiData['followers'] ?? 0, 'external_url' => "https://modrinth.com/modpack/{$apiData['slug']}"
	] : [
		'external_id' => (string)$apiData['id'], 'slug' => $apiData['slug'], 'name' => $apiData['name'],
		'description' => $apiData['summary'] ?? '', 'icon_url' => $apiData['logo']['thumbnailUrl'] ?? null,
		'author' => $apiData['authors'][0]['name'] ?? 'Unknown', 'downloads' => $apiData['downloadCount'] ?? 0,
		'follows' => 0, 'external_url' => "https://www.curseforge.com/minecraft/modpacks/{$apiData['slug']}"
	];
	$modpack = $modpackRepo->getOrCreate($platform, $mpData);
	$isLoading = false;
}

?>