<h1 class="h3 mb-3">Thực đơn</h1>
<?php include APP_PATH . '/views/menu/_tabs.php'; ?>

<div class="row g-3 mb-3">
  <div class="col-sm-4"><div class="card border-success"><div class="card-body">
    <div class="text-muted">Món đã có công thức</div><div class="fs-4 fw-semibold text-success"><?= (int)$done ?> / <?= count($items) ?></div></div></div></div>
  <div class="col-sm-4"><div class="card <?= count($items) - $done > 0 ? 'border-warning' : 'border-secondary' ?>"><div class="card-body">
    <div class="text-muted">Món chưa có công thức (không tự trừ kho)</div><div class="fs-4 fw-semibold"><?= count($items) - (int)$done ?></div></div></div></div>
</div>

<div class="table-responsive">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light"><tr><th>Món</th><th>Danh mục</th><th>Nguyên liệu cho 1 phần</th><th class="text-end">Thao tác</th></tr></thead>
  <tbody>
  <?php foreach ($items as $it): ?>
    <tr class="<?= (int)$it['status'] === 1 ? '' : 'text-muted' ?>">
      <td class="fw-semibold"><?= e($it['name']) ?></td>
      <td><?= e($it['category_name']) ?></td>
      <td class="small">
        <?php if ($it['lines']): ?>
          <?= e(implode(' · ', array_map(fn($l) => $l['name'] . ' ' . qty_fmt($l['quantity']) . ' ' . $l['unit'], $it['lines']))) ?>
        <?php else: ?>
          <span class="badge bg-warning text-dark">Chưa có công thức</span>
        <?php endif; ?>
      </td>
      <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= url('recipes/edit/' . (int)$it['id']) ?>"><?= $isAdmin ? 'Sửa công thức' : 'Xem' ?></a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$items): ?><tr><td colspan="4" class="text-center text-muted py-4">Chưa có món nào trong thực đơn.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<p class="text-muted small">Khi một đơn được thanh toán, hệ thống tự trừ kho của chi nhánh theo công thức (định lượng 1 phần nhân số phần đã bán). Món chưa có công thức sẽ không trừ kho.</p>
