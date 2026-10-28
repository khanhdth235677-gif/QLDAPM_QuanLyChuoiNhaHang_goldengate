<?php
$badge = ['empty' => 'success', 'occupied' => 'danger', 'reserved' => 'warning'];
$label = TablesController::STATUS;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="h3 m-0">Bàn</h1>
  <div class="d-flex align-items-center gap-2">
    <?php if ($canPick && $branches): ?>
      <select class="form-select w-auto" onchange="location = '<?= url('tables') ?>?branch=' + this.value">
        <?php foreach ($branches as $b): ?>
          <option value="<?= (int)$b['id'] ?>" <?= (int)$b['id'] === $bid ? 'selected' : '' ?>><?= e($b['name']) ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
    <?php if ($canEdit && $bid): ?>
      <a class="btn btn-warning" href="<?= url('tables/create') ?>?branch=<?= $bid ?>"><i class="bi bi-plus-lg"></i> Thêm bàn</a>
    <?php endif; ?>
  </div>
</div>

<?php if (!$bid): ?>
  <div class="alert alert-warning">Chưa có chi nhánh nào. <a href="<?= url('branches/create') ?>">Thêm chi nhánh</a> trước.</div>
<?php else: ?>

<?php if ($canEdit): ?>
<div class="card card-body mb-4" style="max-width:720px">
  <form method="post" action="<?= url('tables/bulk') ?>?branch=<?= $bid ?>" class="row g-2 align-items-end">
    <?= csrf_field() ?>
    <div class="col-12"><strong class="small">Thêm nhanh nhiều bàn</strong> <span class="text-muted small">(ví dụ tiền tố B, số lượng 10 sẽ tạo B01 đến B10, bỏ qua tên đã có)</span></div>
    <div class="col-sm-3"><label class="form-label small mb-1">Tiền tố</label><input class="form-control" name="prefix" value="B" maxlength="10" required></div>
    <div class="col-sm-3"><label class="form-label small mb-1">Số lượng</label><input class="form-control" name="count" type="number" min="1" max="50" value="10" required></div>
    <div class="col-sm-3"><label class="form-label small mb-1">Số chỗ</label><input class="form-control" name="seats" type="number" min="1" max="50" value="4" required></div>
    <div class="col-sm-3"><button class="btn btn-outline-dark w-100">Thêm nhanh</button></div>
  </form>
</div>
<?php endif; ?>

<div class="table-responsive" style="max-width:820px">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light"><tr><th>Tên bàn</th><th class="text-center">Số chỗ</th><th>Trạng thái</th><?php if ($canEdit): ?><th class="text-end">Thao tác</th><?php endif; ?></tr></thead>
  <tbody>
  <?php foreach ($tables as $t): $id = (int)$t['id']; ?>
    <tr>
      <td class="fw-semibold"><?= e($t['table_name']) ?></td>
      <td class="text-center"><?= (int)$t['seats'] ?></td>
      <td><span class="badge bg-<?= $badge[$t['status']] ?> <?= $t['status'] === 'reserved' ? 'text-dark' : '' ?>"><?= e($label[$t['status']]) ?></span></td>
      <?php if ($canEdit): ?>
      <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-outline-success" href="<?= url('tables/qr/' . $id) ?>"><i class="bi bi-qr-code"></i> Mã QR</a>
        <a class="btn btn-sm btn-outline-primary" href="<?= url('tables/edit/' . $id) ?>">Sửa</a>
        <form method="post" action="<?= url('tables/delete/' . $id) ?>" class="d-inline" onsubmit="return confirm('Xóa bàn <?= e($t['table_name']) ?>?')">
          <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Xóa</button>
        </form>
      </td>
      <?php endif; ?>
    </tr>
  <?php endforeach; ?>
  <?php if (!$tables): ?><tr><td colspan="4" class="text-center text-muted py-4">Chi nhánh này chưa có bàn nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
