<?php $isEdit = $mode === 'edit'; ?>
<h1 class="h3 mb-4"><?= $isEdit ? 'Sửa đặt bàn' : 'Thêm đặt bàn' ?></h1>
<form method="post" action="<?= url($isEdit ? 'reservations/edit/' . (int)$res['id'] : 'reservations/create') ?>"
      class="card card-body" style="max-width:640px" autocomplete="off">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-7">
      <label class="form-label" for="customer_name">Tên khách</label>
      <input class="form-control" id="customer_name" name="customer_name" value="<?= e($res['customer_name']) ?>" maxlength="100" required autofocus>
    </div>
    <div class="col-md-5">
      <label class="form-label" for="customer_phone">Điện thoại</label>
      <input class="form-control" id="customer_phone" name="customer_phone" value="<?= e($res['customer_phone']) ?>" maxlength="20" required>
    </div>

    <div class="col-md-7">
      <label class="form-label" for="reserved_at">Ngày giờ đến</label>
      <input class="form-control" id="reserved_at" name="reserved_at" type="datetime-local" step="900"
             value="<?= e($res['at_input']) ?>" <?= $isEdit ? '' : 'min="' . date('Y-m-d\TH:i') . '"' ?> required>
    </div>
    <div class="col-md-5">
      <label class="form-label" for="guests">Số khách</label>
      <input class="form-control" id="guests" name="guests" type="number" min="1" max="200" value="<?= (int)$res['guests'] ?>" required>
    </div>

    <?php if ($isAdmin): ?>
    <div class="col-md-6">
      <label class="form-label" for="branch_id">Chi nhánh</label>
      <select class="form-select" id="branch_id" name="branch_id" required>
        <?php foreach ($branches as $b): ?>
          <option value="<?= (int)$b['id'] ?>" <?= (int)$res['branch_id'] === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="<?= $isAdmin ? 'col-md-6' : 'col-12' ?>">
      <label class="form-label" for="table_id">Bàn (có thể xếp sau)</label>
      <select class="form-select" id="table_id" name="table_id">
        <option value="0" data-branch="">-- Chưa xếp bàn --</option>
        <?php foreach ($tables as $t): ?>
          <option value="<?= (int)$t['id'] ?>" data-branch="<?= (int)$t['branch_id'] ?>" <?= (int)($res['table_id'] ?? 0) === (int)$t['id'] ? 'selected' : '' ?>>
            <?= e($t['table_name']) ?> (<?= (int)$t['seats'] ?> chỗ)</option>
        <?php endforeach; ?>
      </select>
      <div class="form-text">Hệ thống kiểm tra đủ chỗ ngồi và không trùng giờ với lượt đặt khác.</div>
    </div>

    <div class="col-12">
      <label class="form-label" for="note">Ghi chú</label>
      <input class="form-control" id="note" name="note" value="<?= e($res['note'] ?? '') ?>" maxlength="255" placeholder="Sinh nhật, cần ghế trẻ em, gần cửa sổ...">
    </div>
  </div>
  <div class="d-flex gap-2 mt-4">
    <button class="btn btn-warning"><?= $isEdit ? 'Lưu thay đổi' : 'Ghi nhận đặt bàn' ?></button>
    <a class="btn btn-outline-secondary" href="<?= url('reservations') ?>">Hủy</a>
  </div>
</form>
<?php if ($isAdmin): ?>
<script>
// Chỉ hiện các bàn thuộc chi nhánh đang chọn
(function () {
  var br = document.getElementById('branch_id'), tb = document.getElementById('table_id');
  function sync() {
    Array.prototype.forEach.call(tb.options, function (o) {
      var show = o.dataset.branch === '' || o.dataset.branch === br.value;
      o.hidden = !show; o.disabled = !show;
    });
    if (tb.options[tb.selectedIndex].disabled) tb.value = '0';
  }
  br.addEventListener('change', sync);
  sync();
})();
</script>
<?php endif; ?>
