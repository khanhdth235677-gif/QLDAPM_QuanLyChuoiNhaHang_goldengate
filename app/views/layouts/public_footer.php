</main>
<footer class="site-footer mt-5 py-4">
  <div class="container d-flex flex-wrap justify-content-between gap-2 small">
    <span>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></span>
    <span>
      <a href="<?= url('site/menu') ?>">Thực đơn</a> ·
      <a href="<?= url('site/book') ?>">Đặt bàn</a> ·
      <a href="<?= url('site/track') ?>">Tra cứu đặt bàn</a> ·
      <?php if (customer_logged_in()): ?><a href="<?= url('customer/account') ?>">Tài khoản khách hàng</a> · <?php else: ?><a href="<?= url('customer/login') ?>">Đăng nhập khách hàng</a> · <a href="<?= url('customer/register') ?>">Đăng ký</a> · <?php endif; ?>
      <?php if (is_logged_in()): ?><a href="<?= url('dashboard') ?>">Vào trang quản lý</a><?php else: ?><a href="<?= url('auth/login') ?>">Nhân viên đăng nhập</a><?php endif; ?>
    </span>
  </div>
</footer>
<?php include APP_PATH . '/views/layouts/_chat_widget.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
