<h1 class="h3 mb-3">Phiếu kho</h1>
<?php include APP_PATH . '/views/inventory/_tabs.php'; ?>
<div class="text-muted mb-3"><i class="bi bi-shop"></i> <?= e($branchName) ?></div>

<form method="post" action="<?= url('inventory/move') ?>?branch=<?= (int)$bid ?>" class="card card-body" style="max-width:560px" autocomplete="off">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label d-block">Loại phiếu</label>
    <?php foreach (Inventory::TYPES as $k => $label): ?>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="type" id="t_<?= $k ?>" value="<?= $k ?>" <?= $form['type'] === $k ? 'checked' : '' ?>>
        <label class="form-check-label" for="t_<?= $k ?>"><?= e($label) ?><?= $k === 'in' ? ' (+)' : ($k === 'out' ? ' (−)' : '') ?></label>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="mb-3">
    <label class="form-label" for="ingredient_id">Nguyên liệu</label>
    <select class="form-select" id="ingredient_id" name="ingredient_id" required>
      <option value="">-- Chọn nguyên liệu --</option>
      <?php foreach ($stock as $s): ?>
        <option value="<?= (int)$s['id'] ?>" data-qty="<?= e(qty_fmt($s['quantity'])) ?>" data-unit="<?= e($s['unit']) ?>"
                <?= (int)$form['ingredient_id'] === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?> (<?= e($s['unit']) ?>)</option>
      <?php endforeach; ?>
    </select>
    <div class="form-text">Tồn hiện tại: <strong id="curQty">-</strong></div>
  </div>

  <div class="mb-3">
    <label class="form-label" for="quantity" id="qtyLabel">Số lượng</label>
    <input class="form-control" id="quantity" name="quantity" inputmode="decimal" value="<?= e($form['quantity']) ?>" required>
    <div class="form-text" id="qtyHelp">Dùng dấu phẩy hoặc chấm cho phần thập phân, ví dụ 12,5. Không dùng dấu ngăn cách hàng nghìn.</div>
  </div>

  <div class="mb-3">
    <label class="form-label" for="note">Ghi chú</label>
    <input class="form-control" id="note" name="note" maxlength="255" value="<?= e($form['note']) ?>" placeholder="Nhà cung cấp, số phiếu, lý do hao hụt...">
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-warning">Ghi phiếu</button>
    <a class="btn btn-outline-secondary" href="<?= url('inventory') ?>?branch=<?= (int)$bid ?>">Hủy</a>
  </div>
</form>
<script>
(function () {
  var sel = document.getElementById('ingredient_id'), cur = document.getElementById('curQty');
  var label = document.getElementById('qtyLabel'), help = document.getElementById('qtyHelp');
  var radios = document.querySelectorAll('input[name=type]');
  function sync() {
    var o = sel.options[sel.selectedIndex];
    cur.textContent = o && o.dataset.qty !== undefined ? o.dataset.qty + ' ' + o.dataset.unit : '-';
    var t = document.querySelector('input[name=type]:checked').value;
    label.textContent = t === 'adjust' ? 'Số lượng thực tế đếm được' : (t === 'in' ? 'Số lượng nhập' : 'Số lượng xuất');
    help.style.display = '';
  }
  sel.addEventListener('change', sync);
  Array.prototype.forEach.call(radios, function (r) { r.addEventListener('change', sync); });
  sync();
})();
</script>
