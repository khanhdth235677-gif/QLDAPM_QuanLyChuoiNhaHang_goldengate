<div class="container py-5" style="max-width:520px">
  <div class="card shadow-sm"><div class="card-body p-4">
    <h1 class="h4 mb-2">Đổi mật khẩu</h1>
    <?php if ($forced): ?><div class="alert alert-warning">Vui lòng đổi mật khẩu tạm thời trước khi tiếp tục.</div><?php endif; ?>
    <?php if ($m = flash('error')): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endif; ?>
    <form method="post" action="<?= url('customer/password') ?>" autocomplete="off">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label" for="current">Mật khẩu hiện tại</label><input class="form-control" id="current" name="current" type="password" required autofocus></div>
      <div class="mb-3"><label class="form-label" for="new">Mật khẩu mới</label><input class="form-control" id="new" name="new" type="password" minlength="8" maxlength="72" required></div>
      <div class="mb-3"><label class="form-label" for="confirm">Nhập lại mật khẩu mới</label><input class="form-control" id="confirm" name="confirm" type="password" minlength="8" maxlength="72" required></div>
      <button class="btn btn-warning w-100">Lưu mật khẩu</button>
    </form>
  </div></div>
</div>
