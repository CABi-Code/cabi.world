<div class="profile-banner" style="<?= $bannerStyle ?>">
    <?php if ($isOwner): ?>
        <button class="banner-edit-btn" id="bannerEditBtn">
            <svg width="14" height="14"><use href="#icon-camera"/></svg>
            <?= $profileUser['banner'] ? 'Изменить' : 'Добавить' ?>
        </button>
        <input type="file" id="bannerInput" accept="image/*" hidden>
    <?php endif; ?>
</div>