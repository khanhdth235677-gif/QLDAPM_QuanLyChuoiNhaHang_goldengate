<div class="container py-5" style="max-width:520px">
  <div class="card shadow-sm"><div class="card-body p-4">
    <h1 class="h4 mb-2">Đăng nhập tài khoản khách</h1>
    <p class="text-muted">Đăng nhập để xem voucher và ưu đãi dành cho khách hàng.</p>
    <?php if ($m = flash('error')): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endif; ?>
    <form method="post" action="<?= url('customer/login') ?>" autocomplete="on">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label" for="phone">Số điện thoại</label>
        <input class="form-control" id="phone" name="phone" type="tel" value="<?= e($phone) ?>" required autofocus></div>
      <div class="mb-3"><label class="form-label" for="password">Mật khẩu</label>
        <input class="form-control" id="password" name="password" type="password" required></div>
      <button class="btn btn-warning w-100">Đăng nhập</button>
    </form>
    <div class="text-center small mt-3">Chưa có tài khoản? <a href="<?= url('customer/register') ?>">Đăng ký ngay</a></div>
  </div></div>
</div>
