<div class="container py-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div><h1 class="h3 mb-1">Xin chào, <?= e($customer['full_name']) ?></h1><div class="text-muted">Tài khoản: <?= e($customer['phone']) ?></div></div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-dark" href="<?= url('customer/password') ?>"><i class="bi bi-key"></i> Đổi mật khẩu</a>
      <form method="post" action="<?= url('customer/logout') ?>"><?= csrf_field() ?><button class="btn btn-outline-secondary">Đăng xuất</button></form>
    </div>
  </div>

  <div class="card">
    <div class="card-header fw-semibold"><i class="bi bi-ticket-perforated"></i> Voucher đang dùng được</div>
    <?php if ($vouchers): ?>
      <div class="list-group list-group-flush">
        <?php foreach ($vouchers as $v): ?>
          <div class="list-group-item">
            <div class="d-flex flex-wrap justify-content-between gap-2">
              <div><code class="fs-5 fw-bold"><?= e($v['code']) ?></code> <span class="fw-semibold"><?= e($v['description']) ?></span>
                <div class="small text-muted">Áp dụng cho đơn từ <?= money($v['min_subtotal']) ?>.
                  Hạn đến <?= e(date('d/m/Y H:i', strtotime($v['ends_at']))) ?>.
                  <?php if ($v['usage_limit'] !== null): ?>Còn <?= max(0, (int)$v['usage_limit'] - (int)$v['usage_count']) ?> lượt dùng toàn hệ thống.<?php endif; ?>
                </div></div>
              <div class="text-success fw-semibold">
                <?= $v['discount_type'] === 'percent' ? (int)$v['discount_value'] . '% giảm' : 'Giảm ' . money($v['discount_value']) ?>
                <?php if ($v['max_discount'] !== null): ?><div class="small text-muted">Tối đa <?= money($v['max_discount']) ?></div><?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="card-body text-muted">Hiện chưa có voucher khả dụng. Voucher hết hạn, bị khóa hoặc đã hết lượt sẽ không hiển thị tại đây.</div>
    <?php endif; ?>
  </div>
  <div class="alert alert-info mt-3 mb-0">Đưa mã voucher cho nhân viên khi thanh toán. Mỗi đơn áp dụng tối đa một voucher; voucher không cộng dồn với chiết khấu khác hoặc đổi điểm.</div>
</div>
