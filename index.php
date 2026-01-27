<?php
session_start();
?>
<?php require_once __DIR__ . '/templates/header.php'; ?>

<main class="container">
    <h1>Портал клининговых услуг «Мой Не Сам»</h1>
    <p>Добро пожаловать. Пожалуйста, зарегистрируйтесь или войдите в систему, если вы этого ещё не сделали.</p>

    <nav class="nav">
        <a href="register.php">Регистрация</a> |
        <a href="login.php">Войти</a> |
        <a href="create_request.php">Создать заявку</a> |
        <a href="admin.php">Панель администратора</a>
    </nav>
</main>

<?php require_once __DIR__ . '/templates/footer.php'; ?>


