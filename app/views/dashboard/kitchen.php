<?php $me = auth_user(); ?>
<h1 class="h3 mb-1">Bếp - <?= e($me['full_name']) ?></h1>
<p class="text-muted mb-4">Chi nhánh: <?= e($me['branch_name'] ?? 'Chưa được gán chi nhánh') ?></p>
<div class="row g-3">
  <div class="col-sm-6"><div class="card border-danger"><div class="card-body">
    <div class="text-muted">Món mới, chờ chế biến</div><div class="fs-2 fw-semibold text-danger"><?= $q['new'] ?></div></div></div></div>
  <div class="col-sm-6"><div class="card border-warning"><div class="card-body">
    <div class="text-muted">Đang nấu</div><div class="fs-2 fw-semibold"><?= $q['cooking'] ?></div></div></div></div>
</div>
<div class="alert alert-info mt-4">Màn hình bếp chi tiết (nhận món, báo xong) sẽ có ở giai đoạn 5.</div>
