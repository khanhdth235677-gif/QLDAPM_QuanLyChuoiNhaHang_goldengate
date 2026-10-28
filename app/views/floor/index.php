<?php
$lbl = ['empty' => 'Trống', 'occupied' => 'Đang dùng', 'reserved' => 'Đã đặt'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="h3 m-0">Sơ đồ bàn</h1>
  <?php if ($isAdmin && $branches): ?>
    <select class="form-select w-auto" onchange="location = '<?= url('floor') ?>?branch=' + this.value">
      <?php foreach ($branches as $b): ?>
        <option value="<?= (int)$b['id'] ?>" <?= (int)$b['id'] === $bid ? 'selected' : '' ?>><?= e($b['name']) ?></option>
      <?php endforeach; ?>
    </select>
  <?php endif; ?>
</div>

<div class="d-flex flex-wrap gap-3 mb-3 small">
  <span><span class="legend-dot" style="background:#198754"></span> Trống: <strong><?= $count['empty'] ?></strong></span>
  <span><span class="legend-dot" style="background:#dc3545"></span> Đang dùng: <strong><?= $count['occupied'] ?></strong></span>
  <span><span class="legend-dot" style="background:#ffc107"></span> Đã đặt: <strong><?= $count['reserved'] ?></strong></span>
  <div class="ms-auto d-flex flex-wrap gap-2">
    <a class="btn btn-sm btn-outline-warning" href="<?= url('reservations') ?><?= $isAdmin && $bid ? '?branch=' . $bid : '' ?>">
      <i class="bi bi-calendar-check"></i> Đặt bàn
    </a>
    <a class="btn btn-sm btn-outline-dark" href="<?= url('orders') ?><?= $isAdmin && $bid ? '?branch=' . $bid : '' ?>">
      <i class="bi bi-receipt"></i> Đơn đang mở
    </a>
  </div>
</div>

<?php if (!$bid): ?>
  <div class="alert alert-warning">Chưa có chi nhánh nào.</div>
<?php elseif (!$tables): ?>
  <div class="alert alert-info">Chi nhánh này chưa có bàn. <?php if (has_role('admin', 'manager')): ?><a href="<?= url('tables') ?>?branch=<?= $bid ?>">Thêm bàn</a><?php endif; ?></div>
<?php else: ?>
<div class="row g-3">
  <?php foreach ($tables as $t):
    $id = (int)$t['id'];
    $byRes = !$t['order_id'] && $t['res_id'];                       // bàn giữ cho lượt đặt sắp tới
    $st = $t['order_id'] ? 'occupied' : ($t['res_id'] ? 'reserved' : $t['status']);
    if ($byRes) {
        $action  = url('reservations/arrive/' . (int)$t['res_id']);
        $confirm = 'Bàn đã đặt cho ' . $t['res_name'] . ' lúc ' . date('H:i', strtotime($t['res_at'])) . '. Khách đã đến, mở bàn?';
    } else {
        $action  = url('orders/open/' . $id);
        $confirm = $st === 'reserved' ? 'Bàn này đã được đặt trước. Mở bàn cho khách?' : '';
    }
  ?>
  <div class="col-6 col-md-4 col-xl-3 col-xxl-2">
    <?php if ($t['order_id']): ?>
      <a class="table-card tc-occupied" href="<?= url('orders/show/' . (int)$t['order_id']) ?>">
    <?php else: ?>
      <form method="post" action="<?= $action ?>" class="m-0"
            <?= $confirm !== '' ? 'onsubmit="return confirm(' . e(json_encode($confirm, JSON_UNESCAPED_UNICODE)) . ')"' : '' ?>>
        <?= csrf_field() ?>
        <button type="submit" class="table-card tc-<?= $st ?>">
    <?php endif; ?>
          <div class="d-flex justify-content-between align-items-start">
            <span class="fs-5 fw-bold"><?= e($t['table_name']) ?></span>
            <span class="small text-muted"><i class="bi bi-people"></i> <?= (int)$t['seats'] ?></span>
          </div>
          <div class="small fw-semibold mt-1"><?= $lbl[$st] ?></div>
          <?php if ($t['order_id']): ?>
            <div class="small mt-2"><?= (int)$t['minutes'] ?> phút · <?= (int)$t['item_count'] ?> món</div>
            <div class="fw-semibold"><?= money($t['order_total']) ?></div>
          <?php elseif ($byRes): ?>
            <div class="small mt-2"><i class="bi bi-calendar-check"></i> <?= e(date('H:i', strtotime($t['res_at']))) ?> · <?= (int)$t['res_guests'] ?> khách</div>
            <div class="small fw-semibold text-truncate"><?= e($t['res_name']) ?></div>
            <div class="small text-muted">Bấm khi khách đến</div>
          <?php else: ?>
            <div class="small text-muted mt-2">Bấm để mở bàn</div>
          <?php endif; ?>
    <?php if ($t['order_id']): ?>
      </a>
    <?php else: ?>
        </button>
      </form>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
