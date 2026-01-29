<?php
/**
 * Таблица заявок в админ-панели
 * Вся строка кликабельна для открытия модалки
 */
use App\Core\Role;
?>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Пользователь</th>
                <th>Модпак</th>
                <th>Сообщение</th>
                <th>Дата</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($applications as $app): 
                $avatarColors = explode(',', $app['avatar_bg_value'] ?? '#3b82f6,#8b5cf6');
                $avatarStyle = 'background:linear-gradient(135deg,' . ($avatarColors[0] ?? '#3b82f6') . ',' . ($avatarColors[1] ?? $avatarColors[0]) . ')';
            ?>
                <tr data-app-id="<?= $app['id'] ?>" class="admin-row-clickable">
                    <td class="admin-td-id">#<?= $app['id'] ?></td>
                    <td>
                        <a href="/@<?= e($app['login']) ?>" class="admin-user-link" onclick="event.stopPropagation()">
                            <div class="admin-avatar" style="<?= empty($app['avatar']) ? $avatarStyle : '' ?>">
                                <?php if ($app['avatar']): ?>
                                    <img src="<?= e($app['avatar']) ?>" alt="">
                                <?php else: ?>
                                    <?= mb_strtoupper(mb_substr($app['username'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="admin-username">
                                    <?= e($app['username']) ?>
                                    <?= Role::badge($app['role'] ?? 'user') ?>
                                </div>
                                <div class="admin-login">@<?= e($app['login']) ?></div>
                            </div>
                        </a>
                    </td>
                    <td>
                        <a href="/modpack/<?= e($app['platform']) ?>/<?= e($app['slug']) ?>" 
                           class="admin-modpack-link" onclick="event.stopPropagation()">
                            <?php if ($app['icon_url']): ?>
                                <img src="<?= e($app['icon_url']) ?>" alt="" class="admin-mp-icon">
                            <?php endif; ?>
                            <?= e($app['modpack_name']) ?>
                        </a>
                    </td>
                    <td class="admin-td-message">
                        <div class="admin-message-preview" title="<?= e($app['message']) ?>">
                            <?= e(mb_substr($app['message'], 0, 100)) ?><?= mb_strlen($app['message']) > 100 ? '...' : '' ?>
                        </div>
                    </td>
                    <td class="admin-td-date">
                        <?= date('d.m.Y', strtotime($app['created_at'])) ?><br>
                        <span class="admin-time"><?= date('H:i', strtotime($app['created_at'])) ?></span>
                    </td>
                    <td>
                        <span class="app-status status-<?= $app['status'] ?>" data-status>
                            <?= match($app['status']) { 
                                'pending' => 'Ожидает', 
                                'accepted' => 'Одобрена', 
                                'rejected' => 'Отклонена', 
                                default => $app['status'] 
                            } ?>
                        </span>
                    </td>
                    <td class="admin-td-actions">
                        <div class="admin-actions">
                            <?php if ($app['status'] !== 'accepted'): ?>
                                <button class="btn btn-sm admin-btn-accept" 
                                        onclick="event.stopPropagation();setAppStatus(<?= $app['id'] ?>, 'accepted')" 
                                        title="Одобрить">
                                    <svg width="14" height="14"><use href="#icon-check"/></svg>
                                </button>
                            <?php endif; ?>
                            <?php if ($app['status'] !== 'rejected'): ?>
                                <button class="btn btn-sm admin-btn-reject" 
                                        onclick="event.stopPropagation();setAppStatus(<?= $app['id'] ?>, 'rejected')" 
                                        title="Отклонить">
                                    <svg width="14" height="14"><use href="#icon-x"/></svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
