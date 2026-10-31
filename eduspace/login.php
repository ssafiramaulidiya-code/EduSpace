<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

// Jika sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'teacher') {
        header("Location: index.php");
    } else {
        header("Location: ../eduspace-student/pages/index.php");
    }
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === "" || $password === "") {
        $error = "Username dan password harus diisi.";
    } else {

        $stmt = $conn->prepare("
            SELECT id, student_id, username, password_hash, role
            FROM user_accounts
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password_hash'])) {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['student_id'] = $user['student_id'];

                // Simpan nama dan kelas untuk akun siswa
                if ($user['role'] === 'student') {

                    // Contoh: "Adit 01" menjadi "Adit"
                    $_SESSION['student_name'] = preg_replace(
                        '/\s+\d+$/',
                        '',
                        $user['username']
                    );

                    // Kelas untuk tiga akun contoh
                    $class_map = [
                        'Adit 01' => 'A',
                        'Agus 01' => 'B',
                        'Aldo 01' => 'C'
                    ];

                    $_SESSION['student_class'] =
                        $class_map[$user['username']] ?? '';
                }

                if ($user['role'] === 'teacher') {
                    header("Location: index.php");
                } else {
                    header("Location: ../eduspace-student/pages/index.php");
                }

                exit;

            } else {
                $error = "Username atau password salah.";
            }

        } else {
            $error = "Username atau password salah.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | EduSpace</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f3efe7;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            padding: 40px 35px;
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(20, 40, 70, 0.10);
        }

        .logo {
            text-align: center;
            margin-bottom: 18px;
        }

        .logo img {
            width: 75px;
            height: 75px;
            object-fit: contain;
        }

        h2 {
            text-align: center;
            color: #183153;
            font-size: 26px;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: #777;
            font-size: 14px;
            margin-bottom: 30px;
        }

        label {
            display: block;
            color: #263b55;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #d8dce3;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 14px;
            outline: none;
        }

        input:focus {
            border-color: #183153;
        }

        button {
            width: 100%;
            padding: 14px;
            background: #183153;
            color: white;
            border: none;
            border-radius: 9px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #29496f;
        }

        .error {
            background: #fdeaea;
            color: #b42318;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 18px;
            text-align: center;
        }

        .footer {
            text-align: center;
            color: #999;
            font-size: 12px;
            margin-top: 25px;
        }
    </style>
</head>

<body>

<div class="login-container">

    <div class="logo">
        <img src="assets/logo-sekolah.png" alt="Logo Sekolah">
    </div>

    <h2>Welcome to EduSpace</h2>
    <p class="subtitle">Silakan masuk ke akun pembelajaran Anda</p>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Username</label>
        <input
            type="text"
            name="username"
            placeholder="Masukkan username"
            required
        >

        <label>Password</label>
        <input
            type="password"
            name="password"
            placeholder="Masukkan password"
            required
        >

        <button type="submit">Login</button>

    </form>

    <div class="footer">
        EduSpace Learning Management System
    </div>

</div>

</body>
</html>
