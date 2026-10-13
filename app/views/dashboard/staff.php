<?php $me = auth_user(); ?>
<h1 class="h3 mb-1">Xin chào, <?= e($me['full_name']) ?></h1>
<p class="text-muted mb-4">Chi nhánh: <?= e($me['branch_name'] ?? 'Chưa được gán chi nhánh') ?></p>
<div class="row g-3">
  <div class="col-6 col-xl-3"><div class="card border-success"><div class="card-body">
    <div class="text-muted">Bàn trống</div><div class="fs-3 fw-semibold text-success"><?= $tables['empty'] ?></div></div></div></div>
  <div class="col-6 col-xl-3"><div class="card border-danger"><div class="card-body">
    <div class="text-muted">Đang dùng</div><div class="fs-3 fw-semibold text-danger"><?= $tables['occupied'] ?></div></div></div></div>
  <div class="col-6 col-xl-3"><div class="card border-warning"><div class="card-body">
    <div class="text-muted">Đã đặt</div><div class="fs-3 fw-semibold"><?= $tables['reserved'] ?></div></div></div></div>
  <div class="col-6 col-xl-3"><div class="card border-primary"><div class="card-body">
    <div class="text-muted">Đơn đang mở</div><div class="fs-3 fw-semibold"><?= $open ?></div></div></div></div>
</div>
<a class="btn btn-warning btn-lg mt-4 me-2" href="<?= url('floor') ?>"><i class="bi bi-grid-3x3-gap"></i> Mở sơ đồ bàn</a>
<a class="btn btn-outline-dark btn-lg mt-4" href="<?= url('orders') ?>">Đơn đang mở</a>
