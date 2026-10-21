<?php
$code = 'DB' . str_pad((string)$r['id'], 6, '0', STR_PAD_LEFT);
[$stText, $stCls] = Site::STATUS[$r['status']] ?? [$r['status'], 'secondary'];
?>
<div class="container pt-4" style="max-width:640px">
  <div class="card border-success">
    <div class="card-body text-center">
      <div class="display-5 text-success"><i class="bi bi-check-circle"></i></div>
      <h1 class="h4">Đã gửi yêu cầu đặt bàn</h1>
      <p class="text-muted mb-1">Mã đặt bàn của bạn</p>
      <div class="display-6 fw-bold mb-2"><?= e($code) ?></div>
      <span class="badge bg-<?= $stCls ?> <?= $stCls === 'warning' ? 'text-dark' : '' ?> mb-3"><?= e($stText) ?></span>
      <ul class="list-unstyled text-start d-inline-block mb-3">
        <li><i class="bi bi-shop"></i> <?= e($r['branch_name']) ?></li>
        <li><i class="bi bi-clock"></i> <?= e(date('H:i d/m/Y', strtotime($r['reserved_at']))) ?></li>
        <li><i class="bi bi-people"></i> <?= (int)$r['guests'] ?> khách</li>
        <li><i class="bi bi-person"></i> <?= e($r['customer_name']) ?> · <?= e($r['customer_phone']) ?></li>
      </ul>
      <p class="small text-muted">Nhà hàng sẽ xác nhận lượt đặt của bạn. Hãy lưu lại mã này. Dùng mã cùng số điện thoại ở mục <a href="<?= url('site/track') ?>">Tra cứu đặt bàn</a> để xem trạng thái hoặc hủy.
        <?php if (!empty($r['branch_phone'])): ?>Cần hỗ trợ gấp, gọi <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $r['branch_phone'])) ?>"><?= e($r['branch_phone']) ?></a>.<?php endif; ?></p>
      <a class="btn btn-outline-dark" href="<?= url('site') ?>">Về trang chủ</a>
    </div>
  </div>
</div>
