<div class="container pt-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 m-0">Thực đơn</h1>
    <?php if (count($branches) > 1): ?>
      <select class="form-select w-auto" onchange="location = '<?= url('site/menu') ?>/' + this.value">
        <?php foreach ($branches as $b): ?><option value="<?= (int)$b['id'] ?>" <?= $branch && (int)$b['id'] === (int)$branch['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?>
      </select>
    <?php endif; ?>
  </div>
  <?php if ($branch): ?><div class="text-muted small mb-3"><i class="bi bi-shop"></i> <?= e($branch['name']) ?> · giá đã áp dụng tại chi nhánh này (chưa gồm VAT)</div><?php endif; ?>

  <?php foreach ($groups as $cat => $items): ?>
    <h2 class="h5 mt-4 mb-3 border-bottom pb-1"><?= e($cat) ?></h2>
    <div class="row g-3">
      <?php foreach ($items as $m): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <div class="card h-100 site-card">
          <?php if ($m['image']): ?><img class="card-img-top" src="<?= asset($m['image']) ?>" alt="<?= e($m['name']) ?>"><?php else: ?><div class="card-img-top site-noimg"><i class="bi bi-image"></i></div><?php endif; ?>
          <div class="card-body">
            <div class="fw-semibold"><?= e($m['name']) ?></div>
            <?php if ($m['description']): ?><div class="small text-muted"><?= e(mb_strimwidth($m['description'], 0, 90, '...')) ?></div><?php endif; ?>
            <div class="text-success fw-semibold mt-1"><?= money($m['price']) ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
  <?php if (!$groups): ?><div class="alert alert-info">Thực đơn đang được cập nhật.</div><?php endif; ?>

  <div class="text-center mt-5"><a class="btn btn-warning btn-lg" href="<?= url('site/book') ?><?= $branch ? '?branch=' . (int)$branch['id'] : '' ?>"><i class="bi bi-calendar-check"></i> Đặt bàn</a></div>
</div>
