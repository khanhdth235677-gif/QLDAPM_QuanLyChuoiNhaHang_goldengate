<?php $isEdit = $mode === 'edit'; ?>
<h1 class="h3 mb-4"><?= $isEdit ? 'Sửa chi nhánh' : 'Thêm chi nhánh' ?></h1>
<form method="post" action="<?= url($isEdit ? 'branches/edit/' . (int)$branch['id'] : 'branches/create') ?>"
      class="card card-body" style="max-width:560px" autocomplete="off">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label" for="name">Tên chi nhánh</label>
    <input class="form-control" id="name" name="name" value="<?= e($branch['name']) ?>" maxlength="100" required autofocus>
  </div>
  <div class="mb-3">
    <label class="form-label" for="address">Địa chỉ</label>
    <input class="form-control" id="address" name="address" value="<?= e($branch['address']) ?>" maxlength="255" required>
  </div>
  <div class="mb-3">
    <label class="form-label" for="phone">Điện thoại</label>
    <input class="form-control" id="phone" name="phone" value="<?= e($branch['phone'] ?? '') ?>" maxlength="20">
  </div>
  <?php if (!$isEdit): ?>
    <div class="alert alert-info py-2 small">Chi nhánh mới sẽ tự có toàn bộ món trong thực đơn với giá gốc. Bạn có thể chỉnh giá riêng sau.</div>
  <?php endif; ?>
  <div class="d-flex gap-2">
    <button class="btn btn-warning"><?= $isEdit ? 'Lưu thay đổi' : 'Thêm chi nhánh' ?></button>
    <a class="btn btn-outline-secondary" href="<?= url('branches') ?>">Hủy</a>
  </div>
</form>
