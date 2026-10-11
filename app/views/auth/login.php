<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> - <?= e(APP_NAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body class="login-page">
  <div class="card shadow login-card">
    <div class="card-body p-4">
      <h1 class="h4 text-center mb-1">Golden Gate</h1>
      <p class="text-center text-muted mb-4">Hệ thống quản lý chuỗi nhà hàng</p>
      <?php if ($m = flash('error')): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endif; ?>
      <?php if ($m = flash('success')): ?><div class="alert alert-success py-2"><?= e($m) ?></div><?php endif; ?>
      <form method="post" action="<?= url('auth/login') ?>" autocomplete="off">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="username">Tên đăng nhập</label>
          <input class="form-control" id="username" name="username" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label" for="password">Mật khẩu</label>
          <input class="form-control" id="password" name="password" type="password" required>
        </div>
        <button class="btn btn-warning w-100">Đăng nhập</button>
      </form>
    </div>
  </div>
</body>
</html>
