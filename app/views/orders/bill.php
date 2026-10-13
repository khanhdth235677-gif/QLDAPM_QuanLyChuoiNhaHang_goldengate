<?php
$isPaid = $order['status'] === 'paid';
$base = (int)$order['subtotal'] - (int)$order['discount'];
$pct  = $base > 0 ? round($order['vat'] * 100 / $base) : 0;
$method = $payments ? (Order::METHODS[$payments[0]['method']] ?? $payments[0]['method']) : '';
$title = $isPaid ? 'HÓA ĐƠN THANH TOÁN' : ($order['status'] === 'cancelled' ? 'ĐƠN ĐÃ HỦY' : 'PHIẾU TẠM TÍNH');
$sumLines = 0; foreach ($lines as $l) $sumLines += (int)$l['quantity'] * (int)$l['unit_price'];
?><!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> HD<?= str_pad((string)$order['id'], 6, '0', STR_PAD_LEFT) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f4f1ea; }
    .receipt { width: 320px; margin: 16px auto; background: #fff; padding: 16px; font-family: "Courier New", monospace; font-size: 13px; color: #000; }
    .receipt h1 { font-size: 17px; margin: 0; text-align: center; }
    .receipt .c { text-align: center; }
    .receipt hr { border-top: 1px dashed #000; opacity: 1; margin: 8px 0; }
    .receipt table { width: 100%; }
    .receipt td { vertical-align: top; padding: 1px 0; }
    .receipt .r { text-align: right; white-space: nowrap; }
    .receipt .tot { font-size: 16px; font-weight: bold; }
    @media print { body { background: #fff; } .no-print { display: none !important; } .receipt { margin: 0; width: 100%; padding: 0; } }
  </style>
</head>
<body>
<div class="no-print text-center pt-3">
  <?php if ($justPaid): ?><div class="alert alert-success d-inline-block py-2">Thanh toán thành công.</div><br><?php endif; ?>
  <button class="btn btn-dark" onclick="window.print()"><i class="bi bi-printer"></i> In</button>
  <a class="btn btn-outline-dark" href="<?= url('floor') ?><?= has_role('admin') ? '?branch=' . (int)$order['branch_id'] : '' ?>">Về sơ đồ bàn</a>
  <?php if (!$isPaid && $order['status'] === 'open'): ?><a class="btn btn-outline-dark" href="<?= url('orders/show/' . (int)$order['id']) ?>">Quay lại gọi món</a><?php endif; ?>
</div>

<div class="receipt">
  <h1><?= e(APP_NAME) ?></h1>
  <div class="c"><?= e($branch['name'] ?? $order['branch_name']) ?></div>
  <?php if (!empty($branch['address'])): ?><div class="c"><?= e($branch['address']) ?></div><?php endif; ?>
  <?php if (!empty($branch['phone'])): ?><div class="c">ĐT: <?= e($branch['phone']) ?></div><?php endif; ?>
  <hr>
  <div class="c fw-bold"><?= e($title) ?></div>
  <table>
    <tr><td>Số:</td><td class="r">HD<?= str_pad((string)$order['id'], 6, '0', STR_PAD_LEFT) ?></td></tr>
    <tr><td>Bàn:</td><td class="r"><?= e($order['table_name'] ?? '-') ?></td></tr>
    <tr><td>Giờ:</td><td class="r"><?= e(date('d/m/Y H:i', strtotime($isPaid || $order['status'] === 'cancelled' ? $order['closed_at'] : 'now'))) ?></td></tr>
    <tr><td>Nhân viên:</td><td class="r"><?= e($order['created_by_name']) ?></td></tr>
  </table>
  <hr>
  <table>
    <?php foreach ($lines as $l): ?>
      <tr><td colspan="2"><?= e($l['name']) ?></td></tr>
      <?php if ($l['note']): ?><tr><td colspan="2" style="font-style:italic">&nbsp;&nbsp;(<?= e($l['note']) ?>)</td></tr><?php endif; ?>
      <tr><td>&nbsp;&nbsp;<?= (int)$l['quantity'] ?> × <?= number_format((int)$l['unit_price'], 0, ',', '.') ?></td>
          <td class="r"><?= number_format((int)$l['quantity'] * (int)$l['unit_price'], 0, ',', '.') ?></td></tr>
    <?php endforeach; ?>
  </table>
  <hr>
  <table>
    <?php if ($isPaid): ?>
      <tr><td>Tạm tính</td><td class="r"><?= money($order['subtotal']) ?></td></tr>
      <?php if ((int)$order['discount'] > 0): ?><tr><td>Giảm giá</td><td class="r">- <?= money($order['discount']) ?></td></tr><?php endif; ?>
      <tr><td>VAT (<?= $pct ?>%)</td><td class="r"><?= money($order['vat']) ?></td></tr>
      <tr class="tot"><td>TỔNG CỘNG</td><td class="r"><?= money($order['total']) ?></td></tr>
      <?php if ($method): ?><tr><td>Thanh toán</td><td class="r"><?= e($method) ?></td></tr><?php endif; ?>
    <?php else: ?>
      <tr class="tot"><td>TẠM TÍNH</td><td class="r"><?= money($sumLines) ?></td></tr>
      <tr><td colspan="2" class="c"><small>Chưa gồm VAT và giảm giá</small></td></tr>
    <?php endif; ?>
  </table>
  <hr>
  <div class="c"><?= $isPaid ? 'Cảm ơn quý khách, hẹn gặp lại!' : 'Phiếu này không có giá trị thanh toán' ?></div>
</div>
</body>
</html>
