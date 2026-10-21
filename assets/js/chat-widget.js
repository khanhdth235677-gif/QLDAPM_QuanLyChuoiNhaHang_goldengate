// Khung chat nổi cho khách. Nói chuyện với /livechat/* bằng JSON, tự hỏi tin mới mỗi vài giây.
(function () {
  var cfg = window.GG_CHAT;
  if (!cfg) return;
  var el = function (id) { return document.getElementById(id); };
  var btn = el('ggChatBtn'), panel = el('ggChatPanel'), dot = el('ggChatDot');
  var startForm = el('ggChatStartForm'), startErr = el('ggChatStartErr');
  var thread = el('ggChatThread'), msgs = el('ggChatMsgs'), closedBox = el('ggChatClosed');
  var form = el('ggChatForm'), input = el('ggChatInput'), endBtn = el('ggChatEnd');
  var s = { active: false, status: null, lastId: 0, open: false, fresh: false, timer: null };

  function api(path, data, method) {
    method = method || 'POST';
    var opt = { method: method, credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } };
    if (method === 'POST') {
      var fd = new FormData();
      fd.append('csrf', cfg.csrf);
      for (var k in (data || {})) fd.append(k, data[k]);
      opt.body = fd;
    }
    return fetch(cfg.base + path, opt).then(function (r) {
      return r.json().catch(function () { return { ok: false, error: 'Lỗi kết nối, vui lòng tải lại trang.' }; });
    }).catch(function () { return { ok: false, error: 'Không kết nối được, vui lòng thử lại.' }; });
  }

  function addMsg(m) {
    if (m.id <= s.lastId) return false;
    s.lastId = m.id;
    var row = document.createElement('div'); row.className = 'gg-row ' + m.from;
    var b = document.createElement('div'); b.className = 'gg-bubble';
    b.textContent = m.text;                      // textContent: không bao giờ chạy mã từ tin nhắn
    var t = document.createElement('div'); t.className = 'gg-time'; t.textContent = m.time;
    b.appendChild(t); row.appendChild(b); msgs.appendChild(row);
    return true;
  }
  function toBottom() { msgs.scrollTop = msgs.scrollHeight; }

  function render() {
    startForm.hidden = s.active;
    thread.hidden = !s.active;
    var closed = s.active && s.status === 'closed';
    closedBox.hidden = !closed;
    form.hidden = closed;
    endBtn.hidden = !(s.active && !closed);
  }

  function schedule() {
    clearTimeout(s.timer);
    var wait = s.open ? 3000 : (s.active && s.status === 'open' ? 12000 : 0);
    if (wait) s.timer = setTimeout(poll, wait);
  }

  function poll() {
    if (document.hidden) { schedule(); return; }
    api('livechat/poll?after=' + s.lastId + (s.open ? '&open=1' : ''), null, 'GET').then(function (j) {
      if (s.fresh) { schedule(); return; }       // khách vừa bấm "chat mới", bỏ qua cuộc cũ
      if (j && j.active) {
        s.active = true; s.status = j.status;
        var near = msgs.scrollHeight - msgs.scrollTop - msgs.clientHeight < 60;
        var got = false;
        (j.messages || []).forEach(function (m) { if (addMsg(m)) got = true; });
        if (got && (near || s.open)) toBottom();
        if (!s.open && j.unread > 0) { dot.textContent = j.unread > 9 ? '9+' : j.unread; dot.hidden = false; } else dot.hidden = true;
      } else if (j && j.active === false) {
        s.active = false;
      }
      render();
      schedule();
    });
  }

  function openPanel() {
    s.open = true; panel.hidden = false; btn.hidden = true; dot.hidden = true;
    render(); poll();
    setTimeout(function () { (s.active ? input : el('ggName')).focus(); }, 50);
  }
  function closePanel() { s.open = false; panel.hidden = true; btn.hidden = false; schedule(); }

  btn.addEventListener('click', openPanel);
  el('ggChatClose').addEventListener('click', closePanel);

  startForm.addEventListener('submit', function (e) {
    e.preventDefault();
    startErr.textContent = '';
    var b = startForm.querySelector('button'); b.disabled = true;
    api('livechat/start', { name: el('ggName').value, branch_id: el('ggBranch').value, message: el('ggFirst').value }).then(function (j) {
      b.disabled = false;
      if (!j.ok) { startErr.textContent = j.error || 'Không bắt đầu được cuộc trò chuyện.'; return; }
      try {                                       // nhớ tên cho lần chat sau (cùng nơi lưu với form đặt bàn)
        var g = JSON.parse(localStorage.getItem('gg_guest') || 'null') || {};
        g.name = el('ggName').value.trim();
        localStorage.setItem('gg_guest', JSON.stringify(g));
      } catch (e2) {}
      s.fresh = false; s.active = true; s.status = 'open'; s.lastId = 0; msgs.innerHTML = '';
      (j.messages || []).forEach(addMsg);
      el('ggFirst').value = '';
      render(); toBottom(); schedule(); input.focus();
    });
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var v = input.value.trim();
    if (!v) return;
    var b = form.querySelector('button'); b.disabled = true;
    api('livechat/send', { message: v }).then(function (j) {
      b.disabled = false;
      if (j.ok) { input.value = ''; (j.messages || []).forEach(addMsg); toBottom(); input.focus(); }
      else if (j.closed) { s.status = 'closed'; render(); }
      else { alert(j.error || 'Không gửi được.'); }
    });
  });

  endBtn.addEventListener('click', function () {
    if (!confirm('Kết thúc cuộc trò chuyện này?')) return;
    api('livechat/close', {}).then(function () { s.status = 'closed'; render(); });
  });
  el('ggChatNew').addEventListener('click', function () {
    s.fresh = true; s.active = false; s.lastId = 0; msgs.innerHTML = ''; render(); el('ggFirst').focus();
  });

  try {                                           // điền sẵn tên đã lưu trên thiết bị này
    var saved = JSON.parse(localStorage.getItem('gg_guest') || 'null');
    if (saved && saved.name) el('ggName').value = saved.name;
  } catch (e3) {}

  poll();   // lần đầu: xem khách có cuộc trò chuyện đang dở không
})();
