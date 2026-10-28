<?php
$tabs = ['revenue' => ['reports', 'Doanh thu'], 'items' => ['reports/items', 'Món bán chạy'], 'hours' => ['reports/hours', 'Giờ và ngày'],
         'branches' => ['reports/branches', 'So sánh chi nhánh'], 'usage' => ['reports/usage', 'Nguyên liệu']];
if (!$isAdmin) unset($tabs['branches']);
$today = date('Y-m-d');
$presets = [
    'Hôm nay'     => [$today, $today],
    '7 ngày qua'  => [date('Y-m-d', strtotime('-6 days')), $today],
    'Tháng này'   => [date('Y-m-01'), $today],
    'Tháng trước' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
    'Năm nay'     => [date('Y-01-01'), $today],
];
$branchQ = $isAdmin && $bid ? ['branch' => $bid] : [];
$activePreset = null;
foreach ($presets as $label => [$pf, $pt]) {
    if ($f === $pf && $t === $pt) { $activePreset = $label; break; }
}
?>
<h1 class="h3 mb-3">Báo cáo</h1>
<ul class="nav nav-pills mb-3">
  <?php foreach ($tabs as $k => [$path, $label]): ?>
    <li class="nav-item"><a class="nav-link <?= $tab === $k ? 'active' : '' ?>" href="<?= url($path) . '?' . e($qs) ?>"><?= e($label) ?></a></li>
  <?php endforeach; ?>
</ul>

<form method="get" action="<?= url($action) ?>" class="row g-2 align-items-end mb-2" style="max-width:860px">
  <?php if (!empty($sort)): ?><input type="hidden" name="sort" value="<?= e($sort) ?>"><?php endif; ?>
  <div class="col-sm-3"><label class="form-label small mb-1">Từ ngày</label><input class="form-control" type="date" name="from" value="<?= e($f) ?>"></div>
  <div class="col-sm-3"><label class="form-label small mb-1">Đến ngày</label><input class="form-control" type="date" name="to" value="<?= e($t) ?>"></div>
  <?php if ($isAdmin && $tab !== 'branches'): ?>
  <div class="col-sm-4"><label class="form-label small mb-1">Chi nhánh</label>
    <select class="form-select" name="branch">
      <option value="0">Tất cả chi nhánh</option>
      <?php foreach ($branches as $b): ?><option value="<?= (int)$b['id'] ?>" <?= (int)$b['id'] === $bid ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?>
    </select></div>
  <?php endif; ?>
  <div class="col-sm-2"><button class="btn btn-warning w-100">Xem</button></div>
</form>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <span class="small text-muted me-1"><i class="bi bi-calendar-range"></i> Khoảng nhanh:</span>
  <?php foreach ($presets as $label => [$pf, $pt]): ?>
    <a class="btn btn-sm <?= $activePreset === $label ? 'btn-warning' : 'btn-outline-dark' ?>"
       href="<?= url($action) . '?' . e(http_build_query(['from' => $pf, 'to' => $pt] + $branchQ)) ?>">
      <?php if ($label === 'Hôm nay'): ?><i class="bi bi-sun"></i>
      <?php elseif ($label === '7 ngày qua'): ?><i class="bi bi-calendar-week"></i>
      <?php elseif (strpos($label, 'Tháng') === 0): ?><i class="bi bi-calendar-month"></i>
      <?php else: ?><i class="bi bi-calendar3"></i><?php endif; ?>
      <?= e($label) ?>
    </a>
  <?php endforeach; ?>
</div>
<div class="text-muted small mb-3">Kỳ báo cáo: <strong><?= e(date('d/m/Y', strtotime($f))) ?> - <?= e(date('d/m/Y', strtotime($t))) ?></strong>
  · <?= e($tab === 'branches' ? 'Tất cả chi nhánh' : $branchName) ?> · chỉ tính đơn đã thanh toán</div>
