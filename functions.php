<?php
// Усі PHP-функції сайту. HTML-сторінки лише викликають ці функції.
require_once __DIR__ . '/config.php';

// Запускаємо сесію (потрібна для входу адміністратора)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------- Питання анкети ---------- */

// Повертає масив з питаннями анкети
function get_questions()
{
    return [
        'q1' => [
            'text'    => 'Як часто ви користуєтесь ШІ-інструментами?',
            'type'    => 'radio',
            'options' => ['Щодня', 'Кілька разів на тиждень', 'Рідко', 'Ніколи'],
        ],
        'q2' => [
            'text'    => 'Яким ШІ-інструментом ви користуєтесь найчастіше?',
            'type'    => 'select',
            'options' => ['ChatGPT', 'Gemini', 'Claude', 'Copilot', 'Інше'],
        ],
        'q3' => [
            'text'    => 'Для чого ви використовуєте ШІ? (можна обрати кілька)',
            'type'    => 'checkbox',
            'options' => ['Навчання', 'Програмування', 'Переклад текстів', 'Творчість', 'Інше'],
        ],
        'q4' => [
            'text'    => 'Чи допомагає вам ШІ краще навчатися?',
            'type'    => 'radio',
            'options' => ['Так', 'Частково', 'Ні'],
        ],
        'q5' => [
            'text'    => 'Ваш коментар: що ви думаєте про ШІ в освіті?',
            'type'    => 'textarea',
            'options' => [],
        ],
    ];
}

// Безпечний вивід тексту в HTML (захист від XSS)
function e($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/* ---------- Обробка форми ---------- */

// Перевіряє дані з форми. Повертає масив [дані, помилки]
function validate_form($post)
{
    $errors = [];
    $data = [];

    $data['name']  = trim($post['name'] ?? '');
    $data['email'] = trim($post['email'] ?? '');

    if ($data['name'] === '') {
        $errors[] = 'Введіть ваше ім’я.';
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введіть правильний email.';
    }

    foreach (get_questions() as $key => $q) {
        $answer = $post[$key] ?? '';

        if ($q['type'] === 'checkbox') {
            // з чекбоксів приходить масив, перетворюємо на рядок
            $answer = is_array($answer) ? implode(', ', $answer) : '';
        }
        $answer = trim($answer);

        if ($answer === '' && $q['type'] !== 'textarea') {
            $errors[] = 'Дайте відповідь на питання: «' . $q['text'] . '»';
        }
        $data[$key] = $answer;
    }

    $data['date'] = date('Y-m-d H:i:s');

    return [$data, $errors];
}

// Зберігає відповідь у текстовий файл у папці survey. Повертає ім'я файлу
function save_to_file($data)
{
    if (!is_dir(SURVEY_DIR)) {
        mkdir(SURVEY_DIR, 0777, true);
    }

    // Ім'я файлу містить дату і час, напр. answer_2026-11-11_14-30-05.txt
    $filename = 'answer_' . date('Y-m-d_H-i-s') . '_' . rand(100, 999) . '.txt';

    $text  = "Дата заповнення: {$data['date']}\n";
    $text .= "Ім'я: {$data['name']}\n";
    $text .= "Email: {$data['email']}\n\n";

    $number = 1;
    foreach (get_questions() as $key => $q) {
        $text .= "$number. {$q['text']}\n";
        $text .= "Відповідь: " . ($data[$key] !== '' ? $data[$key] : '—') . "\n\n";
        $number++;
    }

    file_put_contents(SURVEY_DIR . '/' . $filename, $text);
    return $filename;
}

/* ---------- База даних (SQLite) ---------- */

// Підключення до бази даних. Якщо таблиці немає, вона створюється
function db_connect()
{
    if (!is_dir(dirname(DB_FILE))) {
        mkdir(dirname(DB_FILE), 0777, true);
    }

    $db = new PDO('sqlite:' . DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec('CREATE TABLE IF NOT EXISTS answers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT, email TEXT,
        q1 TEXT, q2 TEXT, q3 TEXT, q4 TEXT, q5 TEXT,
        date TEXT, filename TEXT
    )');

    return $db;
}

// Зберігає відповідь у базу даних
function save_to_db($data, $filename)
{
    $db = db_connect();
    $sql = 'INSERT INTO answers (name, email, q1, q2, q3, q4, q5, date, filename)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $data['name'], $data['email'],
        $data['q1'], $data['q2'], $data['q3'], $data['q4'], $data['q5'],
        $data['date'], $filename,
    ]);
}

// Головна функція для survey.php: обробляє надіслану форму
function handle_survey()
{
    $result = ['sent' => false, 'errors' => [], 'data' => []];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return $result;
    }

    [$data, $errors] = validate_form($_POST);
    $result['data'] = $data;
    $result['errors'] = $errors;

    if (empty($errors)) {
        $filename = save_to_file($data);
        save_to_db($data, $filename);
        $result['sent'] = true;
    }

    return $result;
}

// Повертає всі відповіді з бази (нові зверху)
function get_all_answers()
{
    $db = db_connect();
    return $db->query('SELECT * FROM answers ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
}

// Видаляє відповідь з бази і відповідний .txt файл
function delete_answer($id)
{
    $db = db_connect();

    $stmt = $db->prepare('SELECT filename FROM answers WHERE id = ?');
    $stmt->execute([$id]);
    $filename = $stmt->fetchColumn();

    if ($filename && is_file(SURVEY_DIR . '/' . basename($filename))) {
        unlink(SURVEY_DIR . '/' . basename($filename));
    }

    $stmt = $db->prepare('DELETE FROM answers WHERE id = ?');
    $stmt->execute([$id]);
}

// Віддає всі відповіді як CSV-файл для завантаження (відкривається в Excel)
function export_csv()
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="survey_' . date('Y-m-d_H-i') . '.csv"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM, щоб Excel правильно показав кирилицю

    $header = ['ID', 'Дата', "Ім'я", 'Email'];
    foreach (get_questions() as $q) {
        $header[] = $q['text'];
    }
    fputcsv($out, $header, ';', '"', '');

    foreach (get_all_answers() as $row) {
        fputcsv($out, [$row['id'], $row['date'], $row['name'], $row['email'],
            $row['q1'], $row['q2'], $row['q3'], $row['q4'], $row['q5']], ';', '"', '');
    }
    fclose($out);
    exit;
}

/* ---------- Адміністратор ---------- */

// Перевіряє логін і пароль. Якщо вірні, записує вхід у $_SESSION
function admin_login($login, $password)
{
    if ($login === ADMIN_LOGIN && $password === ADMIN_PASSWORD) {
        $_SESSION['is_admin'] = true;
        return true;
    }
    return false;
}

// Чи увійшов адміністратор
function is_admin()
{
    return !empty($_SESSION['is_admin']);
}

// Вихід адміністратора
function admin_logout()
{
    $_SESSION = [];
    session_destroy();
}
