<?php
require_once '../../eduspace/includes/auth.php';
$username = $_SESSION['username'] ?? 'Student';
$studentName = preg_replace('/\s+\d+$/', '', $username);

$class_map = [
    'Adit 01' => 'A',
    'Agus 01' => 'B',
    'Aldo 01' => 'C'
];

$studentClass = $class_map[$username] ?? '';
require_student();

require_once '../../eduspace/config/database.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$studentId = (int) $_SESSION['student_id'];

// Ambil nama murid yang sedang login
$stmt = $conn->prepare("
    SELECT student_name
    FROM students
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Data siswa tidak ditemukan.");
}

// Daftar quiz dan tugas yang digunakan guru
$activities = [
    'quiz1' => 'Quiz 1',
    'quiz2' => 'Quiz 2',
    'quiz3' => 'Quiz 3',
    'quiz4' => 'Quiz 4',
    'quiz5' => 'Quiz 5',
    'quiz6' => 'Quiz 6',
    'uts_genap' => 'UTS Genap',
    'uts_ganjil' => 'UTS Ganjil',
    'project' => 'Project'
];

// Ambil nilai milik murid yang sedang login
$stmt = $conn->prepare("
    SELECT quiz_code, status, score, teacher_note
    FROM quiz_progress
    WHERE student_id = ?
");
$stmt->bind_param("i", $studentId);
$stmt->execute();

$result = $stmt->get_result();
$grades = [];

while ($row = $result->fetch_assoc()) {
    $grades[$row['quiz_code']] = $row;
}

$stmt->close();

$base_url = '/eduspace-student/';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Score Recap | EduSpace</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

body {
    background: #f3f6fb;
    color: #17375e;
}

.main {
    margin-left: 250px;
    padding: 35px 40px;
    min-height: 100vh;
}

.note-card {
    background: #fff;
    border-radius: 16px;
    padding: 28px;
    margin-bottom: 28px;
    box-shadow: 0 5px 18px rgba(0,0,0,0.05);
    line-height: 1.7;
}

.note-card h1 {
    color: #073b5c;
    font-size: 25px;
    margin-bottom: 18px;
}

.note-card p {
    margin-bottom: 12px;
}

.note-card .greeting {
    font-weight: 700;
    color: #205778;
}

.note-card .signature {
    margin-top: 20px;
    color: #205778;
}

.page-title {
    color: #073b5c;
    margin-bottom: 18px;
}

.grade-card {
    background: white;
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 15px;
    box-shadow: 0 5px 18px rgba(0,0,0,0.05);
}

.grade-card h2 {
    color: #073b5c;
    font-size: 17px;
    margin-bottom: 12px;
}

.grade-info {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 12px;
}

.grade-info span {
    background: #f0f4f7;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 13px;
}

.score {
    font-weight: 700;
    color: #18794e;
}

.note {
    background: #fff8e9;
    border-left: 4px solid #ffca8a;
    padding: 12px 14px;
    border-radius: 7px;
    font-size: 14px;
}

.no-note {
    color: #718096;
    font-size: 13px;
}

.empty {
    background: white;
    padding: 28px;
    border-radius: 14px;
    color: #718096;
    text-align: center;
}

@media (max-width: 800px) {
    .main {
        margin-left: 0;
        padding: 25px 18px;
    }

    .note-card {
    background: linear-gradient(120deg, #123454, #3475a5);
    color: white;
    border-radius: 16px;
    padding: 28px;
    margin-bottom: 28px;
    box-shadow: 0 5px 18px rgba(0,0,0,0.05);
    line-height: 1.7;
}

.note-card h1 {
    color: white;
    font-size: 25px;
    margin-bottom: 18px;
}

.note-card .greeting {
    font-weight: 700;
    color: #f3d9a7;
}

.note-card .signature {
    margin-top: 20px;
    color: #f3d9a7;
}
}

.sidebar {
    width: 250px;
    height: 100vh;
    background: #102f50;
    color: white;
    position: fixed;
    top: 0;
    left: 0;
    padding: 25px 20px;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}

.school-logo {
    text-align: center;
    padding-bottom: 25px;
    border-bottom: 1px solid rgba(255,255,255,.3);
}

.school-logo img {
    width: 150px;
    height: 150px;
    object-fit: contain;
    margin-bottom: 12px;
}

.school-logo h2 {
    font-size: 25px;
    color: #f2d59b;
}

.school-logo p {
    font-size: 10px;
    letter-spacing: 1px;
    margin-top: 5px;
    color: #d7e2ef;
}

.menu-title {
    font-size: 11px;
    color: #a9bfd5;
    margin: 30px 10px 12px;
    letter-spacing: 1.5px;
    font-weight: 600;
}

.menu a {
    display: block;
    text-decoration: none;
    color: #e5edf5;
    padding: 13px 15px;
    border-radius: 10px;
    margin-bottom: 8px;
    font-size: 13px;
    transition: .3s;
}

.menu a:hover,
.menu a.active {
    background: #31577e;
    color: white;
}

.sidebar-bottom {
    margin-top: auto;
    padding: 25px 5px 5px;
    text-align: center;
    color: #f2d59b;
    font-size: 13px;
    font-style: italic;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
    background: transparent;
}

.topbar h2 {
    font-size: 23px;
    color: #17375e;
}

.student {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.student-info {
    text-align: right;
}

.student-info h4 {
    margin: 0 0 4px;
    font-size: 13px;
    color: #243746;
}

.student-info p {
    margin: 0;
    font-size: 11px;
    color: #8193a9;
}

.student-avatar {
    width: 43px;
    height: 43px;
    border-radius: 50%;
    background: #e3edf8;
    color: #17375e;
    display: grid;
    place-items: center;
    font-weight: 700;
    flex-shrink: 0;
}

footer {
    text-align: center;
    padding: 28px 10px 10px;
    color: #8193a9;
    font-size: 11px;
    line-height: 1.8;
}

/* Samakan kotak catatan dengan Welcome di Home */
.note-card {
    background: linear-gradient(120deg, #123454, #3475a5) !important;
    color: #ffffff;
}

.note-card h1 {
    color: #ffffff;
}

.note-card .greeting,
.note-card .signature {
    color: #f3d9a7;
}

</style>
</head>

<body>

<aside class="sidebar">
    <div class="school-logo">
        <img src="/eduspace/assets/logo-sekolah.png" alt="Logo SMA Bangsa Utama">
        <h2>EduSpace.</h2>
        <p>LEARNING MANAGEMENT SYSTEM</p>
    </div>

    <p class="menu-title">MAIN MENU</p>

    <nav class="menu">
        <a href="../index.php">🏠 Home</a>
<a href="materi.php">📚 Materi</a>
<a href="program.php">🗓️ Program Akademik</a>
<a href="rekap-absensi.php">📊 Rekap Absensi</a>
<a href="diskusi.php">💬 Ruang Diskusi</a>
<a href="tugas.php">📝 Tugas & Quiz</a>
<a href="nilai.php" class="active">📊 Nilai</a>
    </nav>

    <div class="sidebar-bottom">
        Learn, Explore,<br>Achieve. ✨
    </div>
</aside>

<main class="main">

<header class="topbar">
    <h2>Recap Score</h2>

    <div class="student">
        <div class="student-info">
            <h4><?= htmlspecialchars($studentName) ?></h4>
            <p>
                Student<?= $studentClass !== '' ? ' · Kelas ' . htmlspecialchars($studentClass) : '' ?>
            </p>
        </div>

        <div class="student-avatar">
            <?= htmlspecialchars(strtoupper(substr($studentName, 0, 1))) ?>
        </div>
    </div>
</header>

    <section class="note-card">
        <h1>A Little Note for Everyone: Proud of Your Hard Work! ✨🚀</h1>

        <p class="greeting">
            Dear <?= e($student['student_name']) ?>, 👋
        </p>

        <p>
            I just want to take a moment to appreciate all of your hard work in this class! 🌟
        </p>

        <p>
            Apapun hasilnya, berapapun nilainya, percayalah bahwa itu semua adalah bukti dari usaha dan proses kerja keras yang sudah kamu perjuangkan.
            <strong>Be proud of yourself!</strong>
        </p>

        <p>
            Jangan pernah menyerah atau berkecil hati, karena perjalanan kita masih panjang dan masih banyak kesempatan-kesempatan di depan yang bisa kamu manfaatkan untuk terus memperbaiki dan meningkatkan hasil kamu.
            <strong>Learning English is a journey, not a sprint.</strong>
        </p>

        <p>
            Tetap semangat, <strong>keep moving forward, stay curious</strong>, dan mari kita melangkah lebih jauh lagi bersama-sama! 🚀
        </p>

        <p class="signature">
            <em>Warm regards,</em><br>
            <strong>Bu Safira Maulidiya, M.Pd. 💙</strong>
        </p>
    </section>

    <?php foreach ($activities as $code => $title): ?>
        <?php $grade = $grades[$code] ?? null; ?>

        <section class="grade-card">
            <h2><?= e($title) ?></h2>

            <?php if (!$grade): ?>
                <p class="no-note">Nilai belum diberikan oleh guru.</p>
            <?php else: ?>
                <div class="grade-info">
                    <span>
                        Status:
                        <strong><?= e($grade['status'] ?? 'Belum ada') ?></strong>
                    </span>

                    <span>
                        Nilai:
                        <strong class="score">
                            <?= $grade['score'] !== null && $grade['score'] !== ''
                                ? e($grade['score'])
                                : 'Belum dinilai' ?>
                        </strong>
                    </span>
                </div>

                <?php if (!empty($grade['teacher_note'])): ?>
                    <div class="note">
                        <strong>Catatan guru:</strong><br>
                        <?= nl2br(e($grade['teacher_note'])) ?>
                    </div>
                <?php else: ?>
                    <p class="no-note">Belum ada catatan dari guru.</p>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <footer>
    © 2026 EduSpace — SMA Bangsa Utama<br>
    Designed for Grade 10 English Learning<br>
    Happy Learning! 💙
</footer>

</main>

</body>
</html>