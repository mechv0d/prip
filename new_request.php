<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/templates/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$errors = [];

$servicesStmt = $pdo->query('SELECT id, name FROM services ORDER BY id');
$services = $servicesStmt->fetchAll();
$oplataStmt = $pdo->query('SELECT id, name FROM oplata ORDER BY id');
$oplata = $oplataStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adress = trim($_POST['adress'] ?? '');
    $user_phone = trim($_POST['user_phone'] ?? '');
    $datetime_raw = trim($_POST['datetime'] ?? '');
    $id_services = intval($_POST['id_services'] ?? 0);
    $id_oplata = intval($_POST['id_oplata'] ?? 0);

    if ($adress === '') {
        $errors[] = 'Укажите адрес';
    }
    if ($user_phone === '') {
        $errors[] = 'Укажите контактный телефон';
    }
    if ($datetime_raw === '') {
        $errors[] = 'Укажите дату и время';
    } else {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $datetime_raw);
        if (!$dt) {
            $errors[] = 'Неверный формат даты/времени';
        } else {
            $datetime = $dt->format('Y-m-d H:i:s');
        }
    }
    if ($id_services <= 0) {
        $errors[] = 'Выберите услугу';
    }
    if ($id_oplata <= 0) {
        $errors[] = 'Выберите тип оплаты';
    }

    if (empty($errors)) {
        $insert = $pdo->prepare('INSERT INTO request (id_user, id_services, id_status, id_oplata, datetime, adress, user_phone) VALUES (:id_user, :id_services, :id_status, :id_oplata, :datetime, :adress, :user_phone)');
        $insert->execute([
            ':id_user' => $userId,
            ':id_services' => $id_services,
        ':id_status' => 1,
            ':id_oplata' => $id_oplata,
            ':datetime' => $datetime,
            ':adress' => $adress,
            ':user_phone' => $user_phone
        ]);
        $_SESSION['flash'] = 'Заявка успешно создана.';
        header('Location: create_request.php');
        exit;
    }
}
?>

<main class="wrap">
    <h2>Формирование заявки</h2>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="new_request.php" class="form request-form">
        <label>Адрес (полный)<br>
            <input type="text" name="adress" value="<?= htmlspecialchars($_POST['adress'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </label>

        <label>Контактный телефон<br>
            <input type="text" name="user_phone" value="<?= htmlspecialchars($_POST['user_phone'] ?? $_SESSION['user']['phone'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </label>

        <label>Желаемая дата и время<br>
            <input type="datetime-local" name="datetime" value="<?= htmlspecialchars($_POST['datetime'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </label>

        <label>Услуга<br>
            <select name="id_services">
                <option value="0">— выберите услугу —</option>
                <?php foreach ($services as $svc): ?>
                    <option value="<?= $svc['id'] ?>" <?= (isset($_POST['id_services']) && intval($_POST['id_services']) === intval($svc['id'])) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($svc['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Тип оплаты<br>
            <select name="id_oplata">
                <option value="0">— выберите тип оплаты —</option>
                <?php foreach ($oplata as $op): ?>
                    <option value="<?= $op['id'] ?>" <?= (isset($_POST['id_oplata']) && intval($_POST['id_oplata']) === intval($op['id'])) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($op['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <button type="submit">Отправить заявку</button>
    </form>
</main>

<?php require_once __DIR__ . '/templates/footer.php'; ?>



