<?php
// Сторінка адміністратора: вхід, перегляд, експорт і видалення відповідей.
require_once 'functions.php';

$login_error = '';

// Вихід
if (isset($_GET['logout'])) {
    admin_logout();
    header('Location: admin.php');
    exit;
}

// Спроба входу
if (isset($_POST['login'])) {
    if (!admin_login($_POST['login'], $_POST['password'] ?? '')) {
        $login_error = 'Невірний логін або пароль';
    }
}

// Дії, доступні лише адміністратору
if (is_admin()) {
    if (isset($_GET['export'])) {
        export_csv();
    }
    if (isset($_POST['delete_id'])) {
        delete_answer((int)$_POST['delete_id']);
        header('Location: admin.php');
        exit;
    }
}

$questions = get_questions();
$page_title = 'Адмін-панель';
include 'templates/header.php';
?>

        <section class="page-header">
            <h1>Адмін-панель</h1>
            <p>Відповіді всіх користувачів на анкету</p>
        </section>

<?php if (!is_admin()): ?>

        <form method="POST" action="admin.php" class="card survey-form login-form">
            <h2>Вхід для адміністратора</h2>
            <?php if ($login_error): ?>
                <p class="error-text"><?= e($login_error) ?></p>
            <?php endif; ?>

            <label for="login">Логін:</label>
            <input type="text" id="login" name="login" required>

            <label for="password">Пароль:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="btn">Увійти</button>
        </form>

<?php else: ?>
    <?php $answers = get_all_answers(); ?>

        <section class="card">
            <div class="admin-bar">
                <span>Всього відповідей: <strong><?= count($answers) ?></strong></span>
                <span>
                    <a href="admin.php?export=1" class="btn">Експорт у CSV</a>
                    <a href="admin.php?logout=1" class="btn btn-gray">Вийти</a>
                </span>
            </div>
        </section>

    <?php if (empty($answers)): ?>
        <section class="card"><p>Поки що відповідей немає.</p></section>
    <?php endif; ?>

    <?php foreach ($answers as $row): ?>
        <section class="card">
            <h2><?= e($row['name']) ?> <small>(<?= e($row['email']) ?>)</small></h2>
            <p class="muted">Дата: <?= e($row['date']) ?> · файл: <?= e($row['filename']) ?></p>

            <table class="styled-table">
                <?php foreach ($questions as $key => $q): ?>
                    <tr>
                        <th><?= e($q['text']) ?></th>
                        <td><?= e($row[$key] !== '' ? $row[$key] : '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <form method="POST" action="admin.php" onsubmit="return confirm('Видалити цю відповідь?');">
                <input type="hidden" name="delete_id" value="<?= (int)$row['id'] ?>">
                <button type="submit" class="btn btn-red">Видалити</button>
            </form>
        </section>
    <?php endforeach; ?>

<?php endif; ?>

<?php include 'templates/footer.php'; ?>
