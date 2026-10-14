<?php
$stLabel = ReservationsController::STATUS;
$prev = date('Y-m-d', strtotime($date . ' -1 day'));
$next = date('Y-m-d', strtotime($date . ' +1 day'));
$qs = fn($d) => '?date=' . $d . '&st=' . $filter . ($isAdmin && $branchId ? '&branch=' . $branchId : '');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="h3 m-0">Đặt bàn</h1>
  <a class="btn btn-warning" href="<?= url('reservations/create') ?><?= $isAdmin && $branchId ? '?branch=' . $branchId : '' ?>"><i class="bi bi-plus-lg"></i> Thêm đặt bàn</a>
</div>

<div class="row g-3 mb-3">
  <div class="col-sm-6 col-xl-3"><div class="card border-warning"><div class="card-body">
    <div class="text-muted">Lượt đặt chưa đến</div><div class="fs-4 fw-semibold"><?= $waiting ?></div></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="card border-primary"><div class="card-body">
    <div class="text-muted">Tổng số khách chờ đến</div><div class="fs-4 fw-semibold"><?= $guests ?></div></div></div></div>
</div>

<form method="get" action="<?= url('reservations') ?>" class="row g-2 mb-3" style="max-width:820px">
  <div class="col-sm-3"><input class="form-control" type="date" name="date" value="<?= e($date) ?>"></div>
  <?php if ($isAdmin): ?>
  <div class="col-sm-3">
    <select class="form-select" name="branch">
      <option value="0">Tất cả chi nhánh</option>
      <?php foreach ($branches as $b): ?>
        <option value="<?= (int)$b['id'] ?>" <?= (int)$b['id'] === $branchId ? 'selected' : '' ?>><?= e($b['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>
  <div class="col-sm-3">
    <select class="form-select" name="st">
      <option value="active"    <?= $filter === 'active'    ? 'selected' : '' ?>>Chưa đến</option>
      <option value="done"      <?= $filter === 'done'      ? 'selected' : '' ?>>Khách đã đến</option>
      <option value="cancelled" <?= $filter === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
      <option value="all"       <?= $filter === 'all'       ? 'selected' : '' ?>>Tất cả</option>
    </select>
  </div>
  <div class="col-sm-3"><button class="btn btn-outline-dark w-100">Xem</button></div>
</form>
<div class="mb-3 small">
  <a href="<?= url('reservations') . $qs($prev) ?>">&laquo; Hôm trước</a> ·
  <a href="<?= url('reservations') . $qs(date('Y-m-d')) ?>">Hôm nay</a> ·
  <a href="<?= url('reservations') . $qs($next) ?>">Hôm sau &raquo;</a>
</div>

<div class="table-responsive">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light">
    <tr><th>Giờ</th><th>Khách</th><th>Điện thoại</th><th class="text-center">Số khách</th><?php if ($isAdmin): ?><th>Chi nhánh</th><?php endif; ?><th>Bàn</th><th>Ghi chú</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $r):
    $id = (int)$r['id']; $active = in_array($r['status'], ['pending', 'confirmed'], true);
    $late = $active && strtotime($r['reserved_at']) < $nowTs - 1800;
    [$txt, $cls] = $stLabel[$r['status']]; ?>
    <tr class="<?= $active ? '' : 'text-muted' ?>">
      <td class="fw-semibold"><?= e(date('H:i', strtotime($r['reserved_at']))) ?></td>
      <td><?= e($r['customer_name']) ?></td>
      <td><?= e($r['customer_phone']) ?></td>
      <td class="text-center"><?= (int)$r['guests'] ?></td>
      <?php if ($isAdmin): ?><td><?= e($r['branch_name']) ?></td><?php endif; ?>
      <td><?= $r['table_name'] ? e($r['table_name']) : '<span class="text-muted">Chưa xếp</span>' ?></td>
      <td class="small"><?= e($r['note'] ?? '') ?></td>
      <td><span class="badge bg-<?= $cls ?>"><?= $txt ?></span><?= $late ? ' <span class="badge bg-danger">Quá giờ</span>' : '' ?></td>
      <td class="text-end">
        <?php if ($active): ?>
        <div class="d-flex flex-wrap gap-1 justify-content-end">
          <form method="post" action="<?= url('reservations/arrive/' . $id) ?>" class="d-inline-flex gap-1 m-0"><?= csrf_field() ?>
            <?php if (!$r['table_id']): ?>
              <select class="form-select form-select-sm" name="table_id" required style="width:auto">
                <option value="">Chọn bàn...</option>
                <?php foreach ($free[(int)$r['branch_id']] ?? [] as $t): ?>
                  <option value="<?= (int)$t['id'] ?>"><?= e($t['table_name']) ?> (<?= (int)$t['seats'] ?> chỗ)</option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
            <button class="btn btn-sm btn-success text-nowrap">Khách đến</button>
          </form>
          <?php if ($r['status'] === 'pending'): ?>
            <form method="post" action="<?= url('reservations/confirm/' . $id) ?>" class="m-0"><?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-primary">Xác nhận</button></form>
          <?php endif; ?>
          <a class="btn btn-sm btn-outline-secondary" href="<?= url('reservations/edit/' . $id) ?>">Sửa</a>
          <form method="post" action="<?= url('reservations/cancel/' . $id) ?>" class="m-0"
                onsubmit="return confirm(<?= e(json_encode('Hủy đặt bàn của ' . $r['customer_name'] . '?', JSON_UNESCAPED_UNICODE)) ?>)"><?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-danger">Hủy</button></form>
        </div>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">Không có lượt đặt nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<p class="text-muted small">Mỗi lượt đặt giữ bàn <?= (int)Reservation::DURATION_MIN ?> phút. Trên sơ đồ bàn, bàn chuyển sang màu vàng từ 60 phút trước giờ hẹn và giữ đến 30 phút sau giờ hẹn.</p>
