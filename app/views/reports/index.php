<?php include APP_PATH . '/views/reports/_head.php';
$labels = array_column($periods, 'label'); $revenue = array_column($periods, 'total');
$tot = ['orders' => 0, 'subtotal' => 0, 'discount' => 0, 'vat' => 0, 'total' => 0];
foreach ($periods as $p) foreach ($tot as $k => $_) $tot[$k] += $p[$k];
?>
<div class="row g-3 mb-3">
  <div class="col-sm-6 col-xl-3"><div class="card border-success"><div class="card-body"><div class="text-muted">Doanh thu</div><div class="fs-4 fw-semibold text-success"><?= money($sum['revenue']) ?></div></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="card border-primary"><div class="card-body"><div class="text-muted">Số đơn đã thanh toán</div><div class="fs-4 fw-semibold"><?= (int)$sum['orders'] ?></div></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="card border-warning"><div class="card-body"><div class="text-muted">Trung bình mỗi đơn</div><div class="fs-4 fw-semibold"><?= money($sum['avg']) ?></div></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="card border-secondary"><div class="card-body"><div class="text-muted">Đơn đã hủy</div><div class="fs-4 fw-semibold"><?= (int)$sum['cancelled'] ?></div></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted">Tạm tính (chưa giảm, chưa VAT)</div><div class="fs-5 fw-semibold"><?= money($sum['subtotal']) ?></div></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted">Tổng giảm giá</div><div class="fs-5 fw-semibold text-danger"><?= money($sum['discount']) ?></div></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted">Tổng VAT thu hộ</div><div class="fs-5 fw-semibold"><?= money($sum['vat']) ?></div></div></div></div>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
  <div class="btn-group btn-group-sm">
    <a class="btn <?= $by === 'day' ? 'btn-dark' : 'btn-outline-dark' ?> <?= $forcedMonth ? 'disabled' : '' ?>" href="<?= url('reports') . '?' . e($qs) ?>&by=day">Theo ngày</a>
    <a class="btn <?= $by === 'month' ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= url('reports') . '?' . e($qs) ?>&by=month">Theo tháng</a>
  </div>
  <a class="btn btn-sm btn-outline-success" href="<?= url('reports/export/revenue') . '?' . e($qs) ?>&by=<?= $by ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Xuất Excel (CSV)</a>
</div>
<?php if ($forcedMonth): ?><div class="small text-muted mb-2">Kỳ dài hơn 92 ngày nên báo cáo tự gom theo tháng.</div><?php endif; ?>

<?php if ((int)$sum['orders'] === 0): ?>
  <div class="alert alert-info">Không có đơn nào được thanh toán trong kỳ này.</div>
<?php else: ?>
<div class="card card-body mb-3"><div style="position:relative;height:300px"><canvas id="chartRev"></canvas></div></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="table-responsive">
    <table class="table table-sm table-hover align-middle bg-white">
      <thead class="table-light"><tr><th><?= $by === 'month' ? 'Tháng' : 'Ngày' ?></th><th class="text-end">Số đơn</th><th class="text-end">Tạm tính</th><th class="text-end">Giảm giá</th><th class="text-end">VAT</th><th class="text-end">Doanh thu</th></tr></thead>
      <tbody>
      <?php foreach ($periods as $p): ?>
        <tr class="<?= $p['orders'] ? '' : 'text-muted' ?>"><td><?= e($p['label']) ?></td><td class="text-end"><?= $p['orders'] ?></td>
          <td class="text-end"><?= money($p['subtotal']) ?></td><td class="text-end"><?= money($p['discount']) ?></td>
          <td class="text-end"><?= money($p['vat']) ?></td><td class="text-end fw-semibold"><?= money($p['total']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot class="table-light fw-semibold"><tr><td>Tổng</td><td class="text-end"><?= $tot['orders'] ?></td><td class="text-end"><?= money($tot['subtotal']) ?></td>
        <td class="text-end"><?= money($tot['discount']) ?></td><td class="text-end"><?= money($tot['vat']) ?></td><td class="text-end"><?= money($tot['total']) ?></td></tr></tfoot>
    </table>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card"><div class="card-header fw-semibold">Theo phương thức thanh toán</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($methods as $m): ?>
          <li class="list-group-item d-flex justify-content-between"><span><?= e($methodNames[$m['method']] ?? $m['method']) ?> <span class="text-muted small">(<?= (int)$m['cnt'] ?> đơn)</span></span>
            <strong><?= money($m['amount']) ?></strong></li>
        <?php endforeach; ?>
        <?php if (!$methods): ?><li class="list-group-item text-muted">Chưa có giao dịch.</li><?php endif; ?>
      </ul></div>
  </div>
</div>

<?php if ((int)$sum['orders'] > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartRev'), {
  type: 'bar',
  data: { labels: <?= json_js($labels) ?>, datasets: [{ label: 'Doanh thu', data: <?= json_js($revenue) ?>, backgroundColor: '#b8860b' }] },
  options: { maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return c.parsed.y.toLocaleString('vi-VN') + ' ₫'; } } } },
    scales: { y: { beginAtZero: true, ticks: { callback: function (v) { return v.toLocaleString('vi-VN'); } } } } }
});
</script>
<?php endif; ?>
