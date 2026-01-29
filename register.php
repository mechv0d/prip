<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/templates/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];
$values = [
    'fio' => '',
    'login' => '',
    'email' => '',
    'phone' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['fio'] = trim($_POST['fio'] ?? '');
    $values['login'] = trim($_POST['login'] ?? '');
    $values['email'] = trim($_POST['email'] ?? '');
    $values['phone'] = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($values['fio'] === '') {
        $errors[] = 'Введите ФИО';
    }
    if ($values['login'] === '') {
        $errors[] = 'Введите логин';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный email';
    }
    if ($values['phone'] === '') {
        $errors[] = 'Введите телефон';
    }
    if ($password === '') {
        $errors[] = 'Введите пароль';
    }
    if ($password !== $password_confirm) {
        $errors[] = 'Пароли не совпадают';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM user WHERE login = :login LIMIT 1');
        $stmt->execute([':login' => $values['login']]);
        if ($stmt->fetch()) {
            $errors[] = 'Пользователь с таким логином уже существует';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO user (fio, login, password, email, phone, id_role) VALUES (:fio, :login, :password, :email, :phone, 1)');
            $insert->execute([
                ':fio' => $values['fio'],
                ':login' => $values['login'],
                ':password' => $hash,
                ':email' => $values['email'],
                ':phone' => $values['phone']
            ]);
            $userId = $pdo->lastInsertId();
            $_SESSION['user'] = [
                'id' => $userId,
                'fio' => $values['fio'],
                'login' => $values['login'],
                'id_role' => 1
            ];
            header('Location: create_request.php');
            exit;
        }
    }
}
?>

<main class="wrap">
    <h2>Регистрация</h2>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="register.php" class="form">
        <label>ФИО<br>
            <input type="text" name="fio" value="<?= htmlspecialchars($values['fio'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </label>
        <label>Логин<br>
            <input type="text" name="login" value="<?= htmlspecialchars($values['login'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </label>
        <label>Email<br>
            <input type="email" name="email" value="<?= htmlspecialchars($values['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </label>
        <label>Телефон<br>
            <input type="text" name="phone" value="<?= htmlspecialchars($values['phone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </label>
        <label>Пароль<br>
            <input type="password" name="password">
        </label>
        <label>Повтор пароля<br>
            <input type="password" name="password_confirm">
        </label>
        <button type="submit">Зарегистрироваться</button>
    </form>
</main>

<?php require_once __DIR__ . '/templates/footer.php'; ?>