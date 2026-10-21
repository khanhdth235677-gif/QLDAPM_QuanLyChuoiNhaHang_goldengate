<div class="container pt-4" style="max-width:640px">
  <h1 class="h3 mb-3">Tra cứu đặt bàn</h1>

  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

  <?php if ($r): [$stText, $stCls] = Site::STATUS[$r['status']] ?? [$r['status'], 'secondary']; ?>
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start">
        <div><div class="text-muted small">Mã đặt bàn</div><div class="fs-4 fw-bold"><?= e('DB' . str_pad((string)$r['id'], 6, '0', STR_PAD_LEFT)) ?></div></div>
        <span class="badge bg-<?= $stCls ?> <?= $stCls === 'warning' ? 'text-dark' : '' ?>"><?= e($stText) ?></span>
      </div>
      <ul class="list-unstyled mt-3 mb-3">
        <li><i class="bi bi-shop"></i> <?= e($r['branch_name']) ?></li>
        <li><i class="bi bi-clock"></i> <?= e(date('H:i d/m/Y', strtotime($r['reserved_at']))) ?></li>
        <li><i class="bi bi-people"></i> <?= (int)$r['guests'] ?> khách</li>
        <li><i class="bi bi-person"></i> <?= e($r['customer_name']) ?></li>
        <?php if ($r['note']): ?><li><i class="bi bi-chat-left-text"></i> <?= e($r['note']) ?></li><?php endif; ?>
      </ul>
      <?php if ($canCancel): ?>
        <form method="post" action="<?= url('site/cancel') ?>" onsubmit="return confirm('Hủy lượt đặt bàn này?')"><?= csrf_field() ?>
          <input type="hidden" name="code" value="<?= e($code) ?>"><input type="hidden" name="phone" value="<?= e($phone) ?>">
          <button class="btn btn-outline-danger">Hủy đặt bàn</button>
        </form>
      <?php elseif (in_array($r['status'], ['pending', 'confirmed'], true)): ?>
        <div class="small text-muted">Đã gần giờ hẹn nên không thể tự hủy. Vui lòng gọi <?= $r['branch_phone'] ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $r['branch_phone'])) . '">' . e($r['branch_phone']) . '</a>' : 'nhà hàng' ?> để thay đổi.</div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <form method="post" action="<?= url('site/track') ?>" class="card card-body" autocomplete="off"><?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label" for="code">Mã đặt bàn</label>
        <input class="form-control" id="code" name="code" value="<?= e($code) ?>" placeholder="DB000123" required></div>
      <div class="col-md-6"><label class="form-label" for="phone">Số điện thoại đã dùng để đặt</label>
        <input class="form-control" id="phone" name="phone" type="tel" value="<?= e($phone) ?>" required></div>
    </div>
    <button class="btn btn-warning mt-3">Tra cứu</button>
  </form>
</div>
