<?php
require_once __DIR__ . '/includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user already logged in, redirect by role (admin id = 2)
if (!empty($_SESSION['user'])) {
    if (intval($_SESSION['user']['id_role'] ?? 0) === 2) {
        header('Location: admin.php');
    } else {
        header('Location: create_request.php');
    }
    exit;
}

require_once __DIR__ . '/templates/header.php';

$errors = [];
$loginValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginValue = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($loginValue === '' || $password === '') {
        $errors[] = 'Введите логин и пароль';
    } else {
        $stmt = $pdo->prepare('SELECT id, fio, login, password, id_role FROM user WHERE login = :login LIMIT 1');
        $stmt->execute([':login' => $loginValue]);
        $user = $stmt->fetch();
        if (!$user) {
            $errors[] = 'Пользователь не найден';
        } else {
            $stored = $user['password'];
            $ok = false;
            if (password_verify($password, $stored)) {
                $ok = true;
            } elseif ($password === $stored) {
                // Устаревшая хранимая пароля в открытом виде — позволим вход и обновим хеш
                $ok = true;
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare('UPDATE user SET password = :ph WHERE id = :id');
                $upd->execute([':ph' => $newHash, ':id' => $user['id']]);
            }

            if ($ok) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'fio' => $user['fio'],
                    'login' => $user['login'],
                    'id_role' => $user['id_role']
                ];
                // Redirect based on role: admin -> admin panel, others -> create request
                if (intval($user['id_role']) === 2) {
                    header('Location: admin.php');
                } else {
                    header('Location: create_request.php');
                }
                exit;
            } else {
                $errors[] = 'Неверный пароль';
            }
        }
    }
}
?>

<main class="wrap">
    <h2>Вход</h2>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="index.php" class="form">
        <label>Логин<br>
            <input type="text" name="login" value="<?= htmlspecialchars($loginValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </label>
        <label>Пароль<br>
            <input type="password" name="password">
        </label>
        <button type="submit">Войти</button>
    </form>
</main>

<?php require_once __DIR__ . '/templates/footer.php'; ?>