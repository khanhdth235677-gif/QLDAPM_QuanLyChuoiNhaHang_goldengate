<div class="container pt-4" style="max-width:960px">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h1 class="h3 mb-1">Gọi món tại bàn <?= e($table['table_name']) ?></h1>
      <div class="text-muted"><i class="bi bi-shop"></i> <?= e($table['branch_name']) ?></div>
    </div>
    <a class="btn btn-outline-dark" href="<?= url('site/menu/' . (int)$table['branch_id']) ?>">Xem thực đơn</a>
  </div>

  <?php if ($sent): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> Đã gửi món tới bếp. Nhân viên sẽ mang món ra bàn.</div>
  <?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

  <?php if (!$orderOpen): ?>
    <div class="alert alert-warning">Bàn chưa được nhân viên mở đơn. Vui lòng báo nhân viên; sau khi mở bàn, bạn có thể gửi món trực tiếp từ điện thoại.</div>
  <?php elseif (!$menu): ?>
    <div class="alert alert-info">Thực đơn của chi nhánh hiện chưa có món đang bán.</div>
  <?php else: ?>
    <p class="text-muted">Chọn số lượng và ghi chú cho từng món. Món gửi đi sẽ được chuyển thẳng vào đơn đang mở và hiển thị trên màn hình bếp.</p>
    <form method="post" action="<?= e($orderUrl) ?>" class="card card-body" autocomplete="off">
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($table['qr_token']) ?>">
      <div class="position-absolute" style="left:-10000px" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
      <?php
      $categories = [];
      foreach ($menu as $item) $categories[$item['category_name']][] = $item;
      ?>
      <?php foreach ($categories as $category => $items): ?>
        <h2 class="h5 mt-3 mb-2 border-bottom pb-2"><?= e($category) ?></h2>
        <?php foreach ($items as $item): $itemId = (int)$item['id']; ?>
          <div class="row align-items-center g-2 py-2 border-bottom">
            <div class="col">
              <div class="fw-semibold"><?= e($item['name']) ?></div>
              <?php if ($item['description']): ?><div class="small text-muted"><?= e(mb_strimwidth($item['description'], 0, 100, '...')) ?></div><?php endif; ?>
              <div class="text-success"><?= money($item['price']) ?></div>
            </div>
            <div class="col-4 col-sm-3">
              <label class="form-label small mb-1" for="qty<?= $itemId ?>">Số lượng</label>
              <input class="form-control" id="qty<?= $itemId ?>" type="number" name="items[<?= $itemId ?>]" min="0" max="<?= Order::MAX_QTY ?>" value="<?= (int)($values[$itemId] ?? 0) ?>">
            </div>
            <div class="col-12">
              <label class="form-label small mb-1" for="note<?= $itemId ?>">Ghi chú (không bắt buộc)</label>
              <input class="form-control form-control-sm" id="note<?= $itemId ?>" name="notes[<?= $itemId ?>]" maxlength="255" placeholder="Ví dụ: ít cay, không hành">
            </div>
          </div>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <button class="btn btn-warning btn-lg mt-3"><i class="bi bi-send"></i> Gửi món tới bếp</button>
      <div class="form-text mt-2">Đơn gọi món không cần nhân viên xác nhận. Vui lòng kiểm tra số lượng trước khi gửi.</div>
    </form>
  <?php endif; ?>
</div>
