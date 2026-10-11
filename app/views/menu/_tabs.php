<?php $sub = strtolower(explode('/', trim($_GET['url'] ?? '', '/'))[1] ?? 'index'); ?>
<ul class="nav nav-pills mb-3">
  <li class="nav-item"><a class="nav-link <?= in_array($sub, ['index', 'create', 'edit'], true) ? 'active' : '' ?>" href="<?= url('menu') ?>">Món ăn</a></li>
  <?php if (has_role('admin')): ?>
    <li class="nav-item"><a class="nav-link <?= $sub === 'categories' ? 'active' : '' ?>" href="<?= url('menu/categories') ?>">Danh mục</a></li>
  <?php endif; ?>
  <li class="nav-item"><a class="nav-link <?= $sub === 'branch' ? 'active' : '' ?>" href="<?= url('menu/branch') ?>">Giá theo chi nhánh</a></li>
</ul>
