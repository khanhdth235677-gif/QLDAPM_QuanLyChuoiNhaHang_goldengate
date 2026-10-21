<?php
$badgeCls = $tier['cls'] . (in_array($tier['cls'], ['warning', 'info'], true) ? ' text-dark' : '');
$cid = (int)$c['id'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h3 m-0"><?= e($c['full_name']) ?> <span class="badge bg-<?= $badgeCls ?> fs-6"><?= e($tier['name']) ?></span></h1>
    <div class="text-muted"><i class="bi bi-telephone"></i> <?= e($c['phone']) ?> · khách từ <?= e(date('d/m/Y', strtotime($c['created_at']))) ?></div>
  </div>
  <div class="d-flex gap-2">
    <?php if (has_role('admin', 'manager')): ?><a class="btn btn-outline-secondary" href="<?= url('customers/edit/' . $cid) ?>">Sửa hồ sơ</a><?php endif; ?>
    <a class="btn btn-outline-dark" href="<?= url('customers') ?>"><i class="bi bi-arrow-left"></i> Danh sách</a>
  </div>
</div>

<?php if (!$on): ?><div class="alert alert-warning">Chưa bật tính năng điểm thưởng (chạy <code>migration_giai_doan_8c.sql</code>).</div><?php endif; ?>

<div class="row g-3 mb-3">
  <div class="col-6 col-xl-2"><div class="card border-warning"><div class="card-body"><div class="text-muted small">Điểm hiện có</div><div class="fs-4 fw-semibold"><?= number_format($sum['balance'], 0, ',', '.') ?></div></div></div></div>
  <div class="col-6 col-xl-2"><div class="card"><div class="card-body"><div class="text-muted small">Đã tích lũy</div><div class="fs-4 fw-semibold"><?= number_format($sum['earned'], 0, ',', '.') ?></div></div></div></div>
  <div class="col-6 col-xl-2"><div class="card"><div class="card-body"><div class="text-muted small">Lượt ghé</div><div class="fs-4 fw-semibold"><?= (int)$sum['visits'] ?></div></div></div></div>
  <div class="col-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Tổng chi tiêu</div><div class="fs-5 fw-semibold"><?= money($sum['spent']) ?></div></div></div></div>
  <div class="col-12 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Trung bình mỗi lượt</div><div class="fs-5 fw-semibold"><?= money($sum['avg']) ?></div></div></div></div>
</div>

<div class="mb-4 small text-muted">
  <?php if ($tier['next_name']): ?>Còn <strong><?= number_format($tier['next_need'], 0, ',', '.') ?></strong> điểm tích lũy nữa để lên hạng <strong><?= e($tier['next_name']) ?></strong>.
  <?php else: ?>Khách đã đạt hạng cao nhất.<?php endif; ?>
  Hạng hiện tại nhân điểm tích lũy với hệ số <strong><?= str_replace('.', ',', (string)$tier['mult']) ?></strong>.
  <?php if ($sum['last_visit']): ?> Lần ghé gần nhất: <?= e(date('H:i d/m/Y', strtotime($sum['last_visit']))) ?>.<?php endif; ?>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card mb-3"><div class="card-header fw-semibold">Món hay gọi</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($favorites as $f): ?>
          <li class="list-group-item d-flex justify-content-between"><span><?= e($f['name']) ?></span><span class="text-muted"><?= (int)$f['qty'] ?> phần</span></li>
        <?php endforeach; ?>
        <?php if (!$favorites): ?><li class="list-group-item text-muted">Chưa có lịch sử gọi món.</li><?php endif; ?>
      </ul></div>

    <div class="card"><div class="card-header fw-semibold">Lịch sử ghé quán (20 lần gần nhất)</div>
      <div class="table-responsive"><table class="table table-sm mb-0 align-middle">
        <tbody>
        <?php foreach ($orders as $o): ?>
          <tr><td class="small"><?= e(date('d/m/Y H:i', strtotime($o['closed_at']))) ?></td><td><?= e($o['branch_name']) ?></td>
            <td class="text-end"><?= money($o['total']) ?></td>
            <td class="text-end"><?php if ($isAdmin || (int)$o['branch_id'] === $myBranch): ?><a class="small" target="_blank" href="<?= url('orders/bill/' . (int)$o['id']) ?>">HD<?= str_pad((string)$o['id'], 6, '0', STR_PAD_LEFT) ?></a><?php endif; ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?><tr><td class="text-muted p-3">Chưa có đơn nào.</td></tr><?php endif; ?>
        </tbody></table></div></div>
  </div>

  <div class="col-lg-6">
    <?php if ($canAdjust && $on): ?>
    <div class="card card-body mb-3">
      <div class="fw-semibold mb-2">Điều chỉnh điểm</div>
      <form method="post" action="<?= url('customers/adjust/' . $cid) ?>" class="row g-2"><?= csrf_field() ?>
        <div class="col-4"><input class="form-control form-control-sm" type="number" name="points" placeholder="+50 hoặc -20" required></div>
        <div class="col-8"><input class="form-control form-control-sm" name="note" maxlength="200" placeholder="Lý do (bắt buộc), ví dụ: quà sinh nhật" required></div>
        <div class="col-12"><button class="btn btn-sm btn-outline-dark">Lưu điều chỉnh</button></div>
      </form>
    </div>
    <?php endif; ?>

    <div class="card"><div class="card-header fw-semibold">Sổ điểm (50 dòng gần nhất)</div>
      <div class="table-responsive"><table class="table table-sm mb-0 align-middle">
        <tbody>
        <?php foreach ($ledger as $l): $p = (int)$l['points']; ?>
          <tr><td class="small text-nowrap"><?= e(date('d/m/Y H:i', strtotime($l['created_at']))) ?></td>
            <td class="small"><?= e($l['note'] ?? '') ?><?= $l['user_name'] ? ' <span class="text-muted">(' . e($l['user_name']) . ')</span>' : '' ?></td>
            <td class="text-end fw-semibold <?= $p > 0 ? 'text-success' : 'text-danger' ?>"><?= ($p > 0 ? '+' : '') . $p ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$ledger): ?><tr><td class="text-muted p-3">Chưa có giao dịch điểm.</td></tr><?php endif; ?>
        </tbody></table></div></div>
  </div>
</div>
