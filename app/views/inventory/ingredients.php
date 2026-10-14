<h1 class="h3 mb-3">Kho nguyên liệu</h1>
<?php include APP_PATH . '/views/inventory/_tabs.php'; ?>

<div class="card card-body mb-4" style="max-width:640px">
  <form method="post" action="<?= url('inventory/saveingredient') ?>" class="row g-2 align-items-end"><?= csrf_field() ?>
    <div class="col-sm-6"><label class="form-label small mb-1">Tên nguyên liệu mới</label>
      <input class="form-control" name="name" maxlength="100" required></div>
    <div class="col-sm-3"><label class="form-label small mb-1">Đơn vị</label>
      <input class="form-control" name="unit" list="units" maxlength="20" required>
      <datalist id="units"><?php foreach ($units as $u): ?><option value="<?= e($u) ?>"><?php endforeach; ?></datalist></div>
    <div class="col-sm-3"><button class="btn btn-warning w-100">Thêm</button></div>
  </form>
</div>

<div class="table-responsive" style="max-width:820px">
<table class="table align-middle bg-white">
  <thead class="table-light"><tr><th>Tên nguyên liệu</th><th style="width:130px">Đơn vị</th><th class="text-end">Tổng tồn (mọi chi nhánh)</th><th class="text-center">Số phiếu</th><?php if ($isAdmin): ?><th class="text-end">Thao tác</th><?php endif; ?></tr></thead>
  <tbody>
  <?php foreach ($items as $i): $id = (int)$i['id']; ?>
    <tr>
      <?php if ($isAdmin): ?>
        <form method="post" action="<?= url('inventory/saveingredient/' . $id) ?>" id="ing<?= $id ?>"><?= csrf_field() ?></form>
        <td><input form="ing<?= $id ?>" class="form-control form-control-sm" name="name" value="<?= e($i['name']) ?>" maxlength="100" required></td>
        <td><input form="ing<?= $id ?>" class="form-control form-control-sm" name="unit" value="<?= e($i['unit']) ?>" list="units" maxlength="20" required></td>
      <?php else: ?>
        <td class="fw-semibold"><?= e($i['name']) ?></td><td><?= e($i['unit']) ?></td>
      <?php endif; ?>
      <td class="text-end"><?= qty_fmt($i['total_stock']) ?></td>
      <td class="text-center"><?= (int)$i['moves'] ?></td>
      <?php if ($isAdmin): ?>
      <td class="text-end text-nowrap">
        <button form="ing<?= $id ?>" class="btn btn-sm btn-outline-primary">Lưu</button>
        <form method="post" action="<?= url('inventory/deleteingredient/' . $id) ?>" class="d-inline"
              onsubmit="return confirm(<?= e(json_encode('Xóa nguyên liệu ' . $i['name'] . '?', JSON_UNESCAPED_UNICODE)) ?>)"><?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-danger">Xóa</button></form>
      </td>
      <?php endif; ?>
    </tr>
  <?php endforeach; ?>
  <?php if (!$items): ?><tr><td colspan="5" class="text-center text-muted py-4">Chưa có nguyên liệu nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<p class="text-muted small">Danh mục nguyên liệu dùng chung cho cả chuỗi. Quản lý chi nhánh được thêm nguyên liệu mới, chỉ admin được đổi tên, đổi đơn vị hoặc xóa. Chỉ xóa được nguyên liệu chưa từng nhập xuất và không còn tồn kho.</p>
