<h1 class="h3 mb-3">Thực đơn</h1>
<?php include APP_PATH . '/views/menu/_tabs.php'; ?>

<div class="d-flex flex-wrap align-items-center gap-3 mb-3">
  <?php if ($canPick): ?>
    <label class="m-0">Chi nhánh:</label>
    <select class="form-select w-auto" onchange="location = '<?= url('menu/branch') ?>/' + this.value">
      <?php foreach ($branches as $b): ?>
        <option value="<?= (int)$b['id'] ?>" <?= (int)$b['id'] === (int)$branch['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
      <?php endforeach; ?>
    </select>
  <?php else: ?>
    <div class="fw-semibold"><i class="bi bi-shop"></i> <?= e($branch['name']) ?></div>
  <?php endif; ?>
</div>

<form method="post" action="<?= url('menu/branch/' . (int)$branch['id']) ?>">
  <?= csrf_field() ?>
  <div class="table-responsive">
  <table class="table align-middle bg-white">
    <thead class="table-light">
      <tr><th style="width:64px"></th><th>Món</th><th>Danh mục</th><th class="text-end">Giá gốc</th><th style="width:170px">Giá tại chi nhánh</th><th class="text-center">Đang bán</th></tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): $id = (int)$r['id']; ?>
      <tr class="<?= (int)$r['status'] === 1 ? '' : 'text-muted' ?>">
        <td><?php if ($r['image']): ?><img class="thumb" src="<?= asset($r['image']) ?>" alt=""><?php else: ?><div class="thumb thumb-empty"><i class="bi bi-image"></i></div><?php endif; ?></td>
        <td><?= e($r['name']) ?><?= (int)$r['status'] === 1 ? '' : ' <span class="badge bg-secondary">ẩn toàn chuỗi</span>' ?></td>
        <td><?= e($r['category_name']) ?></td>
        <td class="text-end"><?= money($r['base_price']) ?></td>
        <td><input class="form-control form-control-sm text-end" name="price[<?= $id ?>]" inputmode="numeric" value="<?= (int)$r['price'] ?>" required></td>
        <td class="text-center"><input class="form-check-input" type="checkbox" name="avail[<?= $id ?>]" value="1" <?= (int)$r['is_available'] ? 'checked' : '' ?>></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">Chưa có món nào trong thực đơn.</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
  <?php if ($rows): ?><button class="btn btn-warning">Lưu giá và tình trạng bán</button><?php endif; ?>
</form>
