
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Memastikan pengguna sudah login
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /eduspace/login.php");
        exit;
    }
}

// Khusus guru
function require_teacher() {
    require_login();

    if ($_SESSION['role'] !== 'teacher') {
        header("Location: /eduspace-student/pages/index.php");
        exit;
    }
}

// Khusus siswa
function require_student() {
    require_login();

    if ($_SESSION['role'] !== 'student') {
        header("Location: /eduspace/index.php");
        exit;
    }
}

?>
