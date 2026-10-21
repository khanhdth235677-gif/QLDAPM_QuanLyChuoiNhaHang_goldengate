<div class="container pt-4" style="max-width:760px">
  <h1 class="h3 mb-1">Đặt bàn</h1>
  <p class="text-muted">Điền thông tin bên dưới. Nhà hàng sẽ xác nhận lượt đặt của bạn. Đặt trước ít nhất <?= (int)BOOK_MIN_LEAD_MIN ?> phút, nhóm trên <?= (int)BOOK_MAX_GUESTS ?> khách vui lòng gọi điện.</p>

  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <?php if (!$branches): ?><div class="alert alert-warning">Hiện chưa nhận đặt bàn online.</div><?php else: ?>
  <form method="post" action="<?= url('site/book') ?>" class="card card-body" autocomplete="off">
    <?= csrf_field() ?>
    <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
    <div class="row g-3">
      <div class="col-12">
        <label class="form-label" for="branch_id">Chi nhánh</label>
        <select class="form-select" id="branch_id" name="branch_id" required>
          <?php foreach ($branches as $b): ?><option value="<?= (int)$b['id'] ?>" <?= (int)$form['branch_id'] === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?> - <?= e($b['address']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-5">
        <label class="form-label" for="date">Ngày đến</label>
        <input class="form-control" id="date" name="date" type="date" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+' . BOOK_MAX_DAYS . ' days')) ?>" value="<?= e($form['date']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="time">Giờ đến</label>
        <select class="form-select" id="time" name="time" required>
          <?php foreach ($slots as $s): ?><option value="<?= e($s) ?>" <?= $form['time'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="guests">Số khách</label>
        <input class="form-control" id="guests" name="guests" type="number" min="1" max="<?= (int)BOOK_MAX_GUESTS ?>" value="<?= (int)$form['guests'] ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="name">Họ tên</label>
        <input class="form-control" id="name" name="name" maxlength="100" value="<?= e($form['name']) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="phone">Số điện thoại</label>
        <input class="form-control" id="phone" name="phone" type="tel" inputmode="tel" maxlength="20" value="<?= e($form['phone']) ?>" placeholder="0901234567" required>
      </div>
      <div class="col-12">
        <label class="form-label" for="note">Ghi chú (không bắt buộc)</label>
        <input class="form-control" id="note" name="note" maxlength="255" value="<?= e($form['note']) ?>" placeholder="Sinh nhật, cần ghế trẻ em, gần cửa sổ...">
      </div>
    </div>
    <div class="form-check mt-3">
      <input class="form-check-input" type="checkbox" id="remember" checked>
      <label class="form-check-label small" for="remember">Ghi nhớ họ tên và số điện thoại trên thiết bị này để điền sẵn cho lần sau (chỉ lưu trong trình duyệt của bạn)</label>
    </div>
    <button class="btn btn-warning btn-lg mt-3">Gửi yêu cầu đặt bàn</button>
    <div class="form-text mt-2">Bạn sẽ nhận một mã đặt bàn. Dùng mã và số điện thoại này ở mục <a href="<?= url('site/track') ?>">Tra cứu đặt bàn</a> để xem trạng thái hoặc hủy.</div>
  </form>
  <?php endif; ?>
</div>
<script>
// Điền sẵn họ tên, số điện thoại đã lưu trên thiết bị này (không gửi đi đâu, chỉ nằm trong trình duyệt)
(function () {
  var name = document.getElementById('name'), phone = document.getElementById('phone'), rem = document.getElementById('remember');
  if (!name || !phone) return;
  try {
    var g = JSON.parse(localStorage.getItem('gg_guest') || 'null');
    if (g) { if (!name.value && g.name) name.value = g.name; if (!phone.value && g.phone) phone.value = g.phone; }
    name.form.addEventListener('submit', function () {
      if (rem.checked) localStorage.setItem('gg_guest', JSON.stringify({ name: name.value.trim(), phone: phone.value.trim() }));
      else localStorage.removeItem('gg_guest');
    });
  } catch (e) {}
})();
</script>
