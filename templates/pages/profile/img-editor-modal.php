<?php if ($isOwner): ?>
	<!-- Image Editor Modal -->
	<div id="imgEditorModal" class="modal" style="display:none;">
		<div class="modal-overlay" data-close></div>
		<div class="modal-content">
			<h3 id="editorTitle">Редактировать</h3>
			<div class="img-editor">
				<div class="img-preview" id="editorPreview"></div>
				<div class="img-controls">
					<div class="zoom-range">
						<svg width="14" height="14"><use href="#icon-zoom-out"/></svg>
						<input type="range" id="zoomRange" min="1" max="3" step="0.05" value="1">
						<svg width="14" height="14"><use href="#icon-zoom-in"/></svg>
					</div>
				</div>
			</div>
			<div class="modal-actions">
				<button type="button" class="btn btn-secondary btn-sm" data-close>Отмена</button>
				<button type="button" class="btn btn-primary btn-sm" id="saveImgBtn">Сохранить</button>
			</div>
		</div>
	</div>

	<!-- Edit Application Modal -->
	<?php 
	$application = [];
	$modalId = 'editAppModal';
	require TEMPLATES_PATH . '/components/edit-application-modal.php'; 
	?>

	<?php include_once 'js-script.php'; ?>
<?php endif; ?>