<div class="container py-5" style="max-width:560px">
  <div class="card shadow-sm"><div class="card-body p-4">
    <h1 class="h4 mb-2">Tạo tài khoản khách hàng</h1>
    <p class="text-muted">Đăng ký bằng số điện thoại để xem voucher và ưu đãi. Số điện thoại có hồ sơ sẵn cần được nhân viên hỗ trợ liên kết.</p>
    <?php if ($m = flash('error')): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endif; ?>
    <form method="post" action="<?= url('customer/register') ?>" autocomplete="on">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label" for="full_name">Họ tên</label>
        <input class="form-control" id="full_name" name="full_name" value="<?= e($form['full_name']) ?>" maxlength="100" required autofocus></div>
      <div class="mb-3"><label class="form-label" for="phone">Số điện thoại</label>
        <input class="form-control" id="phone" name="phone" type="tel" value="<?= e($form['phone']) ?>" maxlength="20" required></div>
      <div class="mb-3"><label class="form-label" for="password">Mật khẩu</label>
        <input class="form-control" id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
        <div class="form-text">Mật khẩu từ 8 đến 72 ký tự.</div></div>
      <div class="mb-3"><label class="form-label" for="confirm">Nhập lại mật khẩu</label>
        <input class="form-control" id="confirm" name="confirm" type="password" minlength="8" maxlength="72" autocomplete="new-password" required></div>
      <button class="btn btn-warning w-100">Tạo tài khoản</button>
    </form>
    <div class="text-center small mt-3">Đã có tài khoản? <a href="<?= url('customer/login') ?>">Đăng nhập</a></div>
  </div></div>
</div>
