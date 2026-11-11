<?php
// Сторінка анкети. Уся логіка лежить у functions.php, тут лише HTML і виклики функцій.
require_once 'functions.php';

$result = handle_survey();
$questions = get_questions();
$old = $result['data']; // що користувач уже ввів (якщо були помилки)

$page_title = 'Опитування: ШІ у навчанні';
include 'templates/header.php';
?>

        <section class="page-header">
            <h1>Опитування: ШІ у навчанні</h1>
            <p>Розкажіть, як ви користуєтесь штучним інтелектом під час навчання.</p>
        </section>

<?php if ($result['sent']): ?>

        <section class="card success">
            <h2>Дякуємо, вашу відповідь збережено!</h2>
            <p>Дата і час заповнення форми: <strong><?= e($old['date']) ?></strong></p>
            <br>
            <a href="survey.php" class="btn">Заповнити ще раз</a>
        </section>

<?php else: ?>

    <?php if (!empty($result['errors'])): ?>
        <section class="card error">
            <h2>Виправте помилки:</h2>
            <ul class="styled-list">
                <?php foreach ($result['errors'] as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

        <form method="POST" action="survey.php" class="card survey-form">
            <label for="name">Ваше ім’я:</label>
            <input type="text" id="name" name="name" required value="<?= e($old['name'] ?? '') ?>">

            <label for="email">Ваш email:</label>
            <input type="email" id="email" name="email" required value="<?= e($old['email'] ?? '') ?>">

            <?php $n = 1; foreach ($questions as $key => $q): ?>
                <fieldset>
                    <legend><?= $n++ ?>. <?= e($q['text']) ?></legend>

                    <?php if ($q['type'] === 'radio'): ?>
                        <?php foreach ($q['options'] as $option): ?>
                            <label class="option">
                                <input type="radio" name="<?= $key ?>" value="<?= e($option) ?>"
                                    <?= ($old[$key] ?? '') === $option ? 'checked' : '' ?>>
                                <?= e($option) ?>
                            </label>
                        <?php endforeach; ?>

                    <?php elseif ($q['type'] === 'checkbox'): ?>
                        <?php foreach ($q['options'] as $option): ?>
                            <label class="option">
                                <input type="checkbox" name="<?= $key ?>[]" value="<?= e($option) ?>"
                                    <?= str_contains($old[$key] ?? '', $option) ? 'checked' : '' ?>>
                                <?= e($option) ?>
                            </label>
                        <?php endforeach; ?>

                    <?php elseif ($q['type'] === 'select'): ?>
                        <select name="<?= $key ?>">
                            <option value="">— оберіть —</option>
                            <?php foreach ($q['options'] as $option): ?>
                                <option value="<?= e($option) ?>"
                                    <?= ($old[$key] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>

                    <?php else: ?>
                        <textarea name="<?= $key ?>" rows="4"><?= e($old[$key] ?? '') ?></textarea>
                    <?php endif; ?>
                </fieldset>
            <?php endforeach; ?>

            <button type="submit" class="btn">Надіслати відповідь</button>
        </form>

<?php endif; ?>

<?php include 'templates/footer.php'; ?>
