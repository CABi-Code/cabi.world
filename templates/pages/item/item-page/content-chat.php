<?php
/**
 * Контент для элемента типа "chat"
 * @var array $item
 * @var array|null $user
 */
?>
<div class="item-content item-content-chat">
    <div class="chat-embed" id="chatEmbed" data-chat-id="<?= $item['id'] ?>">
        <div class="chat-messages" id="chatMessages">
            <div class="chat-loading">
                <div class="spinner"></div>
                <span>Загрузка сообщений...</span>
            </div>
        </div>
        
        <?php if ($user): ?>
            <div class="chat-input-wrapper">
                <form id="chatForm" class="chat-form">
                    <input type="text" 
                           id="chatInput" 
                           placeholder="Написать сообщение..." 
                           maxlength="1000" 
                           autocomplete="off">
                    <button type="submit" class="chat-send">
                        <svg width="18" height="18"><use href="#icon-send"/></svg>
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="chat-login-prompt">
                <a href="/login">Войдите</a>, чтобы писать сообщения
            </div>
        <?php endif; ?>
    </div>
    
    <a href="/chat/<?= $item['id'] ?>" class="btn btn-secondary btn-block">
        <svg width="16" height="16"><use href="#icon-maximize"/></svg>
        Открыть чат на весь экран
    </a>
</div>
