<?php $isEdit = $mode === 'edit'; ?>
<h1 class="h3 mb-4"><?= $isEdit ? 'Sửa tài khoản' : 'Thêm tài khoản' ?></h1>
<form method="post" action="<?= url($isEdit ? 'users/edit/' . (int)$user['id'] : 'users/create') ?>"
      class="card card-body" style="max-width:560px" autocomplete="off">
  <?= csrf_field() ?>

  <div class="mb-3">
    <label class="form-label" for="username">Tên đăng nhập</label>
    <input class="form-control" id="username" name="username" value="<?= e($user['username']) ?>"
           <?= $isEdit ? 'readonly' : 'required pattern="[A-Za-z0-9_.]{3,50}"' ?>>
    <?php if (!$isEdit): ?><div class="form-text">3-50 ký tự: chữ không dấu, số, dấu chấm, gạch dưới.</div><?php endif; ?>
  </div>

  <div class="mb-3">
    <label class="form-label" for="full_name">Họ tên</label>
    <input class="form-control" id="full_name" name="full_name" value="<?= e($user['full_name']) ?>" maxlength="100" required>
  </div>

  <div class="mb-3">
    <label class="form-label" for="role_id">Vai trò</label>
    <select class="form-select" id="role_id" name="role_id" required <?= $isSelf ? 'disabled' : '' ?>>
      <option value="">-- Chọn vai trò --</option>
      <?php foreach ($roles as $r): ?>
        <option value="<?= (int)$r['id'] ?>" data-code="<?= e($r['code']) ?>" <?= (int)$user['role_id'] === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if ($isSelf): ?><div class="form-text">Bạn không thể tự đổi vai trò của chính mình.</div><?php endif; ?>
  </div>

  <div class="mb-3" id="branchBox">
    <label class="form-label" for="branch_id">Chi nhánh</label>
    <?php if ($actor['role'] === 'admin'): ?>
      <select class="form-select" id="branch_id" name="branch_id" <?= $isSelf ? 'disabled' : '' ?>>
        <option value="">-- Chọn chi nhánh --</option>
        <?php foreach ($branches as $b): ?>
          <option value="<?= (int)$b['id'] ?>" <?= (int)$user['branch_id'] === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
        <?php endforeach; ?>
      </select>
    <?php else: ?>
      <input class="form-control" value="<?= e($actor['branch_name']) ?>" disabled>
      <div class="form-text">Quản lý chỉ tạo tài khoản cho chi nhánh của mình.</div>
    <?php endif; ?>
  </div>

  <?php if (!$isEdit): ?>
  <div class="mb-3">
    <label class="form-label" for="password">Mật khẩu ban đầu</label>
    <input class="form-control" id="password" name="password" type="text" minlength="8" required>
    <div class="form-text">Tối thiểu 8 ký tự. Người dùng sẽ bị buộc đổi ở lần đăng nhập đầu.</div>
  </div>
  <?php endif; ?>

  <div class="d-flex gap-2">
    <button class="btn btn-warning"><?= $isEdit ? 'Lưu thay đổi' : 'Tạo tài khoản' ?></button>
    <a class="btn btn-outline-secondary" href="<?= url('users') ?>">Hủy</a>
  </div>
</form>
<?php if ($isEdit && !$isSelf): ?>
<form method="post" action="<?= url('users/delete/' . (int)$user['id']) ?>" class="mt-3"
      onsubmit="return confirm('Xóa tài khoản <?= e($user['username']) ?>? Nếu đã phát sinh dữ liệu nghiệp vụ, tài khoản sẽ bị khóa thay vì xóa hẳn.')">
  <?= csrf_field() ?><button class="btn btn-outline-danger">Xóa tài khoản</button>
</form>
<?php endif; ?>
<script>
(function () {
  var sel = document.getElementById('role_id'), box = document.getElementById('branchBox');
  function sync() {
    var opt = sel.options[sel.selectedIndex];
    box.style.display = (opt && opt.dataset.code === 'admin') ? 'none' : '';
  }
  sel.addEventListener('change', sync);
  sync();
})();
</script>
