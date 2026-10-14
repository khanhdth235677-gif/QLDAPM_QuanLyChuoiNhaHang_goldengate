<h1 class="h3 mb-3">Thực đơn</h1>
<?php include APP_PATH . '/views/menu/_tabs.php'; ?>

<div class="mb-3">
  <div class="fs-5 fw-semibold"><?= e($item['name']) ?></div>
  <div class="text-muted small"><?= e($item['category_name']) ?> · định lượng tính cho <strong>1 phần</strong></div>
</div>

<?php if (!$canEdit): ?>
  <div class="card card-body" style="max-width:560px">
    <?php if ($lines): ?>
      <ul class="mb-0">
        <?php foreach ($lines as $l): ?><li><?= e($l['name']) ?>: <strong><?= qty_fmt($l['quantity']) ?></strong> <?= e($l['unit']) ?></li><?php endforeach; ?>
      </ul>
    <?php else: ?>
      <span class="text-muted">Món này chưa có công thức. Chỉ admin được chỉnh sửa công thức.</span>
    <?php endif; ?>
  </div>
  <a class="btn btn-outline-secondary mt-3" href="<?= url('recipes') ?>">Quay lại</a>
<?php else: ?>
<?php if (!$ingredients): ?>
  <div class="alert alert-warning">Chưa có nguyên liệu nào. <a href="<?= url('inventory/ingredients') ?>">Thêm nguyên liệu</a> trước.</div>
<?php else: ?>
<?php if (!$lines) $lines = [['ingredient_id' => 0, 'quantity' => '', 'raw' => true]]; ?>
<form method="post" action="<?= url('recipes/edit/' . (int)$item['id']) ?>" class="card card-body" style="max-width:640px" autocomplete="off">
  <?= csrf_field() ?>
  <table class="table table-sm align-middle mb-2">
    <thead><tr><th>Nguyên liệu</th><th style="width:130px" class="text-end">Định lượng</th><th style="width:110px">Đơn vị</th><th style="width:44px"></th></tr></thead>
    <tbody id="rows">
    <?php foreach ($lines as $l): ?>
      <tr>
        <td>
          <select class="form-select form-select-sm ing" name="ingredient_id[]">
            <option value="0" data-unit="">-- Chọn nguyên liệu --</option>
            <?php foreach ($ingredients as $i): ?>
              <option value="<?= (int)$i['id'] ?>" data-unit="<?= e($i['unit']) ?>" <?= (int)$l['ingredient_id'] === (int)$i['id'] ? 'selected' : '' ?>><?= e($i['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><input class="form-control form-control-sm text-end" name="qty[]" inputmode="decimal" value="<?= e(!empty($l['raw']) ? $l['quantity'] : qty_fmt($l['quantity'])) ?>"></td>
        <td class="unit text-muted small"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger rm" title="Xóa dòng">&times;</button></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div><button type="button" class="btn btn-sm btn-outline-dark" id="add"><i class="bi bi-plus-lg"></i> Thêm nguyên liệu</button></div>
  <div class="form-text mt-2">Định lượng dùng số dương, tối đa 2 chữ số thập phân (ví dụ 0,2). Nếu cần nhỏ hơn, hãy đổi đơn vị nguyên liệu sang g hoặc ml. Để trống tất cả các dòng để xóa công thức.</div>
  <div class="d-flex gap-2 mt-3">
    <button class="btn btn-warning">Lưu công thức</button>
    <a class="btn btn-outline-secondary" href="<?= url('recipes') ?>">Hủy</a>
  </div>
</form>
<script>
(function () {
  var tb = document.getElementById('rows');
  function sync(tr) {
    var s = tr.querySelector('.ing'), o = s.options[s.selectedIndex];
    tr.querySelector('.unit').textContent = o && o.dataset.unit ? o.dataset.unit : '';
  }
  tb.addEventListener('change', function (e) { if (e.target.classList.contains('ing')) sync(e.target.closest('tr')); });
  tb.addEventListener('click', function (e) {
    var btn = e.target.closest('.rm');
    if (!btn) return;
    var tr = btn.closest('tr');
    if (tb.rows.length > 1) { tr.remove(); }
    else { tr.querySelector('.ing').value = '0'; tr.querySelector('input').value = ''; sync(tr); }
  });
  document.getElementById('add').addEventListener('click', function () {
    var c = tb.rows[0].cloneNode(true);
    c.querySelector('.ing').value = '0';
    c.querySelector('input').value = '';
    tb.appendChild(c); sync(c);
  });
  Array.prototype.forEach.call(tb.rows, sync);
})();
</script>
<?php endif; ?>
<?php endif; ?>
