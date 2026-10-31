
<?php
require_once __DIR__ . '/../config/database.php';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| DATA KELAS
|--------------------------------------------------------------------------
*/
$classes = [];

$classResult = $conn->query("
    SELECT id, class_name
    FROM classes
    ORDER BY class_name
");

while ($row = $classResult->fetch_assoc()) {
    $classes[] = $row;
}

/*
|--------------------------------------------------------------------------
| FILTER UTAMA
|--------------------------------------------------------------------------
*/
$selectedClass = $_GET['kelas'] ?? ($classes[0]['class_name'] ?? 'X-A');

$selectedDate = trim($_GET['tanggal'] ?? '');
if ($selectedDate === '') {
    $selectedDate = date('Y-m-d');
}

$selectedSession = $_GET['session_code'] ?? '';

$success = '';
$error = '';

/*
|--------------------------------------------------------------------------
| SIMPAN ABSENSI
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {

    $postClass = trim($_POST['kelas'] ?? '');

    // Jika tanggal dikosongkan, gunakan tanggal hari ini.
    $postDate = trim($_POST['tanggal'] ?? '');
    if ($postDate === '') {
        $postDate = date('Y-m-d');
    }

    $session = trim($_POST['session_code'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $statuses = $_POST['status'] ?? [];

    /*
     * Pertemuan wajib dipilih.
     */
    if ($session === '') {
        $error = "Pertemuan wajib dipilih sebelum menyimpan absensi.";

    } elseif (!preg_match('/^P([1-9]|1[0-9]|20)$/', $session)) {
        $error = "Pertemuan yang dipilih tidak valid.";

    } elseif ($postClass === '') {
        $error = "Kelas wajib dipilih.";

    } else {

        /*
         * Validasi format tanggal.
         */
        $dateObject = DateTime::createFromFormat('!Y-m-d', $postDate);

        if (!$dateObject || $dateObject->format('Y-m-d') !== $postDate) {
            $error = "Format tanggal tidak valid.";
        } else {

            /*
             * Pastikan kelas memang ada.
             */
            $checkClass = $conn->prepare("
                SELECT id
                FROM classes
                WHERE class_name = ?
                LIMIT 1
            ");

            $checkClass->bind_param("s", $postClass);
            $checkClass->execute();

            $checkClassResult = $checkClass->get_result();
            $checkClassData = $checkClassResult->fetch_assoc();

            $checkClass->close();

            if (!$checkClassData) {
                $error = "Kelas tidak ditemukan.";

            } else {

                /*
                 * Simpan / update berdasarkan:
                 * student_id + attendance_date + session_code
                 *
                 * P1 dan P2 tetap menjadi data berbeda.
                 */
                $stmt = $conn->prepare("
                    INSERT INTO attendance
                        (student_id, attendance_date, session_code, status, note)
                    VALUES
                        (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        status = VALUES(status),
                        note = VALUES(note)
                ");

                if (!$stmt) {
                    $error = "Gagal menyiapkan query absensi: " . $conn->error;

                } else {

                    $saved = 0;

                    foreach ($statuses as $studentId => $status) {

                        if (!in_array($status, ['Hadir', 'Izin', 'Sakit', 'Alfa'], true)) {
                            continue;
                        }

                        $studentId = (int)$studentId;

                        $stmt->bind_param(
                            "issss",
                            $studentId,
                            $postDate,
                            $session,
                            $status,
                            $note
                        );

                        if ($stmt->execute()) {
                            $saved++;
                        }
                    }

                    $stmt->close();

                    if ($saved > 0) {
                        $success = "Absensi berhasil disimpan untuk {$saved} siswa.";

                        $selectedClass = $postClass;
                        $selectedDate = $postDate;
                        $selectedSession = $session;

                    } else {
                        $error = "Belum ada status kehadiran yang dipilih. Pilih status siswa terlebih dahulu.";

                        $selectedClass = $postClass;
                        $selectedDate = $postDate;
                        $selectedSession = $session;
                    }
                }
            }
        }
    }

    // Jika gagal, pertahankan pilihan tanggal dan filter.
    if ($error !== '') {
        $selectedClass = $postClass ?: $selectedClass;
        $selectedDate = $postDate;
        $selectedSession = $session;
    }
}

/*
|--------------------------------------------------------------------------
| HITUNG RENTANG MINGGU
|--------------------------------------------------------------------------
|
| Rekap menggunakan minggu Senin-Minggu dari tanggal yang dipilih.
|
*/
$weekDate = new DateTime($selectedDate);

$weekStart = clone $weekDate;
$weekStart->modify('monday this week');

$weekEnd = clone $weekStart;
$weekEnd->modify('sunday this week');

$weekStartStr = $weekStart->format('Y-m-d');
$weekEndStr = $weekEnd->format('Y-m-d');

/*
|--------------------------------------------------------------------------
| AMBIL ID KELAS
|--------------------------------------------------------------------------
*/
$classId = 0;

$classStmt = $conn->prepare("
    SELECT id
    FROM classes
    WHERE class_name = ?
    LIMIT 1
");

$classStmt->bind_param("s", $selectedClass);
$classStmt->execute();

$classData = $classStmt->get_result()->fetch_assoc();

$classStmt->close();

if ($classData) {
    $classId = (int)$classData['id'];
}

/*
|--------------------------------------------------------------------------
| DAFTAR SISWA + STATUS SESI YANG DIPILIH
|--------------------------------------------------------------------------
*/
$students = [];

if ($classId && $selectedSession !== '') {

    $stmt = $conn->prepare("
        SELECT
            s.id,
            s.student_name,
            a.status,
            a.note
        FROM students s
        LEFT JOIN attendance a
            ON a.student_id = s.id
            AND a.attendance_date = ?
            AND a.session_code = ?
        WHERE s.class_id = ?
        ORDER BY s.student_name
    ");

    $stmt->bind_param(
        "ssi",
        $selectedDate,
        $selectedSession,
        $classId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| CATATAN SESI YANG DIPILIH
|--------------------------------------------------------------------------
*/
$selectedSessionNote = '';

if ($classId && $selectedSession !== '') {

    $noteStmt = $conn->prepare("
        SELECT note
        FROM attendance a
        INNER JOIN students s
            ON s.id = a.student_id
        WHERE s.class_id = ?
            AND a.attendance_date = ?
            AND a.session_code = ?
            AND a.note IS NOT NULL
            AND a.note <> ''
        LIMIT 1
    ");

    $noteStmt->bind_param(
        "iss",
        $classId,
        $selectedDate,
        $selectedSession
    );

    $noteStmt->execute();

    $noteData = $noteStmt->get_result()->fetch_assoc();

    if ($noteData) {
        $selectedSessionNote = $noteData['note'];
    }

    $noteStmt->close();
}

/*
|--------------------------------------------------------------------------
| REKAP MINGGUAN
|--------------------------------------------------------------------------
*/
$weekly = [
    'Hadir' => 0,
    'Izin' => 0,
    'Sakit' => 0,
    'Alfa' => 0
];

if ($classId) {

    $weeklyStmt = $conn->prepare("
        SELECT
            a.status,
            COUNT(*) AS total
        FROM attendance a
        INNER JOIN students s
            ON s.id = a.student_id
        WHERE s.class_id = ?
            AND a.attendance_date BETWEEN ? AND ?
        GROUP BY a.status
    ");

    $weeklyStmt->bind_param(
        "iss",
        $classId,
        $weekStartStr,
        $weekEndStr
    );

    $weeklyStmt->execute();

    $weeklyResult = $weeklyStmt->get_result();

    while ($row = $weeklyResult->fetch_assoc()) {

        if (isset($weekly[$row['status']])) {
            $weekly[$row['status']] = (int)$row['total'];
        }
    }

    $weeklyStmt->close();
}

/*
|--------------------------------------------------------------------------
| SISWA YANG BELUM MEMILIKI ABSENSI SAMA SEKALI MINGGU INI
|--------------------------------------------------------------------------
*/
$missing = [];

if ($classId) {

    $missingStmt = $conn->prepare("
        SELECT s.student_name
        FROM students s
        LEFT JOIN attendance a
            ON a.student_id = s.id
            AND a.attendance_date BETWEEN ? AND ?
        WHERE s.class_id = ?
            AND a.id IS NULL
        ORDER BY s.student_name
    ");

    $missingStmt->bind_param(
        "ssi",
        $weekStartStr,
        $weekEndStr,
        $classId
    );

    $missingStmt->execute();

    $missingResult = $missingStmt->get_result();

    while ($row = $missingResult->fetch_assoc()) {
        $missing[] = $row['student_name'];
    }

    $missingStmt->close();
}

/*
|--------------------------------------------------------------------------
| STATISTIK
|--------------------------------------------------------------------------
*/
$totalStudents = count($students);

$recorded =
    $weekly['Hadir'] +
    $weekly['Izin'] +
    $weekly['Sakit'] +
    $weekly['Alfa'];
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Input Absensi | EduSpace</title>

<style>

*{
    box-sizing:border-box
}

body{
    margin:0;
    font-family:Arial,sans-serif;
    background:#f5f7fa;
    color:#243746
}

.sidebar{
    width:250px;
    height:100vh;
    background:#003049;
    position:fixed;
    left:0;
    top:0;
    padding:30px 22px;
    color:white;
    overflow-y:auto;
    z-index:5
}

.school-logo{
    text-align:center;
    padding-bottom:25px;
    border-bottom:1px solid rgba(255,255,255,.25);
    margin-bottom:28px
}

.school-logo img{
    width:150px;
    height:150px;
    object-fit:contain;
    margin-bottom:12px
}

.school-logo h2{
    color:#ffca8a;
    font-size:25px;
    margin:0 0 7px
}

.school-logo p{
    font-size:10px;
    letter-spacing:1px;
    color:#dce8f2;
    margin:0
}

.menu-title{
    color:#b7cbdc;
    font-size:11px;
    font-weight:bold;
    letter-spacing:1.5px;
    margin:0 10px 15px
}

.menu a{
    display:block;
    text-decoration:none;
    color:white;
    padding:13px 14px;
    margin-bottom:7px;
    border-radius:10px;
    font-size:14px;
    white-space:nowrap
}

.menu a:hover,
.menu a.active{
    background:#205778
}

.sidebar-bottom{
    text-align:center;
    color:#ffca8a;
    font-size:13px;
    line-height:1.8;
    margin-top:35px;
    padding-top:18px;
    border-top:1px solid rgba(255,255,255,.2)
}

.main{
    margin-left:250px;
    width:calc(100% - 250px);
    padding:32px 38px;
    min-height:100vh
}

.topline{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    margin-bottom:24px
}

.eyebrow{
    color:#668096;
    font-size:11px;
    font-weight:bold;
    letter-spacing:1.6px
}

.topline h1{
    font-size:27px;
    color:#003049;
    margin:7px 0
}

.teacher {
    display: flex;
    align-items: center;
    gap: 12px;
}

.teacher-info {
    text-align: right;
}

.teacher-info h4 {
    margin: 0 0 4px;
    font-size: 13px;
    color: #243746;
}

.teacher-info p {
    margin: 0;
    font-size: 11px;
    color: #8193a9;
}

.teacher-avatar {
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

.welcome{
    background:linear-gradient(110deg,#003049,#205778);
    color:white;
    padding:23px 26px;
    border-radius:17px;
    margin-bottom:22px
}

.welcome h2{
    margin:0 0 8px;
    font-size:20px
}

.welcome p{
    margin:0;
    color:#e1edf4;
    line-height:1.6;
    font-size:13px
}

.panel{
    background:white;
    border:1px solid #e6ebef;
    border-radius:16px;
    padding:22px;
    margin-bottom:20px;
    box-shadow:0 5px 18px rgba(0,48,73,.04)
}

.panel h3{
    margin:0 0 17px;
    color:#003049;
    font-size:17px
}

.filters{
    display:grid;
    grid-template-columns:1fr 1fr 1fr 1fr;
    gap:14px;
    align-items:end
}

label{
    display:block;
    font-size:12px;
    font-weight:bold;
    color:#526777;
    margin-bottom:7px
}

input,
select{
    width:100%;
    padding:11px 12px;
    border:1px solid #d8e0e6;
    border-radius:9px;
    background:#fff;
    color:#243746;
    font-size:13px
}

input:focus,
select:focus{
    outline:none;
    border-color:#205778;
    box-shadow:0 0 0 3px rgba(32,87,120,.08)
}

button,
.button{
    border:0;
    background:#003049;
    color:white;
    padding:12px 18px;
    border-radius:9px;
    font-weight:bold;
    font-size:13px;
    cursor:pointer;
    text-decoration:none;
    display:inline-block
}

button:hover,
.button:hover{
    background:#205778
}

.btn-gold{
    background:#d99a4b;
    color:#142d3a
}

.btn-gold:hover{
    background:#c98939
}

.alert{
    padding:12px 15px;
    border-radius:9px;
    margin-bottom:16px;
    font-size:13px
}

.success{
    background:#e5f5eb;
    color:#17643a
}

.error{
    background:#fff0ed;
    color:#a33b2d
}

.stats{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:12px
}

.stat{
    padding:15px;
    border-radius:12px;
    background:#f5f8fa
}

.stat span{
    font-size:12px;
    color:#657887
}

.stat strong{
    display:block;
    font-size:25px;
    color:#003049;
    margin-top:7px
}

.table-wrap{
    overflow-x:auto
}

table{
    width:100%;
    border-collapse:collapse;
    min-width:550px
}

th,
td{
    text-align:left;
    padding:12px 10px;
    border-bottom:1px solid #edf0f2;
    font-size:13px
}

th{
    color:#667b89;
    background:#f7f9fa;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.5px
}

td select{
    min-width:135px;
    padding:9px
}

.missing{
    background:#fff8eb;
    border:1px solid #f2dfbd;
    border-radius:11px;
    padding:14px;
    color:#73501e;
    font-size:13px;
    line-height:1.7
}

.muted{
    color:#748693;
    font-size:12px
}

.actions{
    display:flex;
    justify-content:flex-end;
    margin-top:17px
}

.session-badge{
    display:inline-block;
    background:#e5f2f8;
    color:#003049;
    padding:6px 10px;
    border-radius:20px;
    font-size:11px;
    font-weight:bold;
    margin-left:5px
}

.empty-state{
    background:#f7f9fa;
    border:1px dashed #d7e0e6;
    border-radius:12px;
    padding:25px;
    text-align:center;
    color:#71818c;
    font-size:13px
}

.note-box{
    margin-top:12px;
    padding:11px 13px;
    background:#f7f9fa;
    border-radius:9px;
    font-size:12px;
    color:#526777
}

@media(max-width:1100px){
    .filters{
        grid-template-columns:1fr 1fr
    }
}

@media(max-width:900px){
    .sidebar{
        width:220px;
        padding:25px 17px
    }

    .main{
        margin-left:220px;
        width:calc(100% - 220px);
        padding:25px
    }

    .stats{
        grid-template-columns:repeat(2,1fr)
    }
}

@media(max-width:650px){
    .sidebar{
        position:relative;
        width:100%;
        height:auto;
        padding:20px
    }

    .main{
        margin:0;
        width:100%;
        padding:20px 15px
    }

    .topline{
        align-items:flex-start;
        flex-direction:column
    }

    .filters{
        grid-template-columns:1fr
    }

    .stats{
        grid-template-columns:repeat(2,1fr)
    }
}

</style>

</head>

<body>

<!-- SIDEBAR -->

<aside class="sidebar">

    <div class="school-logo">

        <img
            src="../assets/logo-sekolah.png"
            alt="Logo sekolah"
            onerror="this.style.display='none'"
        >

        <h2>EduSpace.</h2>

        <p>LEARNING MANAGEMENT SYSTEM</p>

    </div>

    <p class="menu-title">MAIN MENU</p>

    <?php $base_url = '/eduspace/'; ?>

    <nav class="menu">

        <a href="<?= $base_url ?>index.php">
            🏠 Home
        </a>

        <a href="<?= $base_url ?>pages/materi.php">
            📚 Materi
        </a>

        <a href="<?= $base_url ?>pages/program.php">
            🗓️ Program Akademik
        </a>

        <a href="<?= $base_url ?>pages/absensi-guru.php" class="active">
            📋 Input Absensi
        </a>

        <a href="<?= $base_url ?>pages/rekap-absensi.php">
            📊 Rekap Absensi
        </a>

        <a href="<?= $base_url ?>pages/diskusi.php">
            💬 Ruang Diskusi
        </a>

        <a href="<?= $base_url ?>pages/tugas.php">
            📝 Tugas & Quiz
        </a>

        <a href="<?= $base_url ?>pages/nilai.php">
            📊 Nilai
        </a>

    </nav>

    <div class="sidebar-bottom">
        Learn, Explore,<br>
        Achieve. ✨
    </div>

</aside>


<!-- MAIN -->

<main class="main">

    <div class="topline">

        <div>

            <div class="eyebrow">
                TEACHER & ADMIN DASHBOARD
            </div>

            <h1>Attendance Management</h1>

            <div class="muted">
                Kelola absensi Bahasa Inggris Kelas X
            </div>

        </div>

        <div class="teacher">
    <div class="teacher-info">
        <h4>Safira Maulidiya, M.Pd.</h4>
        <p>English Teacher</p>
    </div>

    <div class="teacher-avatar">S</div>
</div>

    </div>


    <!-- WELCOME -->

    <section class="welcome">

        <h2>Welcome, Bu Safira! 👋</h2>

        <p>
            Silakan pilih kelas dan pertemuan untuk menginput
            kehadiran siswa. Tanggal otomatis mengikuti hari ini,
            tetapi dapat diubah jika diperlukan.
        </p>

    </section>


    <!-- ALERT -->

    <?php if ($success): ?>
        <div class="alert success">
            <?= h($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert error">
            <?= h($error) ?>
        </div>
    <?php endif; ?>


    <!-- FILTER KELAS / TANGGAL / PERTEMUAN -->

    <section class="panel">

        <h3>📅 Pilih Kelas & Pertemuan</h3>

        <form method="GET" class="filters">

            <div>

                <label for="kelas">
                    Kelas
                </label>

                <select name="kelas" id="kelas" required>

                    <?php foreach ($classes as $c): ?>

                        <option
                            value="<?= h($c['class_name']) ?>"
                            <?= $selectedClass === $c['class_name'] ? 'selected' : '' ?>
                        >
                            <?= h($c['class_name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div>

                <label for="tanggal">
                    Tanggal pertemuan (opsional)
                </label>

                <input
                    type="date"
                    id="tanggal"
                    name="tanggal"
                    value="<?= h($selectedDate) ?>"
                >

                <small class="muted">
                    
                </small>

            </div>


            <div>

                <label for="session_code">
                    Pertemuan ke-
                </label>

                <select
                    name="session_code"
                    id="session_code"
                    required
                >

                    <option value="">
                        Pilih pertemuan
                    </option>

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


            <div>

                <label>&nbsp;</label>

                <button type="submit">
                    Tampilkan Daftar Siswa
                </button>

            </div>

        </form>

    </section>


    <!-- INPUT KEHADIRAN -->

    <section class="panel">

        <h3>

            📝 Input Kehadiran —
            Kelas <?= h($selectedClass) ?>

            <?php if ($selectedSession !== ''): ?>

                <span class="session-badge">
                    <?= h($selectedSession) ?>
                </span>

            <?php endif; ?>

        </h3>


        <p class="muted">

            Tanggal:
            <?= h(date('d M Y', strtotime($selectedDate))) ?>

            <?php if ($selectedSession !== ''): ?>

                · Pertemuan:
                <?= h($selectedSession) ?>

            <?php endif; ?>

            · Pilih status untuk setiap siswa.

        </p>


        <?php if ($selectedSession === ''): ?>

            <div class="empty-state">

                📌 Silakan pilih
                <strong>kelas</strong> dan
                <strong>pertemuan</strong>
                terlebih dahulu.

            </div>

        <?php elseif (!$classId): ?>

            <div class="empty-state">
                Kelas tidak ditemukan di database.
            </div>

        <?php elseif (count($students) === 0): ?>

            <div class="empty-state">
                Tidak ada siswa yang ditemukan untuk kelas ini.
            </div>

        <?php else: ?>


            <form method="POST">

                <input
                    type="hidden"
                    name="kelas"
                    value="<?= h($selectedClass) ?>"
                >

                <input
                    type="hidden"
                    name="tanggal"
                    value="<?= h($selectedDate) ?>"
                >

                <input
                    type="hidden"
                    name="session_code"
                    value="<?= h($selectedSession) ?>"
                >


                <!-- CATATAN -->

                <div style="margin:16px 0">

                    <label for="note">
                        Catatan pertemuan (opsional)
                    </label>

                    <input
                        type="text"
                        name="note"
                        id="note"
                        value="<?= h($selectedSessionNote) ?>"
                        placeholder="Contoh: Descriptive Text - Meeting 1"
                    >

                </div>


                <!-- TABEL SISWA -->

                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th style="width:65px">
                                    No.
                                </th>

                                <th>
                                    Nama Siswa
                                </th>

                                <th style="width:210px">
                                    Status Kehadiran
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($students as $i => $student): ?>

                                <tr>

                                    <td>
                                        <?= $i + 1 ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= h($student['student_name']) ?>
                                        </strong>
                                    </td>

                                    <td>

                                        <select name="status[<?= h($student['id']) ?>]">

                                            <option value="">
                                                -- Belum diisi --
                                            </option>

                                            <?php foreach (['Hadir','Izin','Sakit','Alfa'] as $st): ?>

                                                <option
                                                    value="<?= h($st) ?>"
                                                    <?= $student['status'] === $st ? 'selected' : '' ?>
                                                >
                                                    <?= h($st) ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


                <!-- SIMPAN -->

                <div class="actions">

                    <button
                        class="btn-gold"
                        type="submit"
                        name="save_attendance"
                    >
                        💾 Simpan Absensi
                    </button>

                </div>

            </form>


            <?php if ($selectedSessionNote !== ''): ?>

                <div class="note-box">

                    📝 <strong>Catatan <?= h($selectedSession) ?>:</strong>
                    <?= h($selectedSessionNote) ?>

                </div>

            <?php endif; ?>


        <?php endif; ?>

    </section>


    <!-- REKAP MINGGUAN -->

    <section class="panel">

        <h3>
            📊 Rekap Mingguan —
            Kelas <?= h($selectedClass) ?>
        </h3>

        <p class="muted">

            Periode
            <?= h($weekStart->format('d M Y')) ?>
            –
            <?= h($weekEnd->format('d M Y')) ?>

            (Senin–Minggu).

            Rekap mencakup seluruh pertemuan dalam minggu tersebut.

        </p>


        <div class="stats">

            <div class="stat">
                <span>Hadir</span>
                <strong><?= $weekly['Hadir'] ?></strong>
            </div>

            <div class="stat">
                <span>Izin</span>
                <strong><?= $weekly['Izin'] ?></strong>
            </div>

            <div class="stat">
                <span>Sakit</span>
                <strong><?= $weekly['Sakit'] ?></strong>
            </div>

            <div class="stat">
                <span>Alfa</span>
                <strong><?= $weekly['Alfa'] ?></strong>
            </div>

        </div>


        <p class="muted" style="margin-top:13px">

            Jumlah catatan absensi:
            <?= $recorded ?>

            dari
            <?= $totalStudents ?>

            siswa untuk seluruh pertemuan dalam minggu ini.

        </p>


        <div class="missing" style="margin-top:14px">

            <strong>
                ⚠️ Siswa yang belum memiliki catatan absensi minggu ini:
            </strong>

            <br>

            <?php if (count($missing)): ?>

                <?= h(implode(', ', $missing)) ?>

            <?php else: ?>

                Semua siswa sudah memiliki setidaknya satu catatan absensi minggu ini.

            <?php endif; ?>

        </div>

    </section>


    <p class="muted">
        EduSpace · English Class · Academic Year 2026/2027
    </p>

</main>

</body>
</html>
