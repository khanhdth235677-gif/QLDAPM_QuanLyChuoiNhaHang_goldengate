<?php include APP_PATH . '/views/reports/_head.php';
$top = array_slice($items, 0, 10);
$totalRev = 0; foreach ($cats as $c) $totalRev += (float)$c['revenue'];
$sq = fn($s) => url('reports/items') . '?' . $qs . '&sort=' . $s;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
  <div class="btn-group btn-group-sm">
    <a class="btn <?= $sort === 'qty' ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= e($sq('qty')) ?>">Xếp theo số phần</a>
    <a class="btn <?= $sort === 'revenue' ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= e($sq('revenue')) ?>">Xếp theo doanh thu</a>
  </div>
  <a class="btn btn-sm btn-outline-success" href="<?= url('reports/export/items') . '?' . e($qs) ?>&sort=<?= e($sort) ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Xuất Excel (CSV)</a>
</div>

<?php if (!$items): ?>
  <div class="alert alert-info">Không có món nào được bán trong kỳ này.</div>
<?php else: ?>
<div class="card card-body mb-3"><div style="position:relative;height:<?= 60 + count($top) * 34 ?>px"><canvas id="chartItems"></canvas></div></div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="table-responsive">
    <table class="table table-sm table-hover align-middle bg-white">
      <thead class="table-light"><tr><th style="width:44px">#</th><th>Món</th><th>Danh mục</th><th class="text-end">Số phần</th><th class="text-end">Doanh thu</th></tr></thead>
      <tbody>
      <?php foreach ($items as $i => $r): ?>
        <tr><td><?= $i + 1 ?></td><td class="fw-semibold"><?= e($r['name']) ?></td><td><?= e($r['category_name']) ?></td>
          <td class="text-end"><?= (int)$r['qty'] ?></td><td class="text-end"><?= money($r['revenue']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <div class="small text-muted">Hiện tối đa 50 món. Doanh thu món tính theo giá niêm yết lúc bán, chưa trừ giảm giá và chưa gồm VAT.</div>
  </div>
  <div class="col-lg-4">
    <div class="card"><div class="card-header fw-semibold">Theo danh mục</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($cats as $c): $pct = $totalRev > 0 ? round($c['revenue'] * 100 / $totalRev) : 0; ?>
          <li class="list-group-item">
            <div class="d-flex justify-content-between"><span><?= e($c['name']) ?></span><strong><?= money($c['revenue']) ?></strong></div>
            <div class="progress" style="height:6px"><div class="progress-bar bg-warning" style="width:<?= $pct ?>%"></div></div>
            <div class="small text-muted"><?= (int)$c['qty'] ?> phần · <?= $pct ?>%</div>
          </li>
        <?php endforeach; ?>
      </ul></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
var byRev = <?= json_js($sort === 'revenue') ?>;
new Chart(document.getElementById('chartItems'), {
  type: 'bar',
  data: { labels: <?= json_js(array_column($top, 'name')) ?>,
          datasets: [{ label: byRev ? 'Doanh thu' : 'Số phần', data: <?= json_js(array_map(fn($r) => $sort === 'revenue' ? (int)$r['revenue'] : (int)$r['qty'], $top)) ?>, backgroundColor: '#b8860b' }] },
  options: { indexAxis: 'y', maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return c.parsed.x.toLocaleString('vi-VN') + (byRev ? ' ₫' : ' phần'); } } } },
    scales: { x: { beginAtZero: true, ticks: { callback: function (v) { return v.toLocaleString('vi-VN'); } } } } }
});
</script>
<?php endif; ?>
