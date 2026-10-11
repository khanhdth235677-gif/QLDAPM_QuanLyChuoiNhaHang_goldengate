<?php $isEdit = $mode === 'edit'; ?>
<h1 class="h3 mb-4"><?= $isEdit ? 'Sửa bàn' : 'Thêm bàn' ?></h1>
<form method="post" action="<?= $isEdit ? url('tables/edit/' . (int)$table['id']) : url('tables/create') . '?branch=' . (int)$bid ?>"
      class="card card-body" style="max-width:480px" autocomplete="off">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label" for="table_name">Tên bàn</label>
    <input class="form-control" id="table_name" name="table_name" value="<?= e($table['table_name']) ?>" maxlength="30" required autofocus>
  </div>
  <div class="mb-3">
    <label class="form-label" for="seats">Số chỗ ngồi</label>
    <input class="form-control" id="seats" name="seats" type="number" min="1" max="50" value="<?= (int)$table['seats'] ?>" required>
  </div>
  <?php if ($isEdit): ?>
  <div class="mb-3">
    <label class="form-label" for="status">Trạng thái</label>
    <select class="form-select" id="status" name="status">
      <?php foreach ($status as $k => $v): ?>
        <option value="<?= e($k) ?>" <?= $table['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
      <?php endforeach; ?>
    </select>
    <div class="form-text">Bình thường trạng thái tự đổi khi mở bàn và thanh toán (giai đoạn 5). Chỉ chỉnh tay khi cần sửa sai.</div>
  </div>
  <?php endif; ?>
  <div class="d-flex gap-2">
    <button class="btn btn-warning"><?= $isEdit ? 'Lưu thay đổi' : 'Thêm bàn' ?></button>
    <a class="btn btn-outline-secondary" href="<?= url('tables') ?>?branch=<?= (int)$bid ?>">Hủy</a>
  </div>
</form>
