<h1 class="h3 mb-3">Thực đơn</h1>
<?php include APP_PATH . '/views/menu/_tabs.php'; ?>

<div class="card card-body mb-4" style="max-width:640px">
  <form method="post" action="<?= url('menu/savecategory') ?>" class="row g-2 align-items-end">
    <?= csrf_field() ?>
    <div class="col-sm-6"><label class="form-label small mb-1">Tên danh mục mới</label>
      <input class="form-control" name="name" maxlength="100" required></div>
    <div class="col-sm-3"><label class="form-label small mb-1">Thứ tự</label>
      <input class="form-control" name="sort_order" type="number" min="0" max="999" value="0"></div>
    <div class="col-sm-3"><button class="btn btn-warning w-100">Thêm</button></div>
  </form>
</div>

<div class="table-responsive" style="max-width:760px">
<table class="table align-middle bg-white">
  <thead class="table-light"><tr><th>Tên danh mục</th><th style="width:110px">Thứ tự</th><th class="text-center">Số món</th><th class="text-end">Thao tác</th></tr></thead>
  <tbody>
  <?php foreach ($cats as $c): $id = (int)$c['id']; ?>
    <tr>
      <form method="post" action="<?= url('menu/savecategory/' . $id) ?>" id="cat<?= $id ?>"><?= csrf_field() ?></form>
      <td><input form="cat<?= $id ?>" class="form-control form-control-sm" name="name" value="<?= e($c['name']) ?>" maxlength="100" required></td>
      <td><input form="cat<?= $id ?>" class="form-control form-control-sm" name="sort_order" type="number" min="0" max="999" value="<?= (int)$c['sort_order'] ?>"></td>
      <td class="text-center"><?= (int)$c['item_count'] ?></td>
      <td class="text-end text-nowrap">
        <button form="cat<?= $id ?>" class="btn btn-sm btn-outline-primary">Lưu</button>
        <form method="post" action="<?= url('menu/deletecategory/' . $id) ?>" class="d-inline" onsubmit="return confirm('Xóa danh mục này?')">
          <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Xóa</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$cats): ?><tr><td colspan="4" class="text-center text-muted py-4">Chưa có danh mục nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<p class="text-muted small">Danh mục xếp theo số thứ tự nhỏ đến lớn. Chỉ xóa được danh mục không còn món.</p>
