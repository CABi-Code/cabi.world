<?php
/** 
 * @var array $user 
 */

use App\Core\Template;

// Используем абсолютные пути для include
$settingsPath = __DIR__ . '/settings-card';
?>

<div class="container-sm">
    <h1 style="font-size:1.25rem;margin-bottom:1.25rem;">Настройки</h1>

    <?php require $settingsPath . '/avatar-and-banner.php'; ?>
    
    <?php require $settingsPath . '/collor-profile.php'; ?>
    
    <?php require $settingsPath . '/profile.php'; ?>
    
    <?php require $settingsPath . '/contacts.php'; ?>
    
    <?php require $settingsPath . '/password.php'; ?>
    
    <?php require $settingsPath . '/privacy.php'; ?>

</div>