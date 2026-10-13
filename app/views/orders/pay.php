<?php $oid = (int)$order['id']; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h3 m-0">Thanh toán - Bàn <?= e($order['table_name'] ?? '-') ?></h1>
    <div class="text-muted small"><?= e($order['branch_name']) ?> · mở <?= (int)$order['minutes'] ?> phút trước</div>
  </div>
  <a class="btn btn-outline-dark" href="<?= url('orders/show/' . $oid) ?>"><i class="bi bi-arrow-left"></i> Quay lại gọi món</a>
</div>

<?php if ($unserved > 0): ?>
  <div class="alert alert-warning py-2">Còn <strong><?= $unserved ?></strong> phần chưa được đánh dấu đã phục vụ. Vẫn có thể thanh toán bình thường.</div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header fw-semibold">Chi tiết đơn</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($lines as $l): ?>
        <li class="list-group-item d-flex justify-content-between">
          <div><?= e($l['name']) ?> <span class="text-muted">× <?= (int)$l['quantity'] ?></span>
            <?php if ($l['note']): ?><div class="small fst-italic text-muted"><?= e($l['note']) ?></div><?php endif; ?></div>
          <div><?= money($l['unit_price'] * $l['quantity']) ?></div>
        </li>
        <?php endforeach; ?>
      </ul>
      <div class="card-footer d-flex justify-content-between fw-semibold"><span>Tạm tính (chưa VAT)</span><span><?= money($subtotal) ?></span></div>
    </div>
  </div>

  <div class="col-lg-6">
    <form method="post" action="<?= url('orders/pay/' . $oid) ?>" id="payForm" class="card card-body" autocomplete="off">
      <?= csrf_field() ?>

      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label" for="discount_mode">Giảm giá</label>
          <select class="form-select" id="discount_mode" name="discount_mode">
            <option value="none">Không giảm</option>
            <option value="percent">Theo %</option>
            <option value="amount">Số tiền (VNĐ)</option>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label" for="discount_value">Giá trị giảm</label>
          <input class="form-control" id="discount_value" name="discount_value" inputmode="numeric" disabled>
        </div>
        <?php if ($maxDiscount !== null): ?>
          <div class="col-12 form-text">Thu ngân giảm tối đa <?= (int)$maxDiscount ?>% tạm tính. Mức cao hơn cần quản lý.</div>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label class="form-label" for="vat_rate">VAT</label>
        <select class="form-select" id="vat_rate" name="vat_rate">
          <?php foreach (VAT_OPTIONS as $v): ?>
            <option value="<?= (int)$v ?>" <?= $v === VAT_DEFAULT ? 'selected' : '' ?>><?= (int)$v ?>%</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="border rounded p-3 mb-3 bg-light">
        <div class="d-flex justify-content-between"><span>Tạm tính</span><span><?= money($subtotal) ?></span></div>
        <div class="d-flex justify-content-between text-danger"><span>Giảm giá</span><span id="sumDiscount">0 ₫</span></div>
        <div class="d-flex justify-content-between"><span>VAT</span><span id="sumVat">0 ₫</span></div>
        <hr class="my-2">
        <div class="d-flex justify-content-between fs-4 fw-bold"><span>Cần thanh toán</span><span id="sumTotal">0 ₫</span></div>
      </div>

      <div class="mb-3">
        <label class="form-label d-block">Phương thức</label>
        <?php foreach (Order::METHODS as $k => $label): ?>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="method" id="m_<?= $k ?>" value="<?= $k ?>" <?= $k === 'cash' ? 'checked' : '' ?>>
            <label class="form-check-label" for="m_<?= $k ?>"><?= e($label) ?></label>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="mb-3" id="cashBox">
        <label class="form-label" for="cash_received">Tiền khách đưa (VNĐ)</label>
        <input class="form-control" id="cash_received" name="cash_received" inputmode="numeric">
        <div class="form-text">Tiền thối lại: <strong id="changeText">0 ₫</strong></div>
      </div>

      <button class="btn btn-success btn-lg" onclick="return confirm('Xác nhận thanh toán và đóng đơn?')">Xác nhận thanh toán</button>
    </form>
  </div>
</div>

<script>
(function () {
  var sub = <?= (int)$subtotal ?>;
  var f = document.getElementById('payForm');
  function digits(v) { return parseInt(String(v).replace(/\D/g, ''), 10) || 0; }
  function fmt(n) { return n.toLocaleString('vi-VN') + ' ₫'; }
  function calc() {
    var mode = f.discount_mode.value, val = digits(f.discount_value.value), rate = parseInt(f.vat_rate.value, 10) || 0;
    var d = 0;
    if (mode === 'percent') d = Math.round(sub * Math.min(val, 100) / 100);
    else if (mode === 'amount') d = val;
    d = Math.max(0, Math.min(d, sub));
    var base = sub - d, vat = Math.round(base * rate / 100), total = base + vat;
    document.getElementById('sumDiscount').textContent = d > 0 ? '- ' + fmt(d) : fmt(0);
    document.getElementById('sumVat').textContent = fmt(vat);
    document.getElementById('sumTotal').textContent = fmt(total);
    var cash = f.method.value === 'cash';
    document.getElementById('cashBox').style.display = cash ? '' : 'none';
    document.getElementById('changeText').textContent = fmt(Math.max(0, digits(f.cash_received.value) - total));
  }
  f.discount_mode.addEventListener('change', function () {
    f.discount_value.disabled = f.discount_mode.value === 'none';
    if (f.discount_value.disabled) f.discount_value.value = '';
    calc();
  });
  ['input', 'change'].forEach(function (ev) { f.addEventListener(ev, calc); });
  calc();
})();
</script>
