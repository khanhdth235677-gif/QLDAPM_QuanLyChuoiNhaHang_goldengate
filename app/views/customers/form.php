<?php $isEdit = $mode === 'edit'; ?>
<h1 class="h3 mb-4"><?= $isEdit ? 'Sửa khách hàng' : 'Thêm khách hàng' ?></h1>
<form method="post" action="<?= url($isEdit ? 'customers/edit/' . (int)$c['id'] : 'customers/create') ?>" class="card card-body" style="max-width:480px" autocomplete="off">
  <?= csrf_field() ?>
  <div class="mb-3"><label class="form-label" for="full_name">Họ tên</label>
    <input class="form-control" id="full_name" name="full_name" maxlength="100" value="<?= e($c['full_name']) ?>" required autofocus></div>
  <div class="mb-3"><label class="form-label" for="phone">Số điện thoại</label>
    <input class="form-control" id="phone" name="phone" type="tel" maxlength="20" value="<?= e($c['phone'] ?? '') ?>" placeholder="0901234567" required>
    <div class="form-text">Số điện thoại là mã nhận diện khách, mỗi số chỉ một hồ sơ.</div></div>
  <div class="d-flex gap-2">
    <button class="btn btn-warning"><?= $isEdit ? 'Lưu thay đổi' : 'Thêm khách' ?></button>
    <a class="btn btn-outline-secondary" href="<?= url($isEdit ? 'customers/show/' . (int)$c['id'] : 'customers') ?>">Hủy</a>
  </div>
</form>
