<?php $isEdit = $mode === 'edit'; ?>
<h1 class="h3 mb-4"><?= $isEdit ? 'Sửa món' : 'Thêm món' ?></h1>
<form method="post" enctype="multipart/form-data" action="<?= url($isEdit ? 'menu/edit/' . (int)$item['id'] : 'menu/create') ?>"
      class="card card-body" style="max-width:640px" autocomplete="off">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label" for="category_id">Danh mục</label>
      <select class="form-select" id="category_id" name="category_id" required>
        <option value="">-- Chọn danh mục --</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (int)$item['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label" for="base_price">Giá gốc (VNĐ)</label>
      <input class="form-control" id="base_price" name="base_price" inputmode="numeric" value="<?= e($item['base_price']) ?>" required>
    </div>
    <div class="col-12">
      <label class="form-label" for="name">Tên món</label>
      <input class="form-control" id="name" name="name" value="<?= e($item['name']) ?>" maxlength="150" required>
    </div>
    <div class="col-12">
      <label class="form-label" for="description">Mô tả</label>
      <textarea class="form-control" id="description" name="description" rows="3" maxlength="2000"><?= e($item['description'] ?? '') ?></textarea>
    </div>
    <div class="col-12">
      <label class="form-label" for="image">Ảnh món (JPG, PNG, WebP, tối đa 2 MB)</label>
      <?php if ($isEdit && $item['image']): ?>
        <div class="mb-2 d-flex align-items-center gap-3">
          <img src="<?= asset($item['image']) ?>" alt="" style="height:80px;border-radius:8px">
          <label class="form-check-label"><input class="form-check-input me-1" type="checkbox" name="remove_image" value="1">Xóa ảnh hiện tại</label>
        </div>
      <?php endif; ?>
      <input class="form-control" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
    </div>
    <div class="col-12">
      <div class="form-check"><input class="form-check-input" type="checkbox" id="status" name="status" value="1" <?= (int)$item['status'] === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="status">Đang bán</label></div>
      <?php if ($isEdit): ?>
      <div class="form-check"><input class="form-check-input" type="checkbox" id="apply_all" name="apply_all" value="1">
        <label class="form-check-label" for="apply_all">Áp dụng giá gốc mới cho mọi chi nhánh (ghi đè giá riêng từng chi nhánh)</label></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="d-flex gap-2 mt-4">
    <button class="btn btn-warning"><?= $isEdit ? 'Lưu thay đổi' : 'Thêm món' ?></button>
    <a class="btn btn-outline-secondary" href="<?= url('menu') ?>">Hủy</a>
  </div>
</form>
