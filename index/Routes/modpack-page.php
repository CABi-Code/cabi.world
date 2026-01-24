<?php

use App\Repository\ModpackRepository;

// Modpack page
if (preg_match('#^/modpack/(modrinth|curseforge)/([a-zA-Z0-9_-]+)$#', $uri, $matches)) {
    $platform = $matches[1];
    $slug = $matches[2];
    
    $modpackRepo = new ModpackRepository();
    $modpack = $modpackRepo->findBySlug($platform, $slug);
    
    $title = ($modpack['name'] ?? $slug) . ' — cabi.world';
    ob_start();
    require TEMPLATES_PATH . '/pages/modpack.php';
    $content = ob_get_clean();
    require TEMPLATES_PATH . '/layouts/main.php';
    exit;
}

?>