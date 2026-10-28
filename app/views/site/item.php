<div class="container py-4">
  <?php if (!$item): ?>
    <div class="alert alert-warning">Món ăn không tồn tại hoặc hiện không được phục vụ.</div>
    <a class="btn btn-outline-dark" href="<?= url('site/menu') ?>">Quay lại thực đơn</a>
  <?php else: ?>
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('site') ?>">Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="<?= url('site/menu' . ($branch ? '/' . (int)$branch['id'] : '')) ?>">Thực đơn</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($item['name']) ?></li>
      </ol>
    </nav>

    <div class="card border-0 shadow-sm overflow-hidden">
      <div class="row g-0">
        <div class="col-md-6">
          <?php if ($item['image']): ?>
            <img class="w-100 h-100 object-fit-cover" style="min-height:280px; max-height:480px" src="<?= asset($item['image']) ?>" alt="<?= e($item['name']) ?>">
          <?php else: ?>
            <div class="site-noimg h-100" style="min-height:280px"><i class="bi bi-image"></i></div>
          <?php endif; ?>
        </div>
        <div class="col-md-6">
          <div class="card-body p-4 p-lg-5">
            <span class="badge text-bg-warning mb-3"><?= e($item['category_name']) ?></span>
            <h1 class="h2"><?= e($item['name']) ?></h1>
            <?php if ($branch): ?><div class="small text-muted mb-2"><i class="bi bi-shop"></i> Giá tại <?= e($branch['name']) ?></div><?php endif; ?>
            <div class="fs-4 fw-semibold text-success mb-3"><?= money($item['price']) ?></div>
            <?php if ($item['description']): ?>
              <div class="text-secondary"><?= nl2br(e($item['description'])) ?></div>
            <?php else: ?>
              <p class="text-muted">Thông tin món ăn đang được cập nhật.</p>
            <?php endif; ?>
            <a class="btn btn-warning mt-4" href="<?= url('site/book') ?><?= $branch ? '?branch=' . (int)$branch['id'] : '' ?>"><i class="bi bi-calendar-check"></i> Đặt bàn</a>
          </div>
        </div>
      </div>
    </div>

    <?php if ($related): ?>
      <section class="mt-5" aria-labelledby="related-items-title">
        <h2 class="h4 mb-3" id="related-items-title">Món cùng danh mục: <?= e($item['category_name']) ?></h2>
        <div class="row g-3">
          <?php foreach ($related as $m): ?>
            <div class="col-6 col-md-4 col-lg-3">
              <article class="card h-100 site-card">
                <a href="<?= url('site/item/' . (int)$m['id']) ?><?= $branch ? '?branch=' . (int)$branch['id'] : '' ?>" aria-label="Xem chi tiết <?= e($m['name']) ?>">
                <?php if ($m['image']): ?>
                  <img class="card-img-top" src="<?= asset($m['image']) ?>" alt="<?= e($m['name']) ?>">
                <?php else: ?>
                  <div class="card-img-top site-noimg"><i class="bi bi-image"></i></div>
                <?php endif; ?>
                </a>
                <div class="card-body d-flex flex-column">
                  <h3 class="h6"><?= e($m['name']) ?></h3>
                  <?php if ($m['description']): ?><p class="small text-muted mb-2"><?= e(mb_strimwidth($m['description'], 0, 80, '...')) ?></p><?php endif; ?>
                  <div class="text-success fw-semibold mt-auto mb-2"><?= money($m['price']) ?></div>
                </div>
              </article>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  <?php endif; ?>
</div>
