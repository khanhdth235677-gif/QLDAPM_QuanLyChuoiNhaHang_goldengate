<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="h3 m-0">Chat khách hàng</h1>
  <form method="get" action="<?= url('chat') ?>" class="d-flex gap-2">
    <?php if ($isAdmin): ?>
      <select class="form-select w-auto" name="branch" onchange="this.form.submit()">
        <option value="0">Tất cả chi nhánh</option>
        <?php foreach ($branches as $b): ?><option value="<?= (int)$b['id'] ?>" <?= (int)$b['id'] === $branchId ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?>
      </select>
    <?php endif; ?>
    <select class="form-select w-auto" name="st" onchange="this.form.submit()">
      <option value="open"   <?= $status === 'open'   ? 'selected' : '' ?>>Đang mở</option>
      <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Đã đóng</option>
    </select>
  </form>
</div>

<div class="table-responsive">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light"><tr><th>Khách</th><?php if ($isAdmin): ?><th>Chi nhánh</th><?php endif; ?><th>Tin gần nhất</th><th>Phụ trách</th><th>Cách đây</th><th class="text-end"></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r):
    $idle = (int)$r['idle_min'];
    $idleTxt = $idle < 1 ? 'Vừa xong' : ($idle < 60 ? $idle . ' phút' : ($idle < 1440 ? intdiv($idle, 60) . ' giờ' : intdiv($idle, 1440) . ' ngày'));
    $waiting = $r['status'] === 'open' && $r['last_from'] === 'customer'; ?>
    <tr class="<?= (int)$r['unread'] > 0 ? 'table-warning' : '' ?>">
      <td class="fw-semibold"><?= e($r['guest_name'] ?: 'Khách') ?>
        <?php if ((int)$r['unread'] > 0): ?><span class="badge bg-danger"><?= (int)$r['unread'] ?> mới</span><?php endif; ?></td>
      <?php if ($isAdmin): ?><td><?= e($r['branch_name'] ?? '-') ?></td><?php endif; ?>
      <td class="small"><?= $r['last_from'] === 'staff' ? '<span class="text-muted">Bạn: </span>' : '' ?><?= e(mb_strimwidth((string)$r['last_msg'], 0, 70, '...')) ?></td>
      <td class="small"><?= $r['assigned_name'] ? e($r['assigned_name']) . ((int)$r['assigned_user_id'] === $me ? ' (bạn)' : '') : '<span class="text-muted">Chưa có</span>' ?></td>
      <td class="small <?= $waiting && $idle >= 5 ? 'text-danger fw-semibold' : 'text-muted' ?>"><?= $idleTxt ?></td>
      <td class="text-end"><a class="btn btn-sm btn-<?= $waiting ? 'warning' : 'outline-primary' ?>" href="<?= url('chat/show/' . (int)$r['id']) ?>"><?= $waiting ? 'Trả lời' : 'Mở' ?></a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4"><?= $status === 'open' ? 'Chưa có cuộc trò chuyện nào đang mở.' : 'Chưa có cuộc trò chuyện nào đã đóng.' ?></td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<p class="text-muted small">Khách nhắn từ khung chat trên trang công khai của nhà hàng. Trang này tự làm mới mỗi 10 giây.</p>
<script>setTimeout(function () { if (!document.hidden) location.reload(); }, 10000);</script>
