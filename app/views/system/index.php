<?php
$cls = ['ok' => ['success', 'Đạt'], 'warn' => ['warning', 'Cần lưu ý'], 'fail' => ['danger', 'Cần sửa']];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="h3 m-0"><i class="bi bi-shield-check"></i> Kiểm tra hệ thống</h1>
  <a class="btn btn-outline-dark" href="<?= url('system') ?>"><i class="bi bi-arrow-repeat"></i> Kiểm tra lại</a>
</div>

<div class="row g-3 mb-4">
  <div class="col-sm-4"><div class="card border-success"><div class="card-body"><div class="text-muted">Đạt</div><div class="fs-3 fw-semibold text-success"><?= (int)$count['ok'] ?></div></div></div></div>
  <div class="col-sm-4"><div class="card border-warning"><div class="card-body"><div class="text-muted">Cần lưu ý</div><div class="fs-3 fw-semibold"><?= (int)$count['warn'] ?></div></div></div></div>
  <div class="col-sm-4"><div class="card border-danger"><div class="card-body"><div class="text-muted">Cần sửa</div><div class="fs-3 fw-semibold text-danger"><?= (int)$count['fail'] ?></div></div></div></div>
</div>

<?php if ($count['fail'] === 0 && $count['warn'] === 0): ?>
  <div class="alert alert-success">Mọi kiểm tra đều đạt. Hệ thống sẵn sàng để bàn giao.</div>
<?php elseif ($count['fail'] === 0): ?>
  <div class="alert alert-warning">Không có lỗi nghiêm trọng. Các mục "Cần lưu ý" chấp nhận được khi thử nghiệm nhưng nên xử lý trước khi chạy thật.</div>
<?php else: ?>
  <div class="alert alert-danger">Có mục cần sửa trước khi dùng thật. Xem cột "Cách xử lý" ở các dòng màu đỏ.</div>
<?php endif; ?>

<?php foreach ($groups as $g): ?>
  <h2 class="h5 mt-4 mb-2"><?= e($g['title']) ?></h2>
  <div class="table-responsive">
  <table class="table align-middle bg-white">
    <thead class="table-light"><tr><th style="width:130px">Kết quả</th><th style="width:240px">Hạng mục</th><th>Chi tiết</th><th>Cách xử lý</th></tr></thead>
    <tbody>
    <?php foreach ($g['items'] as $i): [$bg, $label] = $cls[$i['status']]; ?>
      <tr class="<?= $i['status'] === 'fail' ? 'table-danger' : ($i['status'] === 'warn' ? 'table-warning' : '') ?>">
        <td><span class="badge bg-<?= $bg ?> <?= $bg === 'warning' ? 'text-dark' : '' ?>"><?= $label ?></span></td>
        <td class="fw-semibold"><?= e($i['name']) ?></td>
        <td class="small"><?= e($i['detail']) ?></td>
        <td class="small"><?= e($i['fix']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php endforeach; ?>
<p class="text-muted small">Trang này chỉ đọc, không thay đổi dữ liệu. Nó không kiểm tra được chuyện nằm ngoài ứng dụng như đã đặt lịch sao lưu chưa, hay trình duyệt có truy cập được thư mục config không. Hai việc đó có trong tài liệu "Kịch bản nghiệm thu".</p>
