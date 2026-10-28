<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 m-0">Chi nhánh</h1>
  <a class="btn btn-warning" href="<?= url('branches/create') ?>"><i class="bi bi-plus-lg"></i> Thêm chi nhánh</a>
</div>
<div class="table-responsive">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light">
    <tr><th>Tên</th><th>Địa chỉ</th><th>Điện thoại</th><th class="text-center">Bàn</th><th class="text-center">Tài khoản</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr>
  </thead>
  <tbody>
  <?php foreach ($branches as $b): $id = (int)$b['id']; ?>
    <tr>
      <td class="fw-semibold"><?= e($b['name']) ?></td>
      <td><?= e($b['address']) ?></td>
      <td><?= e($b['phone'] ?? '') ?></td>
      <td class="text-center"><a href="<?= url('tables') ?>?branch=<?= $id ?>"><?= (int)$b['tables_count'] ?></a></td>
      <td class="text-center"><?= (int)$b['users_count'] ?></td>
      <td><?= (int)$b['status'] === 1 ? '<span class="badge bg-success">Hoạt động</span>' : '<span class="badge bg-secondary">Ngừng</span>' ?></td>
      <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-outline-primary" href="<?= url('branches/edit/' . $id) ?>">Sửa</a>
        <a class="btn btn-sm btn-outline-dark" href="<?= url('menu/branch/' . $id) ?>">Giá món</a>
        <form method="post" action="<?= url('branches/toggle/' . $id) ?>" class="d-inline"
              onsubmit="return confirm('<?= (int)$b['status'] === 1 ? 'Ngừng hoạt động' : 'Kích hoạt lại' ?> chi nhánh này?')">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-secondary"><?= (int)$b['status'] === 1 ? 'Ngừng' : 'Kích hoạt' ?></button>
        </form>
        <form method="post" action="<?= url('branches/delete/' . $id) ?>" class="d-inline"
              onsubmit="return confirm('Xóa chi nhánh <?= e($b['name']) ?>? Nếu có bàn, đơn hoặc dữ liệu liên quan, chi nhánh sẽ được ngừng hoạt động thay vì xóa hẳn.')">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-danger">Xóa</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$branches): ?><tr><td colspan="7" class="text-center text-muted py-4">Chưa có chi nhánh nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<p class="text-muted small">Chi nhánh ngừng hoạt động sẽ không xuất hiện khi chọn chi nhánh ở các màn hình khác. Dữ liệu cũ vẫn được giữ nguyên.</p>
