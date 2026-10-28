<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 m-0">Quản lý voucher</h1>
  <a class="btn btn-warning" href="<?= url('vouchers/create') ?>"><i class="bi bi-plus-lg"></i> Tạo voucher</a>
</div>
<div class="table-responsive">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light"><tr><th>Mã</th><th>Nội dung</th><th>Giảm giá</th><th>Thời hạn</th><th>Lượt dùng</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
  <tbody>
  <?php foreach ($vouchers as $v): ?>
    <?php $used = (int)$v['usage_count']; $limit = $v['usage_limit'] === null ? null : (int)$v['usage_limit']; ?>
    <tr>
      <td><code class="fw-bold"><?= e($v['code']) ?></code></td>
      <td><?= e($v['description']) ?><div class="small text-muted">Đơn tối thiểu <?= money($v['min_subtotal']) ?></div></td>
      <td><?= $v['discount_type'] === 'percent' ? (int)$v['discount_value'] . '%' : money($v['discount_value']) ?>
        <?php if ($v['max_discount'] !== null): ?><div class="small text-muted">Tối đa <?= money($v['max_discount']) ?></div><?php endif; ?></td>
      <td class="small"><?= e(date('d/m/Y H:i', strtotime($v['starts_at']))) ?><br>đến <?= e(date('d/m/Y H:i', strtotime($v['ends_at']))) ?></td>
      <td><?= $used ?> / <?= $limit === null ? 'không giới hạn' : $limit ?></td>
      <td>
        <?php if (!(int)$v['is_active']): ?><span class="badge bg-secondary">Đã khóa</span>
        <?php elseif (strtotime($v['ends_at']) < time()): ?><span class="badge bg-dark">Hết hạn</span>
        <?php elseif ($limit !== null && $used >= $limit): ?><span class="badge bg-warning text-dark">Hết lượt</span>
        <?php else: ?><span class="badge bg-success">Đang hoạt động</span><?php endif; ?>
      </td>
      <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-outline-primary" href="<?= url('vouchers/edit/' . (int)$v['id']) ?>">Sửa</a>
        <form method="post" action="<?= url('vouchers/toggle/' . (int)$v['id']) ?>" class="d-inline">
          <?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary"><?= (int)$v['is_active'] ? 'Khóa' : 'Kích hoạt' ?></button>
        </form>
        <form method="post" action="<?= url('vouchers/delete/' . (int)$v['id']) ?>" class="d-inline"
              onsubmit="return confirm('Xóa voucher <?= e($v['code']) ?>? Nếu đã được sử dụng, voucher sẽ bị khóa và lịch sử được giữ.')">
          <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Xóa</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$vouchers): ?><tr><td colspan="7" class="text-center text-muted py-4">Chưa có voucher nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
