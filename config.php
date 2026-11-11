<?php
// Файл конфігурації: тут лише налаштування сайту (змінні), без логіки.

// Часовий пояс, щоб дата і час були київські
date_default_timezone_set('Europe/Kyiv');

// Папка, куди зберігаються відповіді у вигляді .txt файлів
define('SURVEY_DIR', __DIR__ . '/survey');

// Файл бази даних SQLite
define('DB_FILE', __DIR__ . '/data/survey.db');

// Логін і пароль адміністратора (для admin.php)
define('ADMIN_LOGIN', 'admin');
define('ADMIN_PASSWORD', 'admin123');
