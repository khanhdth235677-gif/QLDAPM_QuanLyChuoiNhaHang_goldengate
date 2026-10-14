<?php $q = $isAdmin ? '?branch=' . (int)$bid : ''; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="h3 m-0">Kho nguyên liệu</h1>
  <div class="d-flex align-items-center gap-2">
    <?php if ($isAdmin && $branches): ?>
      <select class="form-select w-auto" onchange="location = '<?= url('inventory') ?>?branch=' + this.value">
        <?php foreach ($branches as $b): ?>
          <option value="<?= (int)$b['id'] ?>" <?= (int)$b['id'] === $bid ? 'selected' : '' ?>><?= e($b['name']) ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
    <?php if ($bid): ?><a class="btn btn-warning" href="<?= url('inventory/move') . $q ?>"><i class="bi bi-plus-lg"></i> Phiếu kho</a><?php endif; ?>
  </div>
</div>
<?php include APP_PATH . '/views/inventory/_tabs.php'; ?>

<?php if (!$bid): ?>
  <div class="alert alert-warning">Chưa có chi nhánh nào.</div>
<?php else: ?>
<div class="row g-3 mb-3">
  <div class="col-sm-4"><div class="card border-primary"><div class="card-body">
    <div class="text-muted">Số loại nguyên liệu</div><div class="fs-4 fw-semibold"><?= (int)$total ?></div></div></div></div>
  <div class="col-sm-4"><div class="card border-warning"><div class="card-body">
    <div class="text-muted">Sắp hết (chạm mức tối thiểu)</div><div class="fs-4 fw-semibold"><?= (int)$lowCount ?></div></div></div></div>
  <div class="col-sm-4"><div class="card border-danger"><div class="card-body">
    <div class="text-muted">Hết hàng</div><div class="fs-4 fw-semibold text-danger"><?= (int)$outCount ?></div></div></div></div>
</div>

<div class="mb-3 small">
  <?php if ($low): ?>
    Đang chỉ hiện nguyên liệu sắp hết. <a href="<?= url('inventory') . $q ?>">Hiện tất cả</a>
  <?php else: ?>
    <a href="<?= url('inventory') . ($q !== '' ? $q . '&low=1' : '?low=1') ?>">Chỉ hiện nguyên liệu sắp hết</a>
  <?php endif; ?>
</div>

<div class="table-responsive">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light">
    <tr><th>Nguyên liệu</th><th class="text-end">Tồn hiện tại</th><th>Đơn vị</th><th style="width:210px">Mức tối thiểu</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $r):
    $id = (int)$r['id']; $qty = (float)$r['quantity']; $min = (float)$r['min_quantity'];
    $out = $qty <= 0; $isLow = !$out && $min > 0 && $qty <= $min;
    $mv = fn($t) => url('inventory/move') . '?branch=' . (int)$bid . '&type=' . $t . '&ingredient=' . $id; ?>
    <tr class="<?= $out ? 'table-danger' : ($isLow ? 'table-warning' : '') ?>">
      <td class="fw-semibold"><?= e($r['name']) ?></td>
      <td class="text-end fs-6 fw-semibold"><?= qty_fmt($r['quantity']) ?></td>
      <td><?= e($r['unit']) ?></td>
      <td>
        <form method="post" action="<?= url('inventory/setmin') . $q ?>" class="d-flex gap-1 m-0"><?= csrf_field() ?>
          <input type="hidden" name="ingredient_id" value="<?= $id ?>">
          <input class="form-control form-control-sm text-end" name="min" inputmode="decimal" value="<?= e(qty_fmt($r['min_quantity'])) ?>">
          <button class="btn btn-sm btn-outline-secondary">Lưu</button>
        </form>
      </td>
      <td><?= $out ? '<span class="badge bg-danger">Hết hàng</span>' : ($isLow ? '<span class="badge bg-warning text-dark">Sắp hết</span>' : '<span class="badge bg-success">Đủ</span>') ?></td>
      <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-outline-success" href="<?= $mv('in') ?>">Nhập</a>
        <a class="btn btn-sm btn-outline-danger" href="<?= $mv('out') ?>">Xuất</a>
        <a class="btn btn-sm btn-outline-dark" href="<?= $mv('adjust') ?>">Kiểm kê</a>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4"><?= $low ? 'Không có nguyên liệu nào sắp hết.' : 'Chưa có nguyên liệu nào.' ?></td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<p class="text-muted small">Đặt mức tối thiểu lớn hơn 0 để được cảnh báo "Sắp hết" khi tồn kho chạm hoặc thấp hơn mức đó. Mức tối thiểu bằng 0 nghĩa là không cảnh báo.</p>
<?php endif; ?>
