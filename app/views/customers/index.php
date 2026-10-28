<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="h3 m-0">Khách hàng</h1>
  <a class="btn btn-warning" href="<?= url('customers/create') ?>"><i class="bi bi-plus-lg"></i> Thêm khách</a>
</div>

<?php if (!$on): ?>
  <div class="alert alert-warning">Chưa bật tính năng điểm thưởng. Hãy kiểm tra cấu trúc database có bảng sổ điểm khách hàng. Danh sách khách vẫn xem được nhưng điểm hiện bằng 0.</div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <div class="col-sm-6 col-xl-3"><div class="card border-primary"><div class="card-body"><div class="text-muted">Số khách (theo bộ lọc)</div><div class="fs-4 fw-semibold"><?= count($rows) ?></div></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="card border-warning"><div class="card-body"><div class="text-muted">Tổng điểm khách đang giữ</div><div class="fs-4 fw-semibold"><?= number_format($points, 0, ',', '.') ?></div></div></div></div>
</div>

<form method="get" action="<?= url('customers') ?>" class="row g-2 mb-3" style="max-width:860px">
  <div class="col-sm-4"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Tìm theo tên hoặc số điện thoại"></div>
  <div class="col-sm-3">
    <select class="form-select" name="tier"><option value="">Mọi hạng</option>
      <?php foreach (LOYALTY_TIERS as $i => $t): ?><option value="<?= $i ?>" <?= $tierFilter === $i ? 'selected' : '' ?>><?= e($t[0]) ?></option><?php endforeach; ?></select>
  </div>
  <div class="col-sm-3">
    <select class="form-select" name="sort">
      <option value="points" <?= $sort === 'points' ? 'selected' : '' ?>>Nhiều điểm nhất</option>
      <option value="spent"  <?= $sort === 'spent'  ? 'selected' : '' ?>>Chi tiêu nhiều nhất</option>
      <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Ghé gần đây nhất</option>
      <option value="name"   <?= $sort === 'name'   ? 'selected' : '' ?>>Theo tên</option>
    </select>
  </div>
  <div class="col-sm-2"><button class="btn btn-outline-dark w-100">Lọc</button></div>
</form>

<div class="table-responsive">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light"><tr><th>Khách</th><th>Điện thoại</th><th>Trạng thái</th><th>Hạng</th><th class="text-end">Điểm hiện có</th><th class="text-end">Lượt ghé</th><th class="text-end">Tổng chi tiêu</th><th>Lần cuối</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td class="fw-semibold"><?= e($r['full_name']) ?></td>
      <td><?= e($r['phone']) ?></td>
      <td><?= (int)$r['status'] ? '<span class="badge bg-success">Hoạt động</span>' : '<span class="badge bg-secondary">Đã lưu trữ</span>' ?></td>
      <td><span class="badge bg-<?= e($r['tier']['cls']) ?> <?= in_array($r['tier']['cls'], ['warning', 'info'], true) ? 'text-dark' : '' ?>"><?= e($r['tier']['name']) ?></span></td>
      <td class="text-end fw-semibold"><?= number_format((int)$r['balance'], 0, ',', '.') ?></td>
      <td class="text-end"><?= (int)$r['visits'] ?></td>
      <td class="text-end"><?= money($r['spent']) ?></td>
      <td class="small"><?= $r['last_visit'] ? e(date('d/m/Y', strtotime($r['last_visit']))) : '<span class="text-muted">Chưa có</span>' ?></td>
      <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-outline-primary" href="<?= url('customers/show/' . (int)$r['id']) ?>">Xem</a>
        <?php if (has_role('admin', 'manager')): ?>
          <form method="post" action="<?= url('customers/delete/' . (int)$r['id']) ?>" class="d-inline"
                onsubmit="return confirm('<?= (int)$r['status'] ? 'Xóa' : 'Khôi phục' ?> hồ sơ khách <?= e($r['full_name']) ?>? <?= (int)$r['status'] ? 'Nếu có lịch sử, hồ sơ sẽ được lưu trữ.' : '' ?>')">
            <?= csrf_field() ?><button class="btn btn-sm <?= (int)$r['status'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"><?= (int)$r['status'] ? 'Xóa' : 'Khôi phục' ?></button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">Không có khách nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<p class="text-muted small">Khách được tạo tự động khi đặt bàn online, hoặc khi nhân viên gắn số điện thoại vào đơn lúc gọi món. Hạng tính theo tổng điểm đã tích lũy.
  Quy tắc điểm: <?= number_format(LOYALTY_VND_PER_POINT, 0, ',', '.') ?> ₫ = 1 điểm, 1 điểm dùng giảm <?= number_format(LOYALTY_POINT_VALUE, 0, ',', '.') ?> ₫ (chỉnh trong <code>config/loyalty.php</code>).</p>
