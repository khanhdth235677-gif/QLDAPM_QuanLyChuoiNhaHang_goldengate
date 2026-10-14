<?php
$u = auth_user();
$current = current_segment();
if ($current === 'recipes') $current = 'menu';   // công thức nằm trong mục Thực đơn
// [key, nhãn, icon, đã làm xong?, vai trò được thấy]
$menu = [
    ['dashboard', 'Tổng quan',           'bi-speedometer2', true,  ['admin','manager','cashier','waiter','kitchen']],
    ['floor',     'Sơ đồ bàn',           'bi-diagram-3',    true,  ['admin','manager','cashier','waiter']],
    ['reservations','Đặt bàn',           'bi-calendar-check',true, ['admin','manager','cashier','waiter']],
    ['users',     'Tài khoản',           'bi-person-gear',  true,  ['admin','manager']],
    ['branches',  'Chi nhánh',           'bi-shop',         true,  ['admin']],
    ['menu',      'Thực đơn',            'bi-journal-text', true,  ['admin','manager']],
    ['tables',    'Quản lý bàn',       'bi-grid-3x3-gap', true,  ['admin','manager','cashier','waiter']],
    ['orders',    'Đơn đang mở','bi-receipt',      true,  ['admin','manager','cashier','waiter']],
    ['history',   'Lịch sử đơn',         'bi-clock-history',true,  ['admin','manager','cashier']],
    ['kitchen',   'Màn hình bếp',        'bi-fire',         true,  ['admin','manager','kitchen']],
    ['inventory', 'Kho nguyên liệu',     'bi-box-seam',     true,  ['admin','manager']],
    ['employees', 'Nhân viên',           'bi-people',       true,  ['admin','manager']],
    ['reports',   'Báo cáo',             'bi-graph-up',     false, ['admin','manager']],
    ['chat',      'Chat khách hàng',     'bi-chat-dots',    false, ['admin','manager','cashier','waiter']],
];
?><!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(($title ?? '') !== '' ? $title . ' - ' . APP_NAME : APP_NAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark sticky-top">
  <div class="container-fluid">
    <button class="btn btn-outline-light d-md-none me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="bi bi-list"></i></button>
    <a class="navbar-brand me-auto" href="<?= url('dashboard') ?>"><i class="bi bi-gem text-warning"></i> <?= e(APP_NAME) ?></a>
    <div class="dropdown">
      <button class="btn btn-sm btn-outline-light dropdown-toggle" data-bs-toggle="dropdown">
        <i class="bi bi-person-circle"></i> <?= e($u['full_name']) ?>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><span class="dropdown-item-text small text-muted"><?= e($u['role_name']) ?><?= $u['branch_name'] ? ' · ' . e($u['branch_name']) : '' ?></span></li>
        <li><a class="dropdown-item" href="<?= url('account/password') ?>"><i class="bi bi-key"></i> Đổi mật khẩu</a></li>
        <li><hr class="dropdown-divider"></li>
        <li>
          <form method="post" action="<?= url('auth/logout') ?>" class="m-0">
            <?= csrf_field() ?>
            <button class="dropdown-item"><i class="bi bi-box-arrow-right"></i> Đăng xuất</button>
          </form>
        </li>
      </ul>
    </div>
  </div>
</nav>
<div class="container-fluid">
  <div class="row">
    <div class="offcanvas-md offcanvas-start col-md-3 col-lg-2 bg-light border-end p-0 sidebar" id="sidebar">
      <div class="list-group list-group-flush w-100 pt-2">
        <?php foreach ($menu as [$key, $label, $icon, $ready, $roles]): ?>
          <?php if (!has_role(...$roles)) continue; ?>
          <?php if ($ready): ?>
            <a href="<?= url($key) ?>" class="list-group-item list-group-item-action <?= $current === $key ? 'active' : '' ?>">
              <i class="bi <?= $icon ?>"></i> <?= e($label) ?></a>
          <?php else: ?>
            <span class="list-group-item text-muted disabled">
              <i class="bi <?= $icon ?>"></i> <?= e($label) ?> <span class="badge bg-secondary float-end">sắp có</span></span>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
    <main class="col-md-9 col-lg-10 py-4 px-md-4">
      <?php foreach (['success' => 'success', 'error' => 'danger'] as $k => $cls): ?>
        <?php if ($m = flash($k)): ?><div class="alert alert-<?= $cls ?> alert-dismissible fade show"><?= e($m) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
      <?php endforeach; ?>
