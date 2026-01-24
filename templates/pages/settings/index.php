<?php /** @var array $user */ ?>

<div class="container-sm">
    <h1 style="font-size:1.25rem;margin-bottom:1.25rem;">Настройки</h1>

	<?php include_once 'settings-card/avatar-and-banner.php'; ?>
    
	<?php include_once 'settings-card/collor-profile.php'; ?>
    
	<?php include_once 'settings-card/profile.php'; ?>
    
	<?php include_once 'settings-card/contacts.php'; ?>
    
	<?php include_once 'settings-card/password.php'; ?>

</div>

<!-- Модальное окно редактора изображений -->
<div id="imgEditorModal" class="modal" style="display:none;">

	<?php include_once 'img-editor-modal.php'; ?>

</div>