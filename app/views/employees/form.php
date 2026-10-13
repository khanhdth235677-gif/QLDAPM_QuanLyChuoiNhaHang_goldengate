<?php $isEdit = $mode === 'edit'; ?>
<h1 class="h3 mb-4"><?= $isEdit ? 'Sửa nhân viên' : 'Thêm nhân viên' ?></h1>
<form method="post" action="<?= url($isEdit ? 'employees/edit/' . (int)$emp['id'] : 'employees/create') ?>"
      class="card card-body" style="max-width:640px" autocomplete="off">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-8">
      <label class="form-label" for="full_name">Họ tên</label>
      <input class="form-control" id="full_name" name="full_name" value="<?= e($emp['full_name']) ?>" maxlength="100" required autofocus>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="phone">Điện thoại</label>
      <input class="form-control" id="phone" name="phone" value="<?= e($emp['phone'] ?? '') ?>" maxlength="20">
    </div>

    <div class="col-md-6">
      <label class="form-label" for="position">Chức vụ</label>
      <input class="form-control" id="position" name="position" list="positions" value="<?= e($emp['position']) ?>" maxlength="50" required>
      <datalist id="positions"><?php foreach ($positions as $p): ?><option value="<?= e($p) ?>"><?php endforeach; ?></datalist>
    </div>
    <div class="col-md-6">
      <label class="form-label" for="salary">Lương tháng (VNĐ)</label>
      <input class="form-control" id="salary" name="salary" inputmode="numeric" value="<?= e($emp['salary']) ?>" required>
    </div>

    <div class="col-md-6">
      <label class="form-label" for="branch_id">Chi nhánh</label>
      <?php if ($isAdmin): ?>
        <select class="form-select" id="branch_id" name="branch_id" required>
          <option value="">-- Chọn chi nhánh --</option>
          <?php foreach ($branches as $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= (int)$emp['branch_id'] === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <input class="form-control" value="<?= e($actor['branch_name']) ?>" disabled>
      <?php endif; ?>
    </div>
    <div class="col-md-6">
      <label class="form-label" for="hired_date">Ngày vào làm</label>
      <input class="form-control" id="hired_date" name="hired_date" type="date" max="<?= date('Y-m-d') ?>" value="<?= e($emp['hired_date'] ?? '') ?>">
    </div>

    <div class="col-12">
      <label class="form-label" for="user_id">Tài khoản đăng nhập (không bắt buộc)</label>
      <select class="form-select" id="user_id" name="user_id">
        <option value="0" data-branch="">-- Không gắn tài khoản --</option>
        <?php foreach ($linkable as $l): ?>
          <option value="<?= (int)$l['id'] ?>" data-branch="<?= (int)$l['branch_id'] ?>" <?= (int)($emp['user_id'] ?? 0) === (int)$l['id'] ? 'selected' : '' ?>>
            <?= e($l['username']) ?> - <?= e($l['full_name']) ?> (<?= e($l['role_name']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <div class="form-text">Chỉ hiện tài khoản cùng chi nhánh và chưa gắn cho nhân viên khác. Tạo tài khoản mới ở mục Tài khoản.</div>
    </div>
  </div>
  <div class="d-flex gap-2 mt-4">
    <button class="btn btn-warning"><?= $isEdit ? 'Lưu thay đổi' : 'Thêm nhân viên' ?></button>
    <a class="btn btn-outline-secondary" href="<?= url('employees') ?>">Hủy</a>
  </div>
</form>
<?php if ($isAdmin): ?>
<script>
// Lọc danh sách tài khoản theo chi nhánh đang chọn
(function () {
  var br = document.getElementById('branch_id'), us = document.getElementById('user_id');
  function sync() {
    var b = br.value;
    Array.prototype.forEach.call(us.options, function (o) {
      var show = o.dataset.branch === '' || o.dataset.branch === b;
      o.hidden = !show; o.disabled = !show;
    });
    if (us.options[us.selectedIndex].disabled) us.value = '0';
  }
  br.addEventListener('change', sync);
  sync();
})();
</script>
<?php endif; ?>
