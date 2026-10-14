<h1 class="h3 mb-3">Kho nguyên liệu</h1>
<?php include APP_PATH . '/views/inventory/_tabs.php'; ?>

<form method="get" action="<?= url('inventory/history') ?>" class="row g-2 mb-3" style="max-width:980px">
  <div class="col-sm-2"><input class="form-control" type="date" name="from" value="<?= e($from) ?>"></div>
  <div class="col-sm-2"><input class="form-control" type="date" name="to" value="<?= e($to) ?>"></div>
  <?php if ($isAdmin): ?>
  <div class="col-sm-3">
    <select class="form-select" name="branch">
      <option value="0">Tất cả chi nhánh</option>
      <?php foreach ($branches as $b): ?>
        <option value="<?= (int)$b['id'] ?>" <?= (int)$b['id'] === $bid ? 'selected' : '' ?>><?= e($b['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>
  <div class="col-sm-2">
    <select class="form-select" name="type">
      <option value="all">Mọi loại phiếu</option>
      <?php foreach (Inventory::TYPES as $k => $label): ?><option value="<?= $k ?>" <?= $type === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-sm-3">
    <select class="form-select" name="ingredient">
      <option value="0">Mọi nguyên liệu</option>
      <?php foreach ($ingredients as $i): ?><option value="<?= (int)$i['id'] ?>" <?= $ingId === (int)$i['id'] ? 'selected' : '' ?>><?= e($i['name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-12"><button class="btn btn-outline-dark">Xem</button></div>
</form>

<div class="table-responsive">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light"><tr><th>Thời gian</th><?php if ($isAdmin): ?><th>Chi nhánh</th><?php endif; ?><th>Nguyên liệu</th><th>Loại</th><th class="text-end">Số lượng</th><th>Ghi chú</th><th>Người ghi</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r):
    $q = (float)$r['quantity'];
    $sign = $r['type'] === 'in' ? '+' : ($r['type'] === 'out' ? '−' : ($q >= 0 ? '+' : '−'));
    $cls  = $r['type'] === 'in' ? 'success' : ($r['type'] === 'out' ? 'danger' : 'dark');
    $show = abs($q); ?>
    <tr>
      <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
      <?php if ($isAdmin): ?><td><?= e($r['branch_name']) ?></td><?php endif; ?>
      <td class="fw-semibold"><?= e($r['ingredient_name']) ?></td>
      <td><span class="badge bg-<?= $cls ?>"><?= e(Inventory::TYPES[$r['type']]) ?></span></td>
      <td class="text-end text-nowrap text-<?= $cls ?>"><?= $sign ?> <?= qty_fmt($show) ?> <?= e($r['unit']) ?></td>
      <td class="small"><?= e($r['note'] ?? '') ?></td>
      <td class="small"><?= e($r['user_name'] ?? '') ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">Không có phiếu nào trong khoảng này.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php if (count($rows) >= $limit): ?><p class="text-muted small">Chỉ hiện <?= (int)$limit ?> phiếu mới nhất. Hãy thu hẹp khoảng ngày hoặc lọc thêm để xem đủ.</p><?php endif; ?>
