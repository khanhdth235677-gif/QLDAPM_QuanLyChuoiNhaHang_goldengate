<?php include APP_PATH . '/views/reports/_head.php';
$totalOrders = array_sum(array_column($hourRows, 'orders'));
$peakH = null; foreach ($hourRows as $r) if ($r['orders'] > 0 && ($peakH === null || $r['orders'] > $peakH['orders'])) $peakH = $r;
$peakD = null; foreach ($dayRows as $r) if ($r['avg'] > 0 && ($peakD === null || $r['avg'] > $peakD['avg'])) $peakD = $r;
?>
<div class="d-flex justify-content-end mb-2">
  <a class="btn btn-sm btn-outline-success" href="<?= url('reports/export/hours') . '?' . e($qs) ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Xuất Excel (CSV) theo giờ</a>
</div>

<?php if ($totalOrders === 0): ?>
  <div class="alert alert-info">Không có đơn nào được thanh toán trong kỳ này.</div>
<?php else: ?>
<div class="row g-3 mb-3">
  <div class="col-sm-6"><div class="card border-danger"><div class="card-body"><div class="text-muted">Giờ đông khách nhất</div>
    <div class="fs-4 fw-semibold"><?= $peakH ? sprintf('%02d:00 - %02d:00', $peakH['h'], ($peakH['h'] + 1) % 24) : '-' ?></div>
    <div class="small text-muted"><?= $peakH ? (int)$peakH['orders'] . ' đơn' : '' ?> (tính theo giờ khách vào bàn)</div></div></div></div>
  <div class="col-sm-6"><div class="card border-primary"><div class="card-body"><div class="text-muted">Ngày đông nhất trong tuần</div>
    <div class="fs-4 fw-semibold"><?= $peakD ? e($peakD['label']) : '-' ?></div>
    <div class="small text-muted"><?= $peakD ? 'Trung bình ' . money($peakD['avg']) . ' mỗi ' . e(mb_strtolower($peakD['label'])) : '' ?></div></div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-7"><div class="card card-body"><div class="fw-semibold mb-2">Số đơn theo giờ khách vào bàn</div>
    <div style="position:relative;height:280px"><canvas id="chartHours"></canvas></div></div></div>
  <div class="col-lg-5"><div class="card card-body"><div class="fw-semibold mb-2">Doanh thu trung bình mỗi ngày, theo thứ</div>
    <div style="position:relative;height:280px"><canvas id="chartDays"></canvas></div></div></div>
</div>

<div class="table-responsive mt-3" style="max-width:720px">
<table class="table table-sm bg-white align-middle">
  <thead class="table-light"><tr><th>Thứ</th><th class="text-end">Số ngày trong kỳ</th><th class="text-end">Số đơn</th><th class="text-end">Doanh thu</th><th class="text-end">Trung bình mỗi ngày</th></tr></thead>
  <tbody>
  <?php foreach ($dayRows as $r): ?>
    <tr><td><?= e($r['label']) ?></td><td class="text-end"><?= (int)$r['days'] ?></td><td class="text-end"><?= (int)$r['orders'] ?></td>
      <td class="text-end"><?= money($r['revenue']) ?></td><td class="text-end fw-semibold"><?= money($r['avg']) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartHours'), {
  type: 'bar',
  data: { labels: <?= json_js(array_map(fn($r) => sprintf('%02dh', $r['h']), $hourRows)) ?>,
          datasets: [{ label: 'Số đơn', data: <?= json_js(array_column($hourRows, 'orders')) ?>, backgroundColor: '#dc3545' }] },
  options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});
new Chart(document.getElementById('chartDays'), {
  type: 'bar',
  data: { labels: <?= json_js(array_column($dayRows, 'label')) ?>,
          datasets: [{ label: 'TB mỗi ngày', data: <?= json_js(array_column($dayRows, 'avg')) ?>, backgroundColor: '#b8860b' }] },
  options: { maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return c.parsed.y.toLocaleString('vi-VN') + ' ₫'; } } } },
    scales: { y: { beginAtZero: true, ticks: { callback: function (v) { return v.toLocaleString('vi-VN'); } } } } }
});
</script>
<?php endif; ?>
