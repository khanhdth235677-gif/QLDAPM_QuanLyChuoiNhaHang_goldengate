<div class="d-flex justify-content-between align-items-center mb-3 qr-no-print">
  <div>
    <h1 class="h3 mb-1">Mã QR gọi món</h1>
    <div class="text-muted"><?= e($table['table_name']) ?> · <?= e($table['branch_name'] ?? '') ?></div>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-warning" type="button" onclick="window.print()"><i class="bi bi-printer"></i> In mã QR</button>
    <a class="btn btn-outline-secondary" href="<?= url('tables') ?>">Quay lại</a>
  </div>
</div>

<div class="card mx-auto text-center p-4 qr-print-card" style="max-width:520px">
  <div class="fw-semibold fs-4"><?= e(APP_NAME) ?></div>
  <div class="text-muted">Quét mã để xem thực đơn và gọi món</div>
  <div id="tableQr" class="d-flex justify-content-center my-3" aria-label="Mã QR của bàn"></div>
  <div class="fw-bold fs-3">Bàn <?= e($table['table_name']) ?></div>
  <div class="small text-break text-muted mt-2"><?= e($qrUrl) ?></div>
  <div id="qrError" class="alert alert-danger mt-3 mb-0" hidden>Không tải được thư viện tạo QR. Hãy kiểm tra kết nối Internet rồi tải lại trang.</div>
</div>

<style>
@media print {
  body * { visibility: hidden !important; }
  .qr-print-card, .qr-print-card * { visibility: visible !important; }
  .qr-print-card { position: absolute; inset: 0 auto auto 0; width: 100%; max-width: none !important; border: 0 !important; }
  .qr-no-print { display: none !important; }
}
</style>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
(function () {
  var target = document.getElementById('tableQr');
  if (typeof QRCode === 'undefined') {
    document.getElementById('qrError').hidden = false;
    return;
  }
  new QRCode(target, {
    text: <?= json_js($qrUrl) ?>,
    width: 280,
    height: 280,
    correctLevel: QRCode.CorrectLevel.M
  });
})();
</script>
