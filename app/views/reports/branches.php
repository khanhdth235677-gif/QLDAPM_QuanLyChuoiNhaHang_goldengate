<?php include APP_PATH . '/views/reports/_head.php';
$sumRev = 0; $sumOrd = 0; foreach ($rows as $r) { $sumRev += (float)$r['revenue']; $sumOrd += (int)$r['orders']; }
?>
<div class="d-flex justify-content-end mb-2">
  <a class="btn btn-sm btn-outline-success" href="<?= url('reports/export/branches') . '?' . e($qs) ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Xuất Excel (CSV)</a>
</div>

<?php if ($sumOrd === 0): ?>
  <div class="alert alert-info">Không có đơn nào được thanh toán trong kỳ này.</div>
<?php else: ?>
<div class="card card-body mb-3"><div style="position:relative;height:300px"><canvas id="chartBr"></canvas></div></div>
<?php endif; ?>

<div class="table-responsive" style="max-width:900px">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light"><tr><th>Chi nhánh</th><th class="text-end">Số đơn</th><th class="text-end">Doanh thu</th><th class="text-end">Tỷ trọng</th><th class="text-end">TB mỗi đơn</th><th class="text-end">Giảm giá</th><th class="text-end">VAT</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): $pct = $sumRev > 0 ? round($r['revenue'] * 100 / $sumRev, 1) : 0; $avg = (int)$r['orders'] > 0 ? round($r['revenue'] / $r['orders']) : 0; ?>
    <tr><td class="fw-semibold"><?= e($r['name']) ?></td><td class="text-end"><?= (int)$r['orders'] ?></td><td class="text-end fw-semibold"><?= money($r['revenue']) ?></td>
      <td class="text-end"><?= str_replace('.', ',', (string)$pct) ?>%</td><td class="text-end"><?= money($avg) ?></td>
      <td class="text-end"><?= money($r['discount']) ?></td><td class="text-end"><?= money($r['vat']) ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">Chưa có chi nhánh nào.</td></tr><?php endif; ?>
  </tbody>
  <tfoot class="table-light fw-semibold"><tr><td>Tổng</td><td class="text-end"><?= $sumOrd ?></td><td class="text-end"><?= money($sumRev) ?></td><td></td><td></td><td></td><td></td></tr></tfoot>
</table>
</div>

<?php if ($sumOrd > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartBr'), {
  type: 'bar',
  data: { labels: <?= json_js(array_column($rows, 'name')) ?>,
          datasets: [{ label: 'Doanh thu', data: <?= json_js(array_map(fn($r) => (int)$r['revenue'], $rows)) ?>, backgroundColor: '#b8860b' }] },
  options: { maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return c.parsed.y.toLocaleString('vi-VN') + ' ₫'; } } } },
    scales: { y: { beginAtZero: true, ticks: { callback: function (v) { return v.toLocaleString('vi-VN'); } } } } }
});
</script>
<?php endif; ?>
