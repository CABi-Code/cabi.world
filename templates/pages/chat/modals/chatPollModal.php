<?

// присоеденен в файле pages/chat/index.php через include

?>

<div class="modal" id="chatPollModal" style="display:none;">
    <div class="modal-backdrop" data-close></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Создать опрос</h3>
            <button class="modal-close" data-close>&times;</button>
        </div>
        <div class="modal-body">
            <form id="chatPollForm">
                <div class="form-group">
                    <label class="form-label">Вопрос</label>
                    <input type="text" name="question" class="form-input" maxlength="500" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Варианты ответа</label>
                    <div id="pollOptions">
                        <div class="poll-option-row">
                            <input type="text" name="options[]" class="form-input" placeholder="Вариант 1" maxlength="200" required>
                        </div>
                        <div class="poll-option-row">
                            <input type="text" name="options[]" class="form-input" placeholder="Вариант 2" maxlength="200" required>
                        </div>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm" id="addPollOption" style="margin-top:0.5rem;">
                        <svg width="14" height="14"><use href="#icon-plus"/></svg>
                        Добавить вариант
                    </button>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_multiple">
                        <span>Множественный выбор</span>
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-ghost" data-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Создать опрос</button>
                </div>
            </form>
        </div>
    </div>
</div>