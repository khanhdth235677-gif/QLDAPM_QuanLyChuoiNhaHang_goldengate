<?php $isAdmin = has_role('admin'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 m-0">Thực đơn</h1>
  <?php if ($isAdmin): ?><a class="btn btn-warning" href="<?= url('menu/create') ?>"><i class="bi bi-plus-lg"></i> Thêm món</a><?php endif; ?>
</div>
<?php include APP_PATH . '/views/menu/_tabs.php'; ?>

<div class="mb-3 d-flex flex-wrap gap-2">
  <a class="btn btn-sm <?= !$cat ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= url('menu') ?>">Tất cả</a>
  <?php foreach ($cats as $c): ?>
    <a class="btn btn-sm <?= $cat === (int)$c['id'] ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= url('menu') ?>?cat=<?= (int)$c['id'] ?>"><?= e($c['name']) ?> (<?= (int)$c['item_count'] ?>)</a>
  <?php endforeach; ?>
</div>

<div class="table-responsive">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light">
    <tr><th style="width:64px"></th><th>Món</th><th>Danh mục</th><th class="text-end">Giá gốc</th><th>Trạng thái</th><?php if ($isAdmin): ?><th class="text-end">Thao tác</th><?php endif; ?></tr>
  </thead>
  <tbody>
  <?php foreach ($items as $m): $id = (int)$m['id']; ?>
    <tr class="<?= (int)$m['status'] === 1 ? '' : 'text-muted' ?>">
      <td><?php if ($m['image']): ?><img class="thumb" src="<?= asset($m['image']) ?>" alt=""><?php else: ?><div class="thumb thumb-empty"><i class="bi bi-image"></i></div><?php endif; ?></td>
      <td><div class="fw-semibold"><?= e($m['name']) ?></div>
          <?php if ($m['description']): ?><div class="small text-muted"><?= e(mb_strimwidth($m['description'], 0, 70, '...')) ?></div><?php endif; ?></td>
      <td><?= e($m['category_name']) ?></td>
      <td class="text-end"><?= money($m['base_price']) ?></td>
      <td><?= (int)$m['status'] === 1 ? '<span class="badge bg-success">Đang bán</span>' : '<span class="badge bg-secondary">Đã ẩn</span>' ?></td>
      <?php if ($isAdmin): ?>
      <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-outline-primary" href="<?= url('menu/edit/' . $id) ?>">Sửa</a>
        <form method="post" action="<?= url('menu/toggle/' . $id) ?>" class="d-inline">
          <?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary"><?= (int)$m['status'] === 1 ? 'Ẩn' : 'Hiện' ?></button>
        </form>
        <form method="post" action="<?= url('menu/delete/' . $id) ?>" class="d-inline" onsubmit="return confirm('Xóa món này?')">
          <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Xóa</button>
        </form>
      </td>
      <?php endif; ?>
    </tr>
  <?php endforeach; ?>
  <?php if (!$items): ?><tr><td colspan="6" class="text-center text-muted py-4">Chưa có món nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
