    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<script>
(function () {
  var b = document.getElementById('chatBadge');
  if (!b) return;
  setInterval(function () {
    if (document.hidden) return;
    fetch(<?= json_js(url('chat/unread')) ?>, { credentials: 'same-origin' }).then(function (r) { return r.json(); })
      .then(function (d) { b.textContent = d.unread; b.style.display = d.unread > 0 ? '' : 'none'; }).catch(function () {});
  }, 15000);
})();
</script>
</body>
</html>
