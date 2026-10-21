<?php
$oid = (int)$order['id'];
$hidden = $cat ? '<input type="hidden" name="cat" value="' . (int)$cat . '">' : '';
$sumQty = 0; foreach ($lines as $l) $sumQty += (int)$l['quantity'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h3 m-0">Bàn <?= e($order['table_name'] ?? '-') ?></h1>
    <div class="text-muted small"><?= e($order['branch_name']) ?> · mở <?= (int)$order['minutes'] ?> phút trước · bởi <?= e($order['created_by_name']) ?></div>
  </div>
  <a class="btn btn-outline-dark" href="<?= url('floor') ?><?= has_role('admin') ? '?branch=' . (int)$order['branch_id'] : '' ?>"><i class="bi bi-arrow-left"></i> Sơ đồ bàn</a>
</div>

<div class="row g-4">
  <!-- Thực đơn -->
  <div class="col-lg-7">
    <?php if ($suggest): ?>
    <div class="card card-body py-2 mb-3 border-warning">
      <div class="small fw-semibold mb-2"><i class="bi bi-stars text-warning"></i> <?= e($suggestTitle) ?></div>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($suggest as $sg): ?>
          <form method="post" action="<?= url('orders/additem/' . $oid) ?>" class="m-0"><?= csrf_field() ?><?= $hidden ?>
            <input type="hidden" name="menu_item_id" value="<?= (int)$sg['id'] ?>">
            <button class="btn btn-sm btn-outline-warning text-dark"><?= e($sg['name']) ?> <span class="text-muted">· <?= money($sg['price']) ?></span></button>
          </form>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
    <div class="mb-3 d-flex flex-wrap gap-2">
      <a class="btn btn-sm <?= !$cat ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= url('orders/show/' . $oid) ?>">Tất cả</a>
      <?php foreach ($cats as $c): ?>
        <a class="btn btn-sm <?= $cat === (int)$c['id'] ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= url('orders/show/' . $oid) ?>?cat=<?= (int)$c['id'] ?>"><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </div>
    <div class="row g-2">
      <?php foreach ($menu as $m): ?>
      <div class="col-6 col-md-4">
        <form method="post" action="<?= url('orders/additem/' . $oid) ?>" class="m-0">
          <?= csrf_field() ?><?= $hidden ?>
          <input type="hidden" name="menu_item_id" value="<?= (int)$m['id'] ?>">
          <button class="menu-card">
            <?php if ($m['image']): ?><img src="<?= asset($m['image']) ?>" alt=""><?php else: ?><div class="menu-card-img"><i class="bi bi-image"></i></div><?php endif; ?>
            <div class="p-2">
              <div class="fw-semibold small"><?= e($m['name']) ?></div>
              <div class="text-success small"><?= money($m['price']) ?></div>
            </div>
          </button>
        </form>
      </div>
      <?php endforeach; ?>
      <?php if (!$menu): ?><div class="col-12 text-muted">Không có món nào đang bán<?= $cat ? ' trong danh mục này' : '' ?>.</div><?php endif; ?>
    </div>
  </div>

  <!-- Đơn hiện tại -->
  <div class="col-lg-5">
    <div class="card card-body py-2 mb-3">
      <?php if ($customer): $tc = $customer['tier']['cls'] . (in_array($customer['tier']['cls'], ['warning', 'info'], true) ? ' text-dark' : ''); ?>
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="fw-semibold"><i class="bi bi-person-heart"></i> <?= e($customer['name']) ?> <span class="badge bg-<?= $tc ?>"><?= e($customer['tier']['name']) ?></span></div>
            <div class="small text-muted"><?= e($customer['phone']) ?> · <?= (int)$customer['sum']['visits'] ?> lần ghé<?= $customer['on'] ? ' · <strong>' . number_format($customer['sum']['balance'], 0, ',', '.') . ' điểm</strong>' : '' ?></div>
          </div>
          <form method="post" action="<?= url('orders/customer/' . $oid) ?>" class="m-0"><?= csrf_field() ?><?= $hidden ?>
            <input type="hidden" name="action" value="remove"><button class="btn btn-sm btn-outline-secondary" title="Bỏ gắn khách">&times;</button></form>
        </div>
      <?php else: ?>
        <form method="post" action="<?= url('orders/customer/' . $oid) ?>" class="m-0"><?= csrf_field() ?><?= $hidden ?>
          <div class="small fw-semibold mb-1"><i class="bi bi-person-heart"></i> Khách thân thiết</div>
          <div class="row g-1">
            <div class="col-5"><input class="form-control form-control-sm" name="phone" type="tel" placeholder="Số điện thoại" required></div>
            <div class="col-5"><input class="form-control form-control-sm" name="name" maxlength="100" placeholder="Tên (nếu khách mới)"></div>
            <div class="col-2"><button class="btn btn-sm btn-outline-dark w-100">Gắn</button></div>
          </div>
        </form>
      <?php endif; ?>
    </div>
    <div class="card order-panel">
      <div class="card-header fw-semibold">Món đã gọi (<?= $sumQty ?>)</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($lines as $l):
          $lid = (int)$l['id']; $ks = $l['kitchen_status']; $editable = $ks === 'new' || $canManage;
          [$ksText, $ksCls] = OrdersController::LINE_STATUS[$ks]; ?>
        <li class="list-group-item">
          <div class="d-flex justify-content-between">
            <div>
              <span class="fw-semibold"><?= e($l['name']) ?></span>
              <span class="badge bg-<?= $ksCls ?> <?= $ksCls === 'warning' || $ksCls === 'info' ? 'text-dark' : '' ?>"><?= $ksText ?></span>
              <div class="small text-muted"><?= money($l['unit_price']) ?> × <?= (int)$l['quantity'] ?></div>
            </div>
            <div class="fw-semibold text-end"><?= money($l['unit_price'] * $l['quantity']) ?></div>
          </div>
          <?php if ($editable): ?>
            <div class="d-flex align-items-center gap-1 mt-2">
              <form method="post" action="<?= url('orders/qty/' . $lid) ?>" class="m-0"><?= csrf_field() ?><?= $hidden ?>
                <input type="hidden" name="delta" value="-1"><button class="btn btn-sm btn-outline-secondary px-2">−</button></form>
              <span class="px-2 fw-semibold"><?= (int)$l['quantity'] ?></span>
              <form method="post" action="<?= url('orders/qty/' . $lid) ?>" class="m-0"><?= csrf_field() ?><?= $hidden ?>
                <input type="hidden" name="delta" value="1"><button class="btn btn-sm btn-outline-secondary px-2">+</button></form>
              <form method="post" action="<?= url('orders/note/' . $lid) ?>" class="m-0 d-flex flex-grow-1 gap-1 ms-2"><?= csrf_field() ?><?= $hidden ?>
                <input class="form-control form-control-sm" name="note" maxlength="255" value="<?= e($l['note'] ?? '') ?>" placeholder="Ghi chú (ít cay, không hành...)">
                <button class="btn btn-sm btn-outline-primary">Lưu</button></form>
              <form method="post" action="<?= url('orders/remove/' . $lid) ?>" class="m-0 ms-1" onsubmit="return confirm('Xóa món này khỏi đơn?')"><?= csrf_field() ?><?= $hidden ?>
                <button class="btn btn-sm btn-outline-danger px-2"><i class="bi bi-trash"></i></button></form>
            </div>
          <?php elseif ($l['note']): ?>
            <div class="small fst-italic mt-1">Ghi chú: <?= e($l['note']) ?></div>
          <?php endif; ?>
          <?php if ($ks === 'ready'): ?>
            <form method="post" action="<?= url('orders/serve/' . $lid) ?>" class="m-0 mt-2"><?= csrf_field() ?><?= $hidden ?>
              <button class="btn btn-sm btn-info"><i class="bi bi-check2-circle"></i> Đã mang ra bàn</button></form>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
        <?php if (!$lines): ?><li class="list-group-item text-muted">Chưa có món nào. Bấm vào món bên trái để thêm.</li><?php endif; ?>
      </ul>
      <div class="card-body border-top">
        <div class="d-flex justify-content-between fs-5 fw-bold"><span>Tạm tính</span><span><?= money($order['total']) ?></span></div>
        <div class="small text-muted mb-3">Chưa gồm VAT và giảm giá. Hai khoản này tính ở màn hình thanh toán.</div>
        <?php if ($lines && has_role('admin', 'manager', 'cashier')): ?>
          <a class="btn btn-success w-100 mb-2" href="<?= url('orders/pay/' . $oid) ?>"><i class="bi bi-cash-coin"></i> Thanh toán</a>
        <?php elseif ($lines): ?>
          <div class="small text-muted mb-2">Phục vụ gọi thu ngân để thanh toán.</div>
        <?php endif; ?>
        <?php if ($lines): ?>
          <a class="btn btn-outline-dark w-100 mb-3" target="_blank" href="<?= url('orders/bill/' . $oid) ?>"><i class="bi bi-printer"></i> In phiếu tạm tính</a>
        <?php endif; ?>

        <?php if ($emptyTables): ?>
        <form method="post" action="<?= url('orders/move/' . $oid) ?>" class="d-flex gap-2 mb-2"><?= csrf_field() ?><?= $hidden ?>
          <select class="form-select form-select-sm" name="table_id" required>
            <option value="">Chuyển sang bàn trống...</option>
            <?php foreach ($emptyTables as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['table_name']) ?> (<?= (int)$t['seats'] ?> chỗ)</option><?php endforeach; ?>
          </select>
          <button class="btn btn-sm btn-outline-dark text-nowrap">Chuyển bàn</button>
        </form>
        <?php endif; ?>

        <form method="post" action="<?= url('orders/cancel/' . $oid) ?>" onsubmit="return confirm('Hủy đơn của bàn này?')"><?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-danger">Hủy đơn</button>
          <?php if ($lines && !$canManage): ?><span class="small text-muted ms-2">Đơn đã có món, chỉ quản lý được hủy.</span><?php endif; ?>
        </form>
      </div>
    </div>
  </div>
</div>
