<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Мой Не Сам</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="wrap">
            <a class="logo" href="/">Мой Не Сам</a>
        </div>
    </header>
    <nav class="site-nav">
        <div class="wrap">
            <?php if (!empty($_SESSION['user'])): ?>
                <span class="nav-greeting">Привет, <?= htmlspecialchars($_SESSION['user']['fio'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                <a class="nav-link" href="logout.php">Выход</a>
            <?php else: ?>
                <a class="nav-link" href="register.php">Регистрация</a>
                <a class="nav-link" href="index.php">Вход</a>
            <?php endif; ?>
        </div>
    </nav>

