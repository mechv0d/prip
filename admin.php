<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/templates/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = !empty($_SESSION['user']) && intval($_SESSION['user']['id_role'] ?? 0) === 2;
$errors = [];
$message = '';

// Простая админ-аутентификация: допустимо использовать учетку из БД с ролью admin или спец. логин 'adminka'/'password'
if (isset($_POST['admin_login'], $_POST['admin_password']) && !$isAdmin) {
    $login = trim($_POST['admin_login']);
    $password = $_POST['admin_password'];

    if ($login === 'adminka' && $password === 'password') {
        // Временная сессия админа
        $_SESSION['user'] = [
            'id' => 0,
            'fio' => 'Администратор',
            'login' => 'adminka',
            'id_role' => 2
        ];
        $isAdmin = true;
    } else {
        // Попробуем найти пользователя в БД с ролью admin
        $stmt = $pdo->prepare('SELECT id, fio, login, password, id_role FROM user WHERE login = :login LIMIT 1');
        $stmt->execute([':login' => $login]);
        $user = $stmt->fetch();
        if ($user && intval($user['id_role']) === 2) {
            $stored = $user['password'];
            $ok = false;
            if (password_verify($password, $stored)) {
                $ok = true;
            } elseif ($password === $stored) {
                // устаревшая открытая строчка
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
                $isAdmin = true;
            } else {
                $errors[] = 'Неверный пароль';
            }
        } else {
            $errors[] = 'Пользователь не найден или не админ';
        }
    }
}

// Действие по смене статуса заявки
if ($isAdmin && isset($_POST['action']) && $_POST['action'] === 'change_status') {
    $reqId = intval($_POST['request_id'] ?? 0);
    $newStatus = intval($_POST['id_status'] ?? 0);
    $adminComment = trim($_POST['admin_comment'] ?? '');

    if ($reqId <= 0 || $newStatus <= 0) {
        $errors[] = 'Неверные параметры для обновления';
    } else {
        // Убедимся, что в таблице request есть колонка admin_comment; если нет — попытаемся добавить
        try {
            $colCheck = $pdo->prepare("SHOW COLUMNS FROM `request` LIKE 'admin_comment'");
            $colCheck->execute();
            if ($colCheck->rowCount() === 0) {
                $pdo->exec("ALTER TABLE `request` ADD COLUMN `admin_comment` varchar(255) DEFAULT NULL");
            }
        } catch (Exception $e) {
            // если не удалось изменить схему — продолжим без сохранения комментария
        }

        // Обновляем статус и комментарий
        $updateSql = "UPDATE request SET id_status = :st";
        if ($adminComment !== '') {
            $updateSql .= ", admin_comment = :ac";
        }
        $updateSql .= " WHERE id = :id";
        $upd = $pdo->prepare($updateSql);
        $params = [':st' => $newStatus, ':id' => $reqId];
        if ($adminComment !== '') { $params[':ac'] = $adminComment; }
        $upd->execute($params);
        $message = 'Статус заявки обновлён.';
    }
}

if (!$isAdmin) {
    // Форма логина админа
    ?>
    <main class="wrap">
        <h2>Вход в панель администратора</h2>
        <?php if (!empty($errors)): ?>
            <div class="errors"><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" class="form admin-login">
            <label>Логин<br><input type="text" name="admin_login" value=""></label>
            <label>Пароль<br><input type="password" name="admin_password" value=""></label>
            <button type="submit">Войти</button>
        </form>
    </main>
    <?php
    require_once __DIR__ . '/templates/footer.php';
    exit;
}

// Админ: убедимся, что колонка admin_comment существует, затем показываем список всех заявок
try {
    $colCheck = $pdo->prepare("SHOW COLUMNS FROM `request` LIKE 'admin_comment'");
    $colCheck->execute();
    if ($colCheck->rowCount() === 0) {
        // Попробуем добавить колонку (возможно, база в read-only — тогда пропустим)
        $pdo->exec("ALTER TABLE `request` ADD COLUMN `admin_comment` varchar(255) DEFAULT NULL");
    }
} catch (Exception $e) {
    // Не критично — если не получилось добавить колонку, запрос ниже уберёт ссылку на неё
}

$stmt = $pdo->query('
    SELECT r.id, r.datetime, r.adress, r.user_phone, r.admin_comment,
           s.name AS service_name, st.name AS status_name, o.name AS oplata_name,
           u.fio AS user_fio, u.email AS user_email
    FROM request r
    LEFT JOIN services s ON r.id_services = s.id
    LEFT JOIN status st ON r.id_status = st.id
    LEFT JOIN oplata o ON r.id_oplata = o.id
    LEFT JOIN user u ON r.id_user = u.id
    ORDER BY r.id DESC
');
$allRequests = $stmt->fetchAll();
$statuses = $pdo->query('SELECT id, name, code FROM status ORDER BY id')->fetchAll();
?>

<main class="wrap admin-panel">
    <h2>Панель администратора</h2>
    <?php if ($message): ?><div class="success"><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div><?php endif; ?>
    <p>Список всех заявок. Изменяйте статус и при отмене указывайте причину.</p>

    <?php if (empty($allRequests)): ?>
        <p>Заявок пока нет.</p>
    <?php else: ?>
        <?php foreach ($allRequests as $r): ?>
            <article class="request-item admin-request">
                <div class="request-meta">
                    <strong>#<?= $r['id'] ?> — <?= htmlspecialchars($r['service_name'] ?? '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
                    <span class="muted"> / <?= htmlspecialchars($r['status_name'] ?? '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                </div>
                <div class="request-body">
                    <div><strong>Клиент:</strong> <?= htmlspecialchars($r['user_fio'] ?? 'Гость', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> (<?= htmlspecialchars($r['user_email'] ?? '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>)</div>
                    <div><strong>Дата:</strong> <?= htmlspecialchars($r['datetime'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                    <div><strong>Адрес:</strong> <?= htmlspecialchars($r['adress'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                    <div><strong>Телефон:</strong> <?= htmlspecialchars($r['user_phone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                    <div><strong>Оплата:</strong> <?= htmlspecialchars($r['oplata_name'] ?? '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                    <?php if (!empty($r['admin_comment'])): ?><div><strong>Комментарий:</strong> <?= htmlspecialchars($r['admin_comment'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div><?php endif; ?>
                </div>

                <form method="post" class="form status-form">
                    <input type="hidden" name="action" value="change_status">
                    <input type="hidden" name="request_id" value="<?= intval($r['id']) ?>">
                    <label>Статус<br>
                        <select name="id_status">
                            <?php foreach ($statuses as $st): ?>
                                <option value="<?= $st['id'] ?>" <?= (intval($r['id_status'] ?? 0) === intval($st['id'])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($st['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Причина (при отмене)<br>
                        <input type="text" name="admin_comment" value="<?= htmlspecialchars($r['admin_comment'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                    </label>
                    <button type="submit">Обновить</button>
                </form>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/templates/footer.php'; ?>


