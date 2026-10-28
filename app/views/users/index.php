<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 m-0">Quản lý tài khoản</h1>
  <a class="btn btn-warning" href="<?= url('users/create') ?>"><i class="bi bi-plus-lg"></i> Thêm tài khoản</a>
</div>
<div class="table-responsive">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light">
    <tr><th>Tên đăng nhập</th><th>Họ tên</th><th>Vai trò</th><th>Chi nhánh</th><th>Trạng thái</th><th>Đăng nhập gần nhất</th><th class="text-end">Thao tác</th></tr>
  </thead>
  <tbody>
  <?php foreach ($users as $r): $self = (int)$r['id'] === (int)$me; $id = (int)$r['id']; ?>
    <tr>
      <td><?= e($r['username']) ?><?= $self ? ' <span class="badge bg-info text-dark">bạn</span>' : '' ?></td>
      <td><?= e($r['full_name']) ?></td>
      <td><?= e($r['role_name']) ?></td>
      <td><?= $r['branch_name'] ? e($r['branch_name']) : '<span class="text-muted">Toàn chuỗi</span>' ?></td>
      <td><?= (int)$r['status'] === 1 ? '<span class="badge bg-success">Hoạt động</span>' : '<span class="badge bg-secondary">Đã khóa</span>' ?></td>
      <td><?= $r['last_login'] ? e(date('d/m/Y H:i', strtotime($r['last_login']))) : '<span class="text-muted">Chưa từng</span>' ?></td>
      <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-outline-primary" href="<?= url('users/edit/' . $id) ?>">Sửa</a>
        <?php if (!$self): ?>
          <form method="post" action="<?= url('users/toggle/' . $id) ?>" class="d-inline">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-secondary"><?= (int)$r['status'] === 1 ? 'Khóa' : 'Mở khóa' ?></button>
          </form>
          <form method="post" action="<?= url('users/resetpassword/' . $id) ?>" class="d-inline"
                onsubmit="return confirm('Đặt lại mật khẩu cho <?= e($r['username']) ?>?')">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-danger">Đặt lại MK</button>
          </form>
          <form method="post" action="<?= url('users/delete/' . $id) ?>" class="d-inline"
                onsubmit="return confirm('Xóa tài khoản <?= e($r['username']) ?>? Nếu đã phát sinh dữ liệu nghiệp vụ, tài khoản sẽ bị khóa thay vì xóa hẳn.')">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-danger">Xóa</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$users): ?><tr><td colspan="7" class="text-center text-muted py-4">Chưa có tài khoản nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
