<?php include APP_PATH . '/views/reports/_head.php'; ?>
<div class="d-flex justify-content-end mb-2">
  <a class="btn btn-sm btn-outline-success" href="<?= url('reports/export/usage') . '?' . e($qs) ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Xuất Excel (CSV)</a>
</div>

<?php if (!$recipesOk): ?>
  <div class="alert alert-warning">Chưa có bảng công thức nên cột "Dự kiến theo công thức" tạm bằng 0. Hãy kiểm tra cấu trúc database có bảng công thức món.</div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-hover align-middle bg-white">
  <thead class="table-light">
    <tr><th>Nguyên liệu</th><th>Đơn vị</th><th class="text-end">Dự kiến theo công thức</th><th class="text-end">Đã tự trừ khi bán</th>
        <th class="text-end">Xuất tay</th><th class="text-end">Kiểm kê điều chỉnh</th><th class="text-end">Hao hụt ngoài bán hàng</th><th class="text-end">Nhập kho</th></tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $r): $loss = $r['loss']; ?>
    <tr>
      <td class="fw-semibold"><?= e($r['name']) ?></td><td><?= e($r['unit']) ?></td>
      <td class="text-end"><?= qty_fmt($r['theory']) ?></td><td class="text-end"><?= qty_fmt($r['auto_out']) ?></td>
      <td class="text-end"><?= qty_fmt($r['manual_out']) ?></td>
      <td class="text-end <?= $r['adjust_net'] < 0 ? 'text-danger' : ($r['adjust_net'] > 0 ? 'text-success' : '') ?>"><?= ($r['adjust_net'] > 0 ? '+' : '') . qty_fmt($r['adjust_net']) ?></td>
      <td class="text-end fw-semibold <?= $loss > 0.004 ? 'text-danger' : '' ?>"><?= qty_fmt($loss) ?></td>
      <td class="text-end"><?= qty_fmt($r['qty_in']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">Không có phát sinh nguyên liệu nào trong kỳ này.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<div class="small text-muted">
  <strong>Dự kiến theo công thức</strong>: định lượng mỗi phần nhân số phần đã bán trong kỳ. <strong>Đã tự trừ khi bán</strong>: số hệ thống thực sự trừ khi thanh toán (có thể khác nếu công thức mới được thêm sau hoặc kho thiếu).
  <strong>Hao hụt ngoài bán hàng</strong> = xuất tay + phần kiểm kê thiếu (kiểm kê dư thì trừ bớt). Số dương, đỏ nghĩa là nguyên liệu mất đi ngoài những gì đã bán, đáng để kiểm tra (hỏng, đổ, thất thoát).
</div>
