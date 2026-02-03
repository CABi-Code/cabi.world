<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;

class ModpackSelectorController
{
    /**
     * Возвращает HTML модального окна
     */
    public function modal(Request $request): void
    {
        header('Content-Type: text/html; charset=utf-8');
        require TEMPLATES_PATH . '/components/modpack-selector/modpack-selector.php';
        exit;
    }

    /**
     * Возвращает список модпаков (объединение Modrinth + CurseForge)
     */
    public function list(Request $request): void
    {
        $sort = $request->get('sort', 'downloads');
        
        $modpacks = $this->fetchAllModpacks($sort);
        
        Response::json(['success' => true, 'modpacks' => $modpacks]);
    }

    /**
     * Поиск модпаков
     */
    public function search(Request $request): void
    {
        $query = $request->get('q', '');
        $sort = $request->get('sort', 'downloads');
        
        if (strlen($query) < 2) {
            Response::json(['success' => true, 'modpacks' => []]);
            return;
        }
        
        $modpacks = $this->searchModpacks($query, $sort);
        
        Response::json(['success' => true, 'modpacks' => $modpacks]);
    }

    private function fetchAllModpacks(string $sort): array
    {
        $config = require CONFIG_PATH . '/app.php';
        $modpacks = [];
        
        // Получаем модпаки из базы с заявками
        $modpacks = array_merge(
            $modpacks, 
            $this->fetchFromDatabase($sort)
        );
        
        // Если мало модпаков - догружаем из API
        if (count($modpacks) < 40) {
            $modpacks = array_merge(
                $modpacks,
                $this->fetchFromModrinth($sort),
                $this->fetchFromCurseforge($config, $sort)
            );
        }
        
        // Убираем дубликаты
        $modpacks = $this->deduplicateModpacks($modpacks);
        
        // Сортируем
        $modpacks = $this->sortModpacks($modpacks, $sort);
        
        return array_slice($modpacks, 0, 50);
    }

    private function fetchFromDatabase(string $sort): array
    {
        $db = \App\Core\Database::getInstance();
        
        $orderBy = $sort === 'applications' 
            ? 'accepted_count DESC, downloads DESC'
            : 'downloads DESC';
        
        $rows = $db->fetchAll(
            "SELECT id, platform, external_id, slug, name, icon_url, downloads, accepted_count as app_count
             FROM modpacks 
             WHERE accepted_count > 0
             ORDER BY {$orderBy}
             LIMIT 50"
        );
        
        return array_map(fn($row) => [
            'id' => $row['external_id'],
            'platform' => $row['platform'],
            'slug' => $row['slug'],
            'name' => $row['name'],
            'icon_url' => $row['icon_url'],
            'downloads' => (int)$row['downloads'],
            'app_count' => (int)$row['app_count'],
            'from_db' => true
        ], $rows);
    }

    private function fetchFromModrinth(string $sort): array
    {
        try {
            $apiSort = $sort === 'applications' ? 'downloads' : 'downloads';
            $url = "https://api.modrinth.com/v2/search?facets=[[\"project_type:modpack\"]]&limit=25&index={$apiSort}";
            
            $response = @file_get_contents($url, false, stream_context_create([
                'http' => ['header' => 'User-Agent: CabiWorld/1.0', 'timeout' => 5]
            ]));
            
            if (!$response) return [];
            
            $data = json_decode($response, true);
            
            return array_map(fn($mp) => [
                'id' => $mp['project_id'],
                'platform' => 'modrinth',
                'slug' => $mp['slug'],
                'name' => $mp['title'],
                'icon_url' => $mp['icon_url'] ?? null,
                'downloads' => (int)($mp['downloads'] ?? 0),
                'app_count' => 0
            ], $data['hits'] ?? []);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function fetchFromCurseforge(array $config, string $sort): array
    {
        $apiKey = $config['curseforge_api_key'] ?? '';
        if (!$apiKey) return [];
        
        try {
            $url = "https://api.curseforge.com/v1/mods/search?gameId=432&classId=4471&pageSize=25&sortField=2&sortOrder=desc";
            
            $response = @file_get_contents($url, false, stream_context_create([
                'http' => [
                    'header' => "User-Agent: CabiWorld/1.0\r\nx-api-key: {$apiKey}",
                    'timeout' => 5
                ]
            ]));
            
            if (!$response) return [];
            
            $data = json_decode($response, true);
            
            return array_map(fn($mp) => [
                'id' => (string)$mp['id'],
                'platform' => 'curseforge',
                'slug' => $mp['slug'],
                'name' => $mp['name'],
                'icon_url' => $mp['logo']['thumbnailUrl'] ?? null,
                'downloads' => (int)($mp['downloadCount'] ?? 0),
                'app_count' => 0
            ], $data['data'] ?? []);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function searchModpacks(string $query, string $sort): array
    {
        $modpacks = [];
        
        // Поиск в БД
        $db = \App\Core\Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT id, platform, external_id, slug, name, icon_url, downloads, accepted_count as app_count
             FROM modpacks 
             WHERE name LIKE ?
             ORDER BY downloads DESC
             LIMIT 20",
            ['%' . $query . '%']
        );
        
        foreach ($rows as $row) {
            $modpacks[] = [
                'id' => $row['external_id'],
                'platform' => $row['platform'],
                'slug' => $row['slug'],
                'name' => $row['name'],
                'icon_url' => $row['icon_url'],
                'downloads' => (int)$row['downloads'],
                'app_count' => (int)$row['app_count']
            ];
        }
        
        return $modpacks;
    }

    private function deduplicateModpacks(array $modpacks): array
    {
        $seen = [];
        $result = [];
        
        foreach ($modpacks as $mp) {
            $key = $mp['platform'] . ':' . $mp['slug'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $mp;
            }
        }
        
        return $result;
    }

    private function sortModpacks(array $modpacks, string $sort): array
    {
        usort($modpacks, function($a, $b) use ($sort) {
            if ($sort === 'applications') {
                $cmp = ($b['app_count'] ?? 0) <=> ($a['app_count'] ?? 0);
                if ($cmp !== 0) return $cmp;
            }
            return ($b['downloads'] ?? 0) <=> ($a['downloads'] ?? 0);
        });
        
        return $modpacks;
    }
}
