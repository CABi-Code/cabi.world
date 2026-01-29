<?php
/**
 * Страница чата сообщества
 * 
 * @var array $chat - данные чата
 * @var array $community - данные сообщества
 * @var array $owner - владелец сообщества
 * @var array|null $user - текущий пользователь
 * @var bool $isOwner - является ли текущий пользователь владельцем
 * @var bool $isModerator - является ли текущий пользователь модератором
 * @var bool $isBanned - забанен ли пользователь
 * @var array $settings - эффективные настройки чата
 */

use App\Core\Role;

$canSendMessages = $user && !$isBanned && !$settings['messages_disabled'];
$canSendFiles = $canSendMessages && !$settings['files_disabled'] && Role::isPremium($user['role'] ?? null);
?>

<div class="chat-page" data-chat-id="<?= $chat['id'] ?>">
    <!-- Заголовок -->
    <div class="chat-header">
        <a href="/@<?= e($owner['login']) ?>?tab=community" class="chat-back">
            <svg width="20" height="20"><use href="#icon-arrow-left"/></svg>
        </a>
        
        <div class="chat-info">
            <h1 class="chat-title"><?= e($chat['name']) ?></h1>
            <span class="chat-subtitle">
                <a href="/@<?= e($owner['login']) ?>"><?= e($owner['username']) ?></a>
                <?php if ($chat['description']): ?>
                    · <?= e($chat['description']) ?>
                <?php endif; ?>
            </span>
        </div>
        
        <?php if ($isOwner || $isModerator): ?>
        <div class="chat-actions-header">
            <button class="btn btn-ghost btn-sm btn-icon" onclick="openChatSettings()" title="Настройки">
                <svg width="18" height="18"><use href="#icon-settings"/></svg>
            </button>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Сообщения -->
    <div class="chat-messages-container" id="chatMessagesContainer">
        <div class="chat-messages" id="chatMessages">
            <div class="chat-messages-loading">Загрузка сообщений...</div>
        </div>
    </div>
    
    <!-- Форма отправки -->
    <?php if ($isBanned): ?>
        <div class="chat-input-container">
            <div class="chat-banned-notice">
                <svg width="16" height="16"><use href="#icon-ban"/></svg>
                Вы заблокированы в этом чате
            </div>
        </div>
    <?php elseif ($settings['messages_disabled']): ?>
        <div class="chat-input-container">
            <div class="chat-disabled-notice">
                <svg width="16" height="16"><use href="#icon-message-circle-off"/></svg>
                Отправка сообщений отключена
            </div>
        </div>
    <?php elseif (!$user): ?>
        <div class="chat-input-container">
            <div class="chat-disabled-notice">
                <a href="/login">Войдите</a>, чтобы отправлять сообщения
            </div>
        </div>
    <?php else: ?>
        <div class="chat-input-container">
            <div id="chatTimeoutNotice" class="chat-timeout-notice" style="display:none;"></div>
            
            <form id="chatMessageForm">
                <div class="chat-input-wrapper">
                    <div class="chat-input-field">
                        <textarea 
                            id="chatInput" 
                            class="chat-input" 
                            placeholder="Написать сообщение..." 
                            rows="1"
                            maxlength="2000"
                        ></textarea>
                        
                        <div id="chatImagePreview" class="chat-image-preview" style="display:none;"></div>
                        
                        <div class="chat-input-toolbar">
                            <?php if ($canSendFiles): ?>
                            <button type="button" class="chat-input-btn" id="chatAttachBtn" title="Прикрепить фото">
                                <svg width="18" height="18"><use href="#icon-image"/></svg>
                            </button>
                            <input type="file" id="chatImageInput" accept="image/*" multiple hidden>
                            <?php endif; ?>
                            
                            <button type="button" class="chat-input-btn" id="chatPollBtn" title="Создать опрос">
                                <svg width="18" height="18"><use href="#icon-bar-chart"/></svg>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="chat-send-btn" id="chatSendBtn">
                        <svg width="20" height="20"><use href="#icon-send"/></svg>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Модальное окно создания опроса -->
<?php include __DIR__ . '/modals/chatPollModal.php'; ?>

<!-- Модальное окно настроек чата (для владельца/модератора) -->
<?php include __DIR__ . '/modals/chatSettingsModal.php'; ?>

<?php include __DIR__ . '/js-script.php'; ?>
