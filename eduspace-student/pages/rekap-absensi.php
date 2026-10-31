
<?php
require_once '../../eduspace/includes/auth.php';
require_student();

require_once '../../eduspace/config/database.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// IDENTITAS SISWA
$studentId = (int) $_SESSION['student_id'];

$studentStmt = $conn->prepare("
    SELECT student_name
    FROM students
    WHERE id = ?
    LIMIT 1
");
$studentStmt->bind_param("i", $studentId);
$studentStmt->execute();
$student = $studentStmt->get_result()->fetch_assoc();
$studentStmt->close();

if (!$student) {
    die("Data siswa tidak ditemukan.");
}

$username = $_SESSION['username'] ?? '';
$class_map = [
    'Adit 01' => 'A',
    'Agus 01' => 'B',
    'Aldo 01' => 'C'
];

$studentClass = $class_map[$username] ?? '';
// FILTER TANGGAL DAN PERTEMUAN
$selectedDate = trim($_GET['tanggal'] ?? '');
$selectedSession = trim($_GET['session_code'] ?? '');

if ($selectedDate !== '') {
    $dateObject = DateTime::createFromFormat('!Y-m-d', $selectedDate);

    if (!$dateObject || $dateObject->format('Y-m-d') !== $selectedDate) {
        $selectedDate = '';
    }
}

if ($selectedSession !== '' && !preg_match('/^P([1-9]|1[0-9]|20)$/', $selectedSession)) {
    $selectedSession = '';
}

// AMBIL RIWAYAT ABSENSI SISWA
$sql = "
    SELECT attendance_date, session_code, status, note
    FROM attendance
    WHERE student_id = ?
";

$params = [$studentId];
$types = "i";

if ($selectedDate !== '') {
    $sql .= " AND attendance_date = ?";
    $params[] = $selectedDate;
    $types .= "s";
}

if ($selectedSession !== '') {
    $sql .= " AND session_code = ?";
    $params[] = $selectedSession;
    $types .= "s";
}

$sql .= " ORDER BY attendance_date DESC, session_code DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

$result = $stmt->get_result();
$attendanceData = [];

while ($row = $result->fetch_assoc()) {
    $attendanceData[] = $row;
}

$stmt->close();

// HITUNG STATISTIK
$summary = [
    'Hadir' => 0,
    'Izin' => 0,
    'Sakit' => 0,
    'Alfa' => 0
];

foreach ($attendanceData as $attendance) {
    if (isset($summary[$attendance['status']])) {
        $summary[$attendance['status']]++;
    }
}

$totalAttendance = count($attendanceData);

function statusClass($status) {
    switch ($status) {
        case 'Hadir':
            return 'status-hadir';
        case 'Izin':
            return 'status-izin';
        case 'Sakit':
            return 'status-sakit';
        case 'Alfa':
            return 'status-alfa';
        default:
            return 'status-empty';
    }
}

$bulanIndonesia = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
];

function formatTanggal($tanggal, $bulanIndonesia) {
    $timestamp = strtotime($tanggal);

    return date('d', $timestamp) . ' ' .
        $bulanIndonesia[(int) date('n', $timestamp)] . ' ' .
        date('Y', $timestamp);
}

$base_url = '/eduspace-student/';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rekap Absensi | EduSpace Student</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: #f3f6fb;
    color: #17375e;
}

/* SIDEBAR */

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

/* MAIN CONTENT */

.main {
    margin-left: 250px;
    padding: 30px 38px;
    min-height: 100vh;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 28px;
}

.topbar h2 {
    font-size: 23px;
}

.student {
    display: flex;
    align-items: center;
    gap: 12px;
}

.student-info {
    text-align: right;
}

.student-info h4 {
    font-size: 13px;
}

.student-info p {
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
}

/* WELCOME */

.welcome {
    background: linear-gradient(120deg, #123454, #3475a5);
    border-radius: 18px;
    padding: 30px;
    color: white;
    margin-bottom: 25px;
}

.welcome-label {
    display: inline-block;
    background: rgba(255,255,255,.15);
    padding: 7px 14px;
    border-radius: 20px;
    font-size: 10px;
    letter-spacing: 1px;
    margin-bottom: 15px;
}

.welcome h1 {
    font-size: 23px;
    margin-bottom: 10px;
}

.welcome p {
    font-size: 12px;
    line-height: 1.9;
    color: #edf5fc;
}

/* PANEL */

.panel {
    background: white;
    border: 1px solid #e0e8f1;
    border-radius: 15px;
    padding: 23px;
    margin-bottom: 22px;
    box-shadow: 0 4px 12px rgba(20,50,80,.03);
}

.panel h3 {
    font-size: 16px;
    margin-bottom: 18px;
}

/* FILTER */

.filter-grid {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 15px;
    align-items: end;
}

label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 7px;
    color: #53677e;
}

