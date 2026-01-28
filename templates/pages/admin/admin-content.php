<?

// присоеденен в файле pages/admin/index.php через include

?>

<?php use App\Core\Role; ?>


<main class="admin-content">
	<div class="admin-card">
		<div class="admin-card-header">
			<h2>Модерация заявок</h2>
			<div class="admin-filters">
				<a href="/admin?status=pending" class="admin-filter-btn <?= $status === 'pending' ? 'active' : '' ?>">
					На рассмотрении
					<?php if ($pendingCount > 0): ?>
						<span class="filter-count"><?= $pendingCount ?></span>
					<?php endif; ?>
				</a>
				<a href="/admin?status=accepted" class="admin-filter-btn <?= $status === 'accepted' ? 'active' : '' ?>">Одобренные</a>
				<a href="/admin?status=rejected" class="admin-filter-btn <?= $status === 'rejected' ? 'active' : '' ?>">Отклонённые</a>
				<a href="/admin" class="admin-filter-btn <?= !$status ? 'active' : '' ?>">Все</a>
			</div>
		</div>
		
		<?php if (empty($applications)): ?>
			<div class="admin-empty">
				<svg width="48" height="48"><use href="#icon-check"/></svg>
				<p>Нет заявок для отображения</p>
			</div>
		<?php else: ?>
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
						<?php foreach ($applications as $app): ?>
							<tr data-app-id="<?= $app['id'] ?>">
								<td class="admin-td-id">#<?= $app['id'] ?></td>
								<td>
									<a href="/@<?= e($app['login']) ?>" class="admin-user-link">
										<div class="admin-avatar">
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
									<a href="/modpack/<?= e($app['platform']) ?>/<?= e($app['slug']) ?>" class="admin-modpack-link">
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
									<?php if ($app['relevant_until']): ?>
										<div class="admin-relevant">
											До: <?= date('d.m.Y', strtotime($app['relevant_until'])) ?>
										</div>
									<?php endif; ?>
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
											<button class="btn btn-sm admin-btn-accept" onclick="setAppStatus(<?= $app['id'] ?>, 'accepted')" title="Одобрить">
												<svg width="14" height="14"><use href="#icon-check"/></svg>
											</button>
										<?php endif; ?>
										<?php if ($app['status'] !== 'rejected'): ?>
											<button class="btn btn-sm admin-btn-reject" onclick="setAppStatus(<?= $app['id'] ?>, 'rejected')" title="Отклонить">
												<svg width="14" height="14"><use href="#icon-x"/></svg>
											</button>
										<?php endif; ?>
										<button class="btn btn-ghost btn-sm" onclick="viewAppDetails(<?= $app['id'] ?>)" title="Подробнее">
											<svg width="14" height="14"><use href="#icon-eye"/></svg>
										</button>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			
			<?php if ($totalPages > 1): ?>
				<div class="pagination" style="margin-top:1rem;">
					<?php 
					$baseUrl = '/admin' . ($status ? '?status=' . e($status) : '');
					$sep = $status ? '&' : '?';
					?>
					<?php if ($page > 1): ?>
						<a href="<?= $baseUrl . $sep ?>page=<?= $page - 1 ?>" class="page-item">&laquo;</a>
					<?php endif; ?>
					<?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
						<a href="<?= $baseUrl . $sep ?>page=<?= $i ?>" class="page-item <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
					<?php endfor; ?>
					<?php if ($page < $totalPages): ?>
						<a href="<?= $baseUrl . $sep ?>page=<?= $page + 1 ?>" class="page-item">&raquo;</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</main>