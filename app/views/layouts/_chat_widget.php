<?php
require_once APP_PATH . '/models/Site.php';
$ggBranches = array_map(fn($b) => ['id' => (int)$b['id'], 'name' => $b['name']], Site::activeBranches());
?>
<div id="ggChat">
  <button type="button" id="ggChatBtn" class="gg-chat-btn" aria-label="Chat với nhà hàng">
    <i class="bi bi-chat-dots-fill"></i><span id="ggChatDot" class="gg-dot" hidden></span>
  </button>
  <div id="ggChatPanel" class="gg-chat-panel" hidden>
    <div class="gg-chat-head">
      <span class="fw-semibold"><i class="bi bi-chat-dots"></i> Chat với <?= e(APP_NAME) ?></span>
      <span>
        <button type="button" id="ggChatEnd" class="gg-link" title="Kết thúc cuộc trò chuyện" hidden>Kết thúc</button>
        <button type="button" id="ggChatClose" class="gg-link" aria-label="Thu nhỏ">&times;</button>
      </span>
    </div>

    <form id="ggChatStartForm" class="gg-chat-body" autocomplete="off">
      <p class="small text-muted mb-2">Nhà hàng sẽ trả lời bạn ngay tại đây. Vui lòng cho biết thông tin trước khi bắt đầu.</p>
      <input class="form-control form-control-sm mb-2" id="ggName" maxlength="50" placeholder="Tên của bạn" required>
      <select class="form-select form-select-sm mb-2" id="ggBranch" required>
        <option value="">-- Chọn chi nhánh --</option>
        <?php foreach ($ggBranches as $b): ?><option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
      </select>
      <textarea class="form-control form-control-sm mb-2" id="ggFirst" rows="3" maxlength="1000" placeholder="Bạn cần hỏi gì?" required></textarea>
      <div class="text-danger small mb-2" id="ggChatStartErr"></div>
      <button class="btn btn-warning btn-sm w-100">Bắt đầu chat</button>
    </form>

    <div id="ggChatThread" hidden>
      <div class="gg-msgs" id="ggChatMsgs"></div>
      <div id="ggChatClosed" class="gg-closed" hidden>
        Cuộc trò chuyện đã kết thúc.<br><button type="button" class="btn btn-sm btn-outline-dark mt-2" id="ggChatNew">Bắt đầu chat mới</button>
      </div>
      <form id="ggChatForm" class="gg-chat-form">
        <input class="form-control form-control-sm" id="ggChatInput" maxlength="1000" placeholder="Nhập tin nhắn..." autocomplete="off">
        <button class="btn btn-warning btn-sm">Gửi</button>
      </form>
    </div>
  </div>
</div>
<script>window.GG_CHAT = <?= json_js(['base' => rtrim(url(''), '/') . '/', 'csrf' => csrf_token(), 'branches' => $ggBranches]) ?>;</script>
<script src="<?= asset('js/chat-widget.js') ?>"></script>