input, select {
    width: 100%;
    padding: 11px 12px;
    border: 1px solid #d8e2ec;
    border-radius: 9px;
    font-family: inherit;
    font-size: 12px;
    color: #17375e;
    background: white;
}

input:focus, select:focus {
    outline: none;
    border-color: #3475a5;
}

.filter-button {
    display: inline-block;
    padding: 12px 17px;
    background: #173f65;
    color: white;
    border: none;
    border-radius: 9px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
}

.filter-button:hover {
    background: #3475a5;
}

.filter-actions {
    display: flex;
    gap: 8px;
}

/* STATISTICS */

.section-heading {
    margin: 30px 0 17px;
}

.section-heading h2 {
    font-size: 19px;
    margin-bottom: 5px;
}

.section-heading p {
    font-size: 11px;
    color: #8193a9;
}

.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
}

.stat-card {
    background: white;
    border: 1px solid #e0e8f1;
    border-radius: 13px;
    padding: 19px;
}

.stat-card span {
    font-size: 12px;
    color: #8193a9;
}

.stat-card strong {
    display: block;
    font-size: 27px;
    margin-top: 7px;
}

.stat-card.hadir strong { color: #23834d; }
.stat-card.izin strong { color: #bd8531; }
.stat-card.sakit strong { color: #3475a5; }
.stat-card.alfa strong { color: #c05245; }

/* TABLE */

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 600px;
}

th, td {
    text-align: left;
    padding: 14px 12px;
    border-bottom: 1px solid #edf1f5;
    font-size: 12px;
}

th {
    background: #f5f8fb;
    color: #71849a;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .5px;
}

tbody tr:hover {
    background: #fafcfe;
}

/* STATUS */

.status {
    display: inline-block;
    padding: 6px 11px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
}

.status-hadir {
    background: #e5f5eb;
    color: #17643a;
}

.status-izin {
    background: #fff4df;
    color: #86531f;
}

.status-sakit {
    background: #eaf2fb;
    color: #24577d;
}

.status-alfa {
    background: #ffe9e5;
    color: #a33b2d;
}

.status-empty {
    background: #f0f2f4;
    color: #71818c;
}

/* EMPTY STATE */

.empty-message {
    background: #f8fafc;
    border: 1px dashed #cbd8e6;
    border-radius: 12px;
    padding: 30px 20px;
    color: #8193a9;
    font-size: 12px;
    text-align: center;
    line-height: 1.8;
}

.note-text {
    color: #71849a;
    font-size: 11px;
}

footer {
    text-align: center;
    padding: 30px 10px 10px;
    color: #8193a9;
    font-size: 11px;
}

/* RESPONSIVE */

@media (max-width: 1100px) {
    .stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .filter-grid {
        grid-template-columns: 1fr 1fr;
    }

    .filter-actions {
        grid-column: 1 / -1;
    }
}

@media (max-width: 900px) {
    .sidebar {
        width: 200px;
    }

    .main {
        margin-left: 200px;
        padding: 25px;
    }
}

@media (max-width: 650px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }

    .main {
        margin-left: 0;
        padding: 20px;
    }

    .stats,
    .filter-grid {
        grid-template-columns: 1fr 1fr;
    }

    .filter-actions {
        grid-column: 1 / -1;
    }

    .topbar {
        align-items: flex-start;
        flex-direction: column;
    }

    .welcome h1 {
        font-size: 19px;
    }
}

@media (max-width: 400px) {
    .stats,
    .filter-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<aside class="sidebar">

    <div class="school-logo">
        <img src="../../eduspace/assets/logo-sekolah.png" alt="Logo SMA Bangsa Utama">
        <h2>EduSpace Student.</h2>
        <p>LEARNING MANAGEMENT SYSTEM</p>
    </div>

    <p class="menu-title">MAIN MENU</p>

    <nav class="menu">
        <a href="../index.php">🏠 Home</a>
<a href="materi.php">📚 Materi</a>
<a href="program.php">🗓️ Program Akademik</a>
<a href="rekap-absensi.php" class="active">📊 Rekap Absensi</a>
<a href="diskusi.php">💬 Ruang Diskusi</a>
<a href="tugas.php">📝 Tugas & Quiz</a>
<a href="nilai.php">📊 Nilai</a>
    </nav>

    <div class="sidebar-bottom">
        Learn, Explore,<br>Achieve. ✨
    </div>

</aside>

<main class="main">

    <header class="topbar">
        <h2>Rekap Absensi</h2>

        <div class="student">
    <div class="student-info">
        <h4><?= e($student['student_name']) ?></h4>
        <p>
            Student<?= $studentClass !== '' ? ' · Kelas ' . e($studentClass) : '' ?>
        </p>
    </div>

    <div class="student-avatar">
        <?= e(strtoupper(substr($student['student_name'], 0, 1))) ?>
    </div>
</div>
    </header>

    <section class="welcome">
        <span class="welcome-label">STUDENT ATTENDANCE RECORD</span>

        <h1>Attendance Overview 📊</h1>

        <p>
            Halo, <?= e($student['student_name']) ?>! 👋
            Di halaman ini kamu bisa melihat riwayat kehadiran
            selama mengikuti pembelajaran Bahasa Inggris.
            Data yang ditampilkan merupakan catatan absensi
            milik akun kamu sendiri.
        </p>
    </section>

    <!-- FILTER -->

    <section class="panel">
        <h3>🔎 Filter Riwayat Absensi</h3>

        <form method="GET" class="filter-grid">

            <div>
                <label for="tanggal">Tanggal</label>
                <input
                    type="date"
                    name="tanggal"
                    id="tanggal"
                    value="<?= e($selectedDate) ?>"
                >
            </div>

            <div>
                <label for="session_code">Pertemuan</label>
                <select name="session_code" id="session_code">
                    <option value="">Semua Pertemuan</option>

                    <?php for ($i = 1; $i <= 20; $i++): ?>
                        <?php $sessionValue = 'P' . $i; ?>

                        <option
                            value="<?= $sessionValue ?>"
                            <?= $selectedSession === $sessionValue ? 'selected' : '' ?>
                        >
                            Pertemuan <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="filter-button">
                    Tampilkan
                </button>

                <a href="<?= $base_url ?>pages/rekap-absensi.php" class="filter-button">
                    Reset
                </a>
            </div>

        </form>
    </section>

    <!-- STATISTIK -->

    <div class="section-heading">
        <h2>📈 Attendance Summary</h2>
        <p>Ringkasan kehadiran berdasarkan hasil filter yang dipilih.</p>
    </div>

    <section class="stats">

        <div class="stat-card hadir">
            <span>Hadir</span>
            <strong><?= $summary['Hadir'] ?></strong>
        </div>

        <div class="stat-card izin">
            <span>Izin</span>
            <strong><?= $summary['Izin'] ?></strong>
        </div>

        <div class="stat-card sakit">
            <span>Sakit</span>
            <strong><?= $summary['Sakit'] ?></strong>
        </div>

        <div class="stat-card alfa">
            <span>Alfa</span>
            <strong><?= $summary['Alfa'] ?></strong>
        </div>

    </section>

    <!-- RIWAYAT ABSENSI -->

    <div class="section-heading">
        <h2>📋 Attendance History</h2>
        <p>Daftar riwayat kehadiran kamu berdasarkan catatan guru.</p>
    </div>

    <section class="panel">

        <?php if (count($attendanceData) > 0): ?>

            <div class="table-wrap">
                <table>

                    <thead>
                        <tr>
                            <th style="width:60px;">No.</th>
                            <th>Tanggal</th>
                            <th>Pertemuan</th>
                            <th>Status</th>
                            <th>Catatan Guru</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($attendanceData as $index => $attendance): ?>

                            <tr>
                                <td><?= $index + 1 ?></td>

                                <td>
                                    <?= e(formatTanggal($attendance['attendance_date'], $bulanIndonesia)) ?>
                                </td>

                                <td>
                                    <?= e($attendance['session_code']) ?>
                                </td>

                                <td>
                                    <span class="status <?= e(statusClass($attendance['status'])) ?>">
                                        <?= e($attendance['status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if (!empty($attendance['note'])): ?>
                                        <?= e($attendance['note']) ?>
                                    <?php else: ?>
                                        <span class="note-text">Tidak ada catatan</span>
                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

            <p class="note-text" style="margin-top:18px;">
                Menampilkan <?= $totalAttendance ?> catatan absensi.
            </p>

        <?php else: ?>

            <div class="empty-message">
                📌 Belum ada data absensi yang ditemukan.<br>
                Riwayat kehadiran kamu akan muncul di sini setelah guru
                menginput absensi.
            </div>

        <?php endif; ?>

    </section>

    <footer>
        © 2026 EduSpace Student · SMA Bangsa Utama · English Learning Platform
    </footer>

</main>

</body>
</html>
