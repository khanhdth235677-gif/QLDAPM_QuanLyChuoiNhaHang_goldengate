<?php
$badge = ['new' => ['Mới', 'danger'], 'cooking' => ['Đang nấu', 'warning'], 'ready' => ['Đã xong, chờ mang ra', 'info']];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="h3 m-0"><i class="bi bi-fire text-danger"></i> Màn hình bếp</h1>
  <div class="d-flex align-items-center gap-2">
    <span class="small text-muted">Tự làm mới sau 15 giây</span>
    <?php if ($isAdmin && $branches): ?>
      <select class="form-select w-auto" onchange="location = '<?= url('kitchen') ?>?branch=' + this.value">
        <?php foreach ($branches as $b): ?>
          <option value="<?= (int)$b['id'] ?>" <?= (int)$b['id'] === $bid ? 'selected' : '' ?>><?= e($b['name']) ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
  </div>
</div>

<?php if (!$orders): ?>
  <div class="alert alert-success">Không có món nào đang chờ. Bếp rảnh!</div>
<?php else: ?>
<div class="row g-3">
  <?php foreach ($orders as $o): ?>
  <div class="col-md-6 col-xl-4">
    <div class="card h-100 shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fs-5 fw-bold">Bàn <?= e($o['table'] ?? '-') ?></span>
        <span class="small <?= $o['minutes'] >= 20 ? 'text-danger fw-semibold' : 'text-muted' ?>"><?= (int)$o['minutes'] ?> phút</span>
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($o['lines'] as $l): [$txt, $cls] = $badge[$l['kitchen_status']]; ?>
        <li class="list-group-item">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
              <span class="fs-5 fw-bold"><?= (int)$l['quantity'] ?>×</span> <span class="fw-semibold"><?= e($l['name']) ?></span>
              <?php if ($l['note']): ?><div class="small fw-semibold text-danger"><i class="bi bi-exclamation-circle"></i> <?= e($l['note']) ?></div><?php endif; ?>
              <span class="badge bg-<?= $cls ?> <?= $cls !== 'danger' ? 'text-dark' : '' ?> mt-1"><?= $txt ?></span>
            </div>
            <?php if ($l['kitchen_status'] !== 'ready'): ?>
            <form method="post" action="<?= url('kitchen/advance/' . (int)$l['id']) ?>" class="m-0">
              <?= csrf_field() ?>
              <button class="btn btn-<?= $l['kitchen_status'] === 'new' ? 'warning' : 'success' ?> text-nowrap">
                <?= $l['kitchen_status'] === 'new' ? 'Nhận nấu' : 'Xong' ?></button>
            </form>
            <?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<script>setTimeout(function () { location.reload(); }, 15000);</script>
