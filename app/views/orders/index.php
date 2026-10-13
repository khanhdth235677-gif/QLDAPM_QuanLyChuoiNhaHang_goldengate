<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 m-0">Đơn đang mở</h1>
  <a class="btn btn-warning" href="<?= url('floor') ?>"><i class="bi bi-grid-3x3-gap"></i> Sơ đồ bàn</a>
</div>
<div class="table-responsive" style="max-width:860px">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light"><tr><th>Bàn</th><?php if ($isAdmin): ?><th>Chi nhánh</th><?php endif; ?><th class="text-center">Số món</th><th>Đã mở</th><th class="text-end">Tạm tính</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td class="fw-semibold"><?= e($o['table_name'] ?? '-') ?></td>
      <?php if ($isAdmin): ?><td><?= e($o['branch_name']) ?></td><?php endif; ?>
      <td class="text-center"><?= (int)$o['item_count'] ?></td>
      <td><?= (int)$o['minutes'] ?> phút</td>
      <td class="text-end"><?= money($o['total']) ?></td>
      <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= url('orders/show/' . (int)$o['id']) ?>">Mở đơn</a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$orders): ?><tr><td colspan="6" class="text-center text-muted py-4">Hiện không có đơn nào đang mở.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
