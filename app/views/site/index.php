<section class="hero text-white">
  <div class="container py-5">
    <h1 class="display-5 fw-bold"><?= e(APP_NAME) ?></h1>
    <p class="lead mb-4">Xem thực đơn, chọn chi nhánh gần bạn và đặt bàn trực tuyến chỉ trong một phút.</p>
    <a class="btn btn-warning btn-lg me-2" href="<?= url('site/book') ?>"><i class="bi bi-calendar-check"></i> Đặt bàn</a>
    <a class="btn btn-outline-light btn-lg" href="<?= url('site/menu') ?>">Xem thực đơn</a>
  </div>
</section>

<div class="container">
  <?php if ($featured): ?>
  <h2 class="h4 mt-5 mb-3">Món nổi bật</h2>
  <div class="row g-3">
    <?php foreach ($featured as $m): ?>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card h-100 site-card">
        <?php if ($m['image']): ?><img class="card-img-top" src="<?= asset($m['image']) ?>" alt="<?= e($m['name']) ?>"><?php else: ?><div class="card-img-top site-noimg"><i class="bi bi-image"></i></div><?php endif; ?>
        <div class="card-body p-2"><div class="fw-semibold small"><?= e($m['name']) ?></div><div class="text-success small"><?= money($m['base_price']) ?></div></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="mt-3"><a href="<?= url('site/menu') ?>">Xem toàn bộ thực đơn &raquo;</a></div>
  <?php endif; ?>

  <h2 class="h4 mt-5 mb-3">Chi nhánh</h2>
  <div class="row g-3">
    <?php foreach ($branches as $b): ?>
    <div class="col-md-6">
      <div class="card h-100"><div class="card-body">
        <h3 class="h5"><?= e($b['name']) ?></h3>
        <div class="mb-1"><i class="bi bi-geo-alt"></i> <?= e($b['address']) ?></div>
        <?php if ($b['phone']): ?><div class="mb-3"><i class="bi bi-telephone"></i> <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $b['phone'])) ?>"><?= e($b['phone']) ?></a></div><?php endif; ?>
        <a class="btn btn-sm btn-outline-dark" href="<?= url('site/menu/' . (int)$b['id']) ?>">Thực đơn</a>
        <a class="btn btn-sm btn-warning" href="<?= url('site/book') ?>?branch=<?= (int)$b['id'] ?>">Đặt bàn tại đây</a>
      </div></div>
    </div>
    <?php endforeach; ?>
    <?php if (!$branches): ?><div class="col-12 text-muted">Hiện chưa có chi nhánh nào đang hoạt động.</div><?php endif; ?>
  </div>
  <p class="text-muted small mt-3">Nhận đặt bàn từ <?= sprintf('%02d:00', BOOK_FIRST_HOUR) ?> đến <?= sprintf('%02d:00', BOOK_LAST_HOUR) ?> mỗi ngày, đặt trước ít nhất <?= (int)BOOK_MIN_LEAD_MIN ?> phút.</p>
</div>
