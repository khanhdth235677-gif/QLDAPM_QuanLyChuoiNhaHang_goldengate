<?php $cid = (int)$conv['id']; $open = $conv['status'] === 'open'; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h4 m-0"><i class="bi bi-chat-dots"></i> <?= e($conv['guest_name'] ?: 'Khách') ?></h1>
    <div class="text-muted small"><?= e($conv['branch_name'] ?? '-') ?> · bắt đầu <?= e(date('H:i d/m/Y', strtotime($conv['created_at']))) ?></div>
  </div>
  <a class="btn btn-outline-dark" href="<?= url('chat') ?>"><i class="bi bi-arrow-left"></i> Danh sách</a>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="chat-thread" id="thread">
        <?php foreach ($msgs as $m): ?>
          <div class="d-flex mb-2 <?= $m['from'] === 'staff' ? 'justify-content-end' : '' ?>" data-id="<?= (int)$m['id'] ?>">
            <div class="bubble <?= e($m['from']) ?>">
              <div><?= e($m['text']) ?></div>
              <div class="small text-muted mt-1"><?= $m['from'] === 'staff' ? e($m['name'] ?? 'Nhân viên') . ' · ' : '' ?><?= e($m['time']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="card-footer">
        <div id="closedNote" class="text-muted small mb-0" <?= $open ? 'hidden' : '' ?>>Cuộc trò chuyện đã đóng. Bấm "Mở lại" bên phải nếu muốn tiếp tục.</div>
        <form id="replyForm" method="post" action="<?= url('chat/send/' . $cid) ?>" class="d-flex gap-2" <?= $open ? '' : 'hidden' ?>>
          <?= csrf_field() ?>
          <textarea class="form-control" id="replyText" name="message" rows="2" maxlength="1000" placeholder="Nhập câu trả lời... (Enter để gửi, Shift+Enter xuống dòng)" required></textarea>
          <button class="btn btn-warning" id="replyBtn">Gửi</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card card-body">
      <div class="mb-1"><span class="text-muted">Trạng thái:</span> <span id="statusBadge" class="badge bg-<?= $open ? 'success' : 'secondary' ?>"><?= $open ? 'Đang mở' : 'Đã đóng' ?></span></div>
      <div class="mb-3"><span class="text-muted">Phụ trách:</span> <span id="assignedName"><?= $conv['assigned_name'] ? e($conv['assigned_name']) : 'Chưa có' ?></span></div>
      <div class="d-grid gap-2">
        <?php if ((int)$conv['assigned_user_id'] !== $me): ?>
          <form method="post" action="<?= url('chat/assign/' . $cid) ?>"><?= csrf_field() ?><button class="btn btn-outline-primary w-100">Nhận phụ trách</button></form>
        <?php endif; ?>
        <?php if ($open): ?>
          <form method="post" action="<?= url('chat/close/' . $cid) ?>" onsubmit="return confirm('Đóng cuộc trò chuyện này?')"><?= csrf_field() ?><button class="btn btn-outline-danger w-100">Đóng cuộc chat</button></form>
        <?php else: ?>
          <form method="post" action="<?= url('chat/reopen/' . $cid) ?>"><?= csrf_field() ?><button class="btn btn-outline-success w-100">Mở lại</button></form>
        <?php endif; ?>
        <a class="btn btn-outline-dark" href="<?= url('reservations/create') ?><?= has_role('admin') && $conv['branch_id'] ? '?branch=' . (int)$conv['branch_id'] : '' ?>"><i class="bi bi-calendar-plus"></i> Tạo đặt bàn cho khách</a>
      </div>
      <p class="small text-muted mt-3 mb-0">Khách thấy tên bạn là "Nhà hàng". Người trả lời đầu tiên tự nhận phụ trách. Tin mới tự hiện, không cần tải lại trang.</p>
    </div>
  </div>
</div>

<script>
(function () {
  var cid = <?= $cid ?>, last = <?= (int)$last ?>, thread = document.getElementById('thread');
  var form = document.getElementById('replyForm'), text = document.getElementById('replyText'), btn = document.getElementById('replyBtn');
  var base = <?= json_js(url('chat')) ?>, csrf = <?= json_js(csrf_token()) ?>;

  function add(m) {
    if (m.id <= last) return;
    last = m.id;
    var row = document.createElement('div'); row.className = 'd-flex mb-2' + (m.from === 'staff' ? ' justify-content-end' : '');
    var b = document.createElement('div'); b.className = 'bubble ' + m.from;
    var t = document.createElement('div'); t.textContent = m.text;
    var meta = document.createElement('div'); meta.className = 'small text-muted mt-1';
    meta.textContent = (m.from === 'staff' ? (m.name || 'Nhân viên') + ' · ' : '') + m.time;
    b.appendChild(t); b.appendChild(meta); row.appendChild(b); thread.appendChild(row);
  }
  function toBottom() { thread.scrollTop = thread.scrollHeight; }
  function setStatus(open, assigned) {
    var sb = document.getElementById('statusBadge');
    sb.textContent = open ? 'Đang mở' : 'Đã đóng'; sb.className = 'badge bg-' + (open ? 'success' : 'secondary');
    form.hidden = !open; document.getElementById('closedNote').hidden = open;
    document.getElementById('assignedName').textContent = assigned || 'Chưa có';
  }

  function poll() {
    if (document.hidden) return;
    var nearBottom = thread.scrollHeight - thread.scrollTop - thread.clientHeight < 80;
    fetch(base + '/poll/' + cid + '?after=' + last, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) return;
        (j.messages || []).forEach(add);
        setStatus(j.status === 'open', j.assigned);
        if ((j.messages || []).length && nearBottom) toBottom();
      }).catch(function () {});
  }

  function send() {
    var v = text.value.trim();
    if (!v) return;
    btn.disabled = true;
    var fd = new FormData(); fd.append('csrf', csrf); fd.append('message', v);
    fetch(base + '/send/' + cid, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (j.ok) { text.value = ''; (j.messages || []).forEach(add); toBottom(); }
        else alert(j.error || 'Không gửi được.');
      }).catch(function () { alert('Lỗi kết nối, vui lòng thử lại.'); })
      .then(function () { btn.disabled = false; text.focus(); });
  }

  form.addEventListener('submit', function (e) { e.preventDefault(); send(); });
  text.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); } });
  toBottom();
  setInterval(poll, 3000);
})();
</script>
