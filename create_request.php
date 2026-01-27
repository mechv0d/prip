<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/templates/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Только для аутентифицированных пользователей
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// Покажем флеш-сообщение, если есть
$flash = $_SESSION['flash'] ?? '';
if (!empty($_SESSION['flash'])) {
    unset($_SESSION['flash']);
}

// Получим историю заявок текущего пользователя
$historyStmt = $pdo->prepare('
    SELECT r.id, r.datetime, r.adress, r.user_phone, s.name AS service_name, st.name AS status_name, o.name AS oplata_name
    FROM request r
    LEFT JOIN services s ON r.id_services = s.id
    LEFT JOIN status st ON r.id_status = st.id
    LEFT JOIN oplata o ON r.id_oplata = o.id
    WHERE r.id_user = :uid
    ORDER BY r.id DESC
    LIMIT 50
');
$historyStmt->execute([':uid' => $userId]);
$history = $historyStmt->fetchAll();
?>

<main class="wrap">
    <h2>Создать заявку</h2>

    <?php if ($flash): ?>
        <div class="success"><?= htmlspecialchars($flash, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
    <?php endif; ?>

    <p><a href="new_request.php" class="button">Перейти к форме создания заявки</a></p>

    <section class="requests-list">
        <h3>История заявок</h3>
        <?php if (empty($history)): ?>
            <p>У вас пока нет заявок.</p>
        <?php else: ?>
            <?php foreach ($history as $row): ?>
                <article class="request-item">
                    <div class="request-meta">
                        <strong><?= htmlspecialchars($row['service_name'] ?? '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
                        — <span class="muted"><?= htmlspecialchars($row['status_name'] ?? '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                    </div>
                    <div class="request-body">
                        <div><strong>Дата:</strong> <?= htmlspecialchars($row['datetime'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                        <div><strong>Адрес:</strong> <?= htmlspecialchars($row['adress'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                        <div><strong>Телефон:</strong> <?= htmlspecialchars($row['user_phone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                        <div><strong>Оплата:</strong> <?= htmlspecialchars($row['oplata_name'] ?? '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/templates/footer.php'; ?>


