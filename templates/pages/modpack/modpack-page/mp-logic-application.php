<?php if (!$user): ?>
	<p style="color:var(--text-secondary);"><a href="/login">Войдите</a> или <a href="/register">зарегистрируйтесь</a>, чтобы оставить заявку.</p>
<?php elseif ($userApplication): ?>
	<div style="background:var(--primary-light);border-radius:6px;padding:1rem;border:1px solid rgba(59,130,246,0.2);">
		<h4 style="font-size:0.875rem;margin-bottom:0.5rem;color:var(--text-secondary);">Ваша заявка</h4>
		<div class="app-card <?= $userApplication['status'] === 'pending' ? 'pending' : '' ?>" style="background:var(--surface);">
			<span class="app-status status-<?= $userApplication['status'] ?>">
				<?= match($userApplication['status']) { 'pending'=>'На рассмотрении', 'accepted'=>'Одобрена', 'rejected'=>'Отклонена', default=>$userApplication['status'] } ?>
			</span>
			<p style="margin:0.5rem 0;line-height:1.5;"><?= nl2br(e($userApplication['message'])) ?></p>
			<?php if ($userApplication['relevant_until']): ?>
				<p style="font-size:0.8125rem;color:var(--text-muted);margin-bottom:0.5rem;">
					Актуально до: <?= date('d.m.Y', strtotime($userApplication['relevant_until'])) ?>
				</p>
			<?php endif; ?>
			<div style="display:flex;gap:0.5rem;margin-top:0.75rem;">
				<button class="btn btn-secondary btn-sm" data-modal="editMyAppModal">
					<svg width="12" height="12"><use href="#icon-edit"/></svg>Редактировать
				</button>
				<button class="btn btn-ghost btn-sm" style="color:var(--danger)" onclick="deleteMyApp(<?= $userApplication['id'] ?>)">
					<svg width="12" height="12"><use href="#icon-trash"/></svg>Удалить
				</button>
			</div>
		</div>
	</div>
<?php else: ?>
	<form id="applicationForm">
		<input type="hidden" name="modpack_id" value="<?= $modpack['id'] ?>">
		<div class="form-group">
			<label class="form-label">Сообщение <span style="font-weight:400;color:var(--text-muted);">(макс. 2000 символов)</span></label>
			<textarea name="message" class="form-input" rows="3" required placeholder="Расскажите о себе, опыте игры..." maxlength="2000"></textarea>
		</div>
		<div class="form-group">
			<label class="form-label">Актуально до <span style="font-weight:400;color:var(--text-muted);">(обязательно, макс. 1 месяц)</span></label>
			<input type="date" name="relevant_until" class="form-input" value="<?= $defaultRelevantDate ?>" min="<?= date('Y-m-d') ?>" max="<?= $maxRelevantDate ?>" required>
			<div class="form-hint">Влияет на сортировку в поиске. После этой даты заявка уйдёт вниз списка.</div>
		</div>
		<div class="form-row">
			<div class="form-group">
				<label class="form-label">Discord</label>
				<input type="text" name="discord" class="form-input" value="<?= e($user['discord'] ?? '') ?>">
			</div>
			<div class="form-group">
				<label class="form-label">Telegram</label>
				<input type="text" name="telegram" class="form-input" value="<?= e($user['telegram'] ?? '') ?>">
			</div>
			<div class="form-group">
				<label class="form-label">VK</label>
				<input type="text" name="vk" class="form-input" value="<?= e($user['vk'] ?? '') ?>">
			</div>
		</div>
		<button type="submit" class="btn btn-primary">
			<svg width="14" height="14"><use href="#icon-send"/></svg>Отправить заявку
		</button>
	</form>
<?php endif; ?>