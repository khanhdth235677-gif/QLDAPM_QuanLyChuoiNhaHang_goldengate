<h1 class="h3 mb-4">Đổi mật khẩu</h1>
<?php if ($forced): ?>
  <div class="alert alert-warning" style="max-width:480px">Đây là lần đăng nhập đầu hoặc mật khẩu vừa được đặt lại. Bạn cần đặt mật khẩu mới để tiếp tục.</div>
<?php endif; ?>
<form method="post" action="<?= url('account/password') ?>" class="card card-body" style="max-width:480px" autocomplete="off">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label" for="current">Mật khẩu hiện tại</label>
    <input class="form-control" id="current" name="current" type="password" required autofocus>
  </div>
  <div class="mb-3">
    <label class="form-label" for="new">Mật khẩu mới</label>
    <input class="form-control" id="new" name="new" type="password" minlength="8" required>
    <div class="form-text">Tối thiểu 8 ký tự.</div>
  </div>
  <div class="mb-3">
    <label class="form-label" for="confirm">Nhập lại mật khẩu mới</label>
    <input class="form-control" id="confirm" name="confirm" type="password" minlength="8" required>
  </div>
  <button class="btn btn-warning">Lưu mật khẩu mới</button>
</form>
