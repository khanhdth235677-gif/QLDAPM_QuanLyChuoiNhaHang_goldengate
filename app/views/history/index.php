<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="h3 m-0">Lịch sử đơn</h1>
</div>
<form method="get" action="<?= url('history') ?>" class="row g-2 mb-3" style="max-width:640px">
  <div class="col-sm-4"><input class="form-control" type="date" name="date" value="<?= e($date) ?>" max="<?= date('Y-m-d') ?>"></div>
  <?php if ($isAdmin): ?>
  <div class="col-sm-5">
    <select class="form-select" name="branch">
      <option value="0">Tất cả chi nhánh</option>
      <?php foreach ($branches as $b): ?>
        <option value="<?= (int)$b['id'] ?>" <?= (int)$b['id'] === $branchId ? 'selected' : '' ?>><?= e($b['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>
  <div class="col-sm-3"><button class="btn btn-outline-dark w-100">Xem</button></div>
</form>

<div class="row g-3 mb-3">
  <div class="col-sm-4"><div class="card border-success"><div class="card-body">
    <div class="text-muted">Doanh thu</div><div class="fs-4 fw-semibold text-success"><?= money($revenue) ?></div></div></div></div>
  <div class="col-sm-4"><div class="card border-primary"><div class="card-body">
    <div class="text-muted">Đơn đã thanh toán</div><div class="fs-4 fw-semibold"><?= $paid ?></div></div></div></div>
  <div class="col-sm-4"><div class="card border-secondary"><div class="card-body">
    <div class="text-muted">Đơn đã hủy</div><div class="fs-4 fw-semibold"><?= $cancelled ?></div></div></div></div>
</div>

<div class="table-responsive" style="max-width:900px">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light"><tr><th>Giờ đóng</th><th>Số HĐ</th><th>Bàn</th><?php if ($isAdmin): ?><th>Chi nhánh</th><?php endif; ?><th>Trạng thái</th><th>Thanh toán</th><th class="text-end">Tổng</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr class="<?= $r['status'] === 'paid' ? '' : 'text-muted' ?>">
      <td><?= e(date('H:i', strtotime($r['closed_at']))) ?></td>
      <td>HD<?= str_pad((string)$r['id'], 6, '0', STR_PAD_LEFT) ?></td>
      <td><?= e($r['table_name'] ?? '-') ?></td>
      <?php if ($isAdmin): ?><td><?= e($r['branch_name']) ?></td><?php endif; ?>
      <td><?= $r['status'] === 'paid' ? '<span class="badge bg-success">Đã thanh toán</span>' : '<span class="badge bg-secondary">Đã hủy</span>' ?></td>
      <td><?= e(Order::METHODS[$r['method']] ?? '') ?></td>
      <td class="text-end"><?= $r['status'] === 'paid' ? money($r['total']) : '-' ?></td>
      <td class="text-end"><a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= url('orders/bill/' . (int)$r['id']) ?>">Xem / In</a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">Không có đơn nào được đóng trong ngày này.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
