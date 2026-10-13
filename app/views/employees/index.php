<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="h3 m-0">Nhân viên</h1>
  <a class="btn btn-warning" href="<?= url('employees/create') ?><?= $isAdmin && $branchId ? '?branch=' . $branchId : '' ?>"><i class="bi bi-plus-lg"></i> Thêm nhân viên</a>
</div>

<div class="row g-3 mb-3">
  <div class="col-sm-6 col-xl-3"><div class="card border-primary"><div class="card-body">
    <div class="text-muted">Đang làm việc</div><div class="fs-4 fw-semibold"><?= (int)$stats['cnt'] ?></div></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="card border-success"><div class="card-body">
    <div class="text-muted">Tổng lương tháng (đang làm)</div><div class="fs-4 fw-semibold text-success"><?= money($stats['total']) ?></div></div></div></div>
</div>

<form method="get" action="<?= url('employees') ?>" class="row g-2 mb-3">
  <?php if ($isAdmin): ?>
  <div class="col-sm-3">
    <select class="form-select" name="branch">
      <option value="0">Tất cả chi nhánh</option>
      <?php foreach ($branches as $b): ?>
        <option value="<?= (int)$b['id'] ?>" <?= (int)$b['id'] === $branchId ? 'selected' : '' ?>><?= e($b['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>
  <div class="col-sm-3">
    <select class="form-select" name="st">
      <option value="active"   <?= $st === 'active'   ? 'selected' : '' ?>>Đang làm việc</option>
      <option value="inactive" <?= $st === 'inactive' ? 'selected' : '' ?>>Đã nghỉ việc</option>
      <option value="all"      <?= $st === 'all'      ? 'selected' : '' ?>>Tất cả</option>
    </select>
  </div>
  <div class="col-sm-4"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Tìm theo tên hoặc số điện thoại"></div>
  <div class="col-sm-2"><button class="btn btn-outline-dark w-100">Lọc</button></div>
</form>

<div class="table-responsive">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light">
    <tr><th>Họ tên</th><th>Chức vụ</th><?php if ($isAdmin): ?><th>Chi nhánh</th><?php endif; ?><th>Điện thoại</th><th>Vào làm</th><th class="text-end">Lương</th><th>Tài khoản</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $r): $id = (int)$r['id']; $active = (int)$r['status'] === 1; ?>
    <tr class="<?= $active ? '' : 'text-muted' ?>">
      <td class="fw-semibold"><?= e($r['full_name']) ?></td>
      <td><?= e($r['position']) ?></td>
      <?php if ($isAdmin): ?><td><?= e($r['branch_name']) ?></td><?php endif; ?>
      <td><?= e($r['phone'] ?? '') ?></td>
      <td><?= $r['hired_date'] ? e(date('d/m/Y', strtotime($r['hired_date']))) : '' ?></td>
      <td class="text-end"><?= money($r['salary']) ?></td>
      <td><?= $r['username'] ? '<span class="badge bg-info text-dark">' . e($r['username']) . '</span>' : '<span class="text-muted">-</span>' ?></td>
      <td><?= $active ? '<span class="badge bg-success">Đang làm</span>' : '<span class="badge bg-secondary">Nghỉ việc</span>' ?></td>
      <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-outline-primary" href="<?= url('employees/edit/' . $id) ?>">Sửa</a>
        <form method="post" action="<?= url('employees/toggle/' . $id) ?>" class="d-inline"
              onsubmit="return confirm('<?= $active ? 'Đánh dấu nhân viên này nghỉ việc?' : 'Cho nhân viên này đi làm lại?' ?>')">
          <?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary"><?= $active ? 'Nghỉ việc' : 'Đi làm lại' ?></button>
        </form>
        <?php if ($isAdmin): ?>
        <form method="post" action="<?= url('employees/delete/' . $id) ?>" class="d-inline" onsubmit="return confirm('Xóa hẳn hồ sơ nhân viên này?')">
          <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Xóa</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">Không có nhân viên nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<p class="text-muted small">Khi đánh dấu nghỉ việc, tài khoản đăng nhập gắn với nhân viên (nếu có) sẽ tự bị khóa. Nên dùng "Nghỉ việc" thay vì xóa để giữ lịch sử.</p>
