<?php
$isEdit = $mode === 'edit';
$asInput = static function ($value): string {
    $time = strtotime((string)$value);
    return $time ? date('Y-m-d\TH:i', $time) : (string)$value;
};
?>
<h1 class="h3 mb-4"><?= $isEdit ? 'Sửa voucher' : 'Tạo voucher' ?></h1>
<form method="post" action="<?= url($isEdit ? 'vouchers/edit/' . (int)$voucher['id'] : 'vouchers/create') ?>" class="card card-body" style="max-width:720px" autocomplete="off">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-5"><label class="form-label" for="code">Mã voucher</label>
      <input class="form-control text-uppercase" id="code" name="code" value="<?= e($voucher['code']) ?>" maxlength="40" pattern="[A-Za-z0-9_-]{3,40}" required></div>
    <div class="col-md-7"><label class="form-label" for="description">Tên / mô tả</label>
      <input class="form-control" id="description" name="description" value="<?= e($voucher['description']) ?>" maxlength="255" required></div>
    <div class="col-md-4"><label class="form-label" for="discount_type">Loại giảm giá</label>
      <select class="form-select" id="discount_type" name="discount_type">
        <option value="percent" <?= $voucher['discount_type'] === 'percent' ? 'selected' : '' ?>>Phần trăm</option>
        <option value="fixed" <?= $voucher['discount_type'] === 'fixed' ? 'selected' : '' ?>>Số tiền (VNĐ)</option>
      </select></div>
    <div class="col-md-4"><label class="form-label" for="discount_value">Giá trị giảm</label>
      <input class="form-control" type="number" id="discount_value" name="discount_value" min="1" value="<?= e($voucher['discount_value']) ?>" required></div>
    <div class="col-md-4"><label class="form-label" for="min_subtotal">Đơn tối thiểu (VNĐ)</label>
      <input class="form-control" type="number" id="min_subtotal" name="min_subtotal" min="0" value="<?= e($voucher['min_subtotal']) ?>" required></div>
    <div class="col-md-4"><label class="form-label" for="max_discount">Mức giảm tối đa (không bắt buộc)</label>
      <input class="form-control" type="number" id="max_discount" name="max_discount" min="1" value="<?= e($voucher['max_discount'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label" for="usage_limit">Tổng lượt dùng (không bắt buộc)</label>
      <input class="form-control" type="number" id="usage_limit" name="usage_limit" min="1" value="<?= e($voucher['usage_limit'] ?? '') ?>"></div>
    <div class="col-md-4 d-flex align-items-end pb-2"><div class="form-check">
      <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= !empty($voucher['is_active']) ? 'checked' : '' ?>>
      <label class="form-check-label" for="is_active">Kích hoạt ngay</label></div></div>
    <div class="col-md-6"><label class="form-label" for="starts_at">Bắt đầu</label>
      <input class="form-control" type="datetime-local" id="starts_at" name="starts_at" value="<?= e($asInput($voucher['starts_at'])) ?>" required></div>
    <div class="col-md-6"><label class="form-label" for="ends_at">Kết thúc</label>
      <input class="form-control" type="datetime-local" id="ends_at" name="ends_at" value="<?= e($asInput($voucher['ends_at'])) ?>" required></div>
  </div>
  <div class="form-text mt-3">Mỗi đơn hàng áp dụng tối đa một voucher. Có thể để trống giới hạn tổng lượt dùng.</div>
  <div class="d-flex gap-2 mt-3">
    <button class="btn btn-warning"><?= $isEdit ? 'Lưu thay đổi' : 'Tạo voucher' ?></button>
    <a class="btn btn-outline-secondary" href="<?= url('vouchers') ?>">Hủy</a>
  </div>
</form>
<?php if ($isEdit): ?>
<form method="post" action="<?= url('vouchers/delete/' . (int)$voucher['id']) ?>" class="mt-3"
      onsubmit="return confirm('Xóa voucher <?= e($voucher['code']) ?>? Nếu đã sử dụng, voucher sẽ bị khóa và lịch sử được giữ.')">
  <?= csrf_field() ?><button class="btn btn-outline-danger">Xóa voucher</button>
</form>
<?php endif; ?>
