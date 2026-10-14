<?php $sub = strtolower(explode('/', trim($_GET['url'] ?? '', '/'))[1] ?? 'index'); ?>
<ul class="nav nav-pills mb-3">
  <li class="nav-item"><a class="nav-link <?= in_array($sub, ['index', 'move'], true) ? 'active' : '' ?>" href="<?= url('inventory') ?>">Tồn kho</a></li>
  <li class="nav-item"><a class="nav-link <?= $sub === 'history' ? 'active' : '' ?>" href="<?= url('inventory/history') ?>">Lịch sử nhập xuất</a></li>
  <li class="nav-item"><a class="nav-link <?= in_array($sub, ['ingredients'], true) ? 'active' : '' ?>" href="<?= url('inventory/ingredients') ?>">Danh mục nguyên liệu</a></li>
  <li class="nav-item"><a class="nav-link" href="<?= url('recipes') ?>">Công thức món</a></li>
</ul>
