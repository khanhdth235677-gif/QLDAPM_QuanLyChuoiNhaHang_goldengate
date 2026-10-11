<h1 class="h3 mb-4">Tổng quan</h1>
<div class="row g-3">
  <div class="col-sm-6 col-xl-3">
    <div class="card border-success"><div class="card-body">
      <div class="text-muted">Doanh thu hôm nay</div>
      <div class="fs-4 fw-semibold text-success"><?= money($stats['revenue_today']) ?></div>
    </div></div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card border-warning"><div class="card-body">
      <div class="text-muted">Đơn đang mở</div>
      <div class="fs-4 fw-semibold"><?= $stats['open_orders'] ?></div>
    </div></div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card border-primary"><div class="card-body">
      <div class="text-muted">Bàn đang dùng / tổng</div>
      <div class="fs-4 fw-semibold"><?= $stats['tables_busy'] ?> / <?= $stats['tables_total'] ?></div>
    </div></div>
  </div>
  <?php if ($stats['branches'] !== null): ?>
  <div class="col-sm-6 col-xl-3">
    <div class="card border-dark"><div class="card-body">
      <div class="text-muted">Chi nhánh hoạt động</div>
      <div class="fs-4 fw-semibold"><?= $stats['branches'] ?></div>
    </div></div>
  </div>
  <?php endif; ?>
</div>

<?php if (!empty($branchRows)): ?>
<h2 class="h5 mt-4 mb-3">So sánh chi nhánh hôm nay</h2>
<div class="table-responsive">
<table class="table bg-white align-middle">
  <thead class="table-light"><tr><th>Chi nhánh</th><th class="text-end">Doanh thu</th><th class="text-end">Đơn đang mở</th></tr></thead>
  <tbody>
  <?php foreach ($branchRows as $r): ?>
    <tr><td><?= e($r['name']) ?></td><td class="text-end"><?= money($r['revenue']) ?></td><td class="text-end"><?= (int)$r['open_orders'] ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
