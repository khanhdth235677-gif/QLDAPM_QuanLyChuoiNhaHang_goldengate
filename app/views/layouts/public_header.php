<?php
$seg = strtolower(explode('/', trim($_GET['url'] ?? '', '/'))[1] ?? 'index');
$nav = ['index' => ['site', 'Trang chủ'], 'menu' => ['site/menu', 'Thực đơn'], 'book' => ['site/book', 'Đặt bàn'], 'track' => ['site/track', 'Tra cứu đặt bàn']];
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
<body class="site-body">
<nav class="navbar navbar-expand-md navbar-dark bg-dark sticky-top">
  <div class="container">
    <a class="navbar-brand" href="<?= url('site') ?>"><i class="bi bi-gem text-warning"></i> <?= e(APP_NAME) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="siteNav">
      <ul class="navbar-nav me-auto">
        <?php foreach ($nav as $k => [$path, $label]): ?>
          <li class="nav-item"><a class="nav-link <?= $seg === $k ? 'active' : '' ?>" href="<?= url($path) ?>"><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <a class="btn btn-warning btn-sm" href="<?= url('site/book') ?>"><i class="bi bi-calendar-check"></i> Đặt bàn ngay</a>
    </div>
  </div>
</nav>
<main>
<?php foreach (['success' => 'success', 'error' => 'danger'] as $fk => $fc): ?>
  <?php if ($fm = flash($fk)): ?><div class="container pt-3"><div class="alert alert-<?= $fc ?> mb-0"><?= e($fm) ?></div></div><?php endif; ?>
<?php endforeach; ?>
