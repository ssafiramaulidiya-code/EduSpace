
<?php
require_once __DIR__ . '/../config/database.php';

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA KELAS
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
| FILTER
|--------------------------------------------------------------------------
*/
$selectedClass = $_GET['kelas'] ?? ($classes[0]['class_name'] ?? '');

// Tanggal tidak wajib diisi.
// Jika kosong, otomatis menggunakan tanggal hari ini.
$selectedDate = trim($_GET['tanggal'] ?? '');

if ($selectedDate === '') {
    $selectedDate = date('Y-m-d');
}

// Validasi format tanggal agar tidak menyebabkan error.
$dateObject = DateTime::createFromFormat('!Y-m-d', $selectedDate);

if (!$dateObject || $dateObject->format('Y-m-d') !== $selectedDate) {
    $selectedDate = date('Y-m-d');
}

$selectedSession = $_GET['session_code'] ?? '';

$students = [];

$summary = [
    'Hadir' => 0,
    'Izin' => 0,
    'Sakit' => 0,
    'Alfa' => 0
];

$classId = 0;
$totalStudents = 0;
$totalRecorded = 0;

/*
|--------------------------------------------------------------------------
| CARI ID KELAS
|--------------------------------------------------------------------------
*/
if ($selectedClass !== '') {

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
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA REKAP
|--------------------------------------------------------------------------
|
| Rekap dibatasi oleh:
| 1. class_id
| 2. attendance_date
| 3. session_code
|
|--------------------------------------------------------------------------
*/
if ($classId > 0 && $selectedSession !== '') {

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
        ORDER BY s.student_name ASC
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

        if ($row['status'] !== null && $row['status'] !== '') {

            $totalRecorded++;

            if (isset($summary[$row['status']])) {
                $summary[$row['status']]++;
            }
        }
    }

    $stmt->close();

    $totalStudents = count($students);
}

/*
|--------------------------------------------------------------------------
| HITUNG BELUM DIISI
|--------------------------------------------------------------------------
*/
$summary['Belum Diisi'] = max(
    0,
    $totalStudents - $totalRecorded
);

/*
|--------------------------------------------------------------------------
| CATATAN PERTEMUAN
|--------------------------------------------------------------------------
*/
$sessionNote = '';

if ($classId > 0 && $selectedSession !== '') {

    $noteStmt = $conn->prepare("
        SELECT a.note
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
        $sessionNote = $noteData['note'];
    }

    $noteStmt->close();
}

/*
|--------------------------------------------------------------------------
| LABEL PERTEMUAN
|--------------------------------------------------------------------------
*/
$sessionNumber = '';

if (preg_match('/^P([0-9]+)$/', $selectedSession, $match)) {
    $sessionNumber = $match[1];
}

/*
|--------------------------------------------------------------------------
| NAMA BULAN INDONESIA
|--------------------------------------------------------------------------
*/
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

$timestamp = strtotime($selectedDate);

$formattedDate =
    date('d', $timestamp)
    . ' '
    . $bulanIndonesia[(int)date('n', $timestamp)]
    . ' '
    . date('Y', $timestamp);

/*
|--------------------------------------------------------------------------
| STATUS CLASS
|--------------------------------------------------------------------------
*/
function statusClass($status)
{
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

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Rekap Absensi | EduSpace</title>

<style>

/* =========================================================
   GLOBAL
========================================================= */

*{
    box-sizing:border-box;
}

body{
    margin:0;
    font-family:Arial,sans-serif;
    background:#f5f7fa;
    color:#243746;
}

/* =========================================================
   SIDEBAR
========================================================= */

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
    z-index:5;
}

.school-logo{
    text-align:center;
    padding-bottom:25px;
    border-bottom:1px solid rgba(255,255,255,.25);
    margin-bottom:28px;
}

.school-logo img{
    width:150px;
    height:150px;
    object-fit:contain;
    margin-bottom:12px;
}

.school-logo h2{
    color:#ffca8a;
    font-size:25px;
    margin:0 0 7px;
}

.school-logo p{
    font-size:10px;
    letter-spacing:1px;
    color:#dce8f2;
    margin:0;
}

.menu-title{
    color:#b7cbdc;
    font-size:11px;
    font-weight:bold;
    letter-spacing:1.5px;
    margin:0 10px 15px;
}

.menu a{
    display:block;
    text-decoration:none;
    color:white;
    padding:13px 14px;
    margin-bottom:7px;
    border-radius:10px;
    font-size:14px;
    white-space:nowrap;
}

.menu a:hover,
.menu a.active{
    background:#205778;
}

.sidebar-bottom{
    text-align:center;
    color:#ffca8a;
    font-size:13px;
    line-height:1.8;
    margin-top:35px;
    padding-top:18px;
    border-top:1px solid rgba(255,255,255,.2);
}

/* =========================================================
   MAIN
========================================================= */

.main{
    margin-left:250px;
    width:calc(100% - 250px);
    padding:32px 38px;
    min-height:100vh;
}

/* =========================================================
   HEADER
========================================================= */

.topline{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    margin-bottom:24px;
}

.eyebrow{
    color:#668096;
    font-size:11px;
    font-weight:bold;
    letter-spacing:1.6px;
}

.topline h1{
    font-size:27px;
    color:#003049;
    margin:7px 0;
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

/* =========================================================
   WELCOME
========================================================= */

.welcome{
    background:linear-gradient(
        110deg,
        #003049,
        #205778
    );
    color:white;
    padding:23px 26px;
    border-radius:17px;
    margin-bottom:22px;
}

.welcome h2{
    margin:0 0 8px;
    font-size:20px;
}

.welcome p{
    margin:0;
    color:#e1edf4;
    line-height:1.6;
    font-size:13px;
}

/* =========================================================
   PANEL
========================================================= */

.panel{
    background:white;
    border:1px solid #e6ebef;
    border-radius:16px;
    padding:22px;
    margin-bottom:20px;
    box-shadow:0 5px 18px rgba(0,48,73,.04);
}

.panel h3{
    margin:0 0 17px;
    color:#003049;
    font-size:17px;
}

/* =========================================================
   FILTER
========================================================= */

.filters{
    display:grid;
    grid-template-columns:1fr 1fr 1fr 1fr;
    gap:14px;
    align-items:end;
}

label{
    display:block;
    font-size:12px;
    font-weight:bold;
    color:#526777;
    margin-bottom:7px;
}

input,
select{
    width:100%;
    padding:11px 12px;
    border:1px solid #d8e0e6;
    border-radius:9px;
    background:#fff;
    color:#243746;
    font-size:13px;
}

input:focus,
select:focus{
    outline:none;
    border-color:#205778;
    box-shadow:0 0 0 3px rgba(32,87,120,.08);
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
    display:inline-block;
}

button:hover,
.button:hover{
    background:#205778;
}

/* =========================================================
   REKAP HEADER
========================================================= */

.rekap-header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:20px;
    margin-bottom:20px;
}

.rekap-title h2{
    margin:0 0 8px;
    color:#003049;
    font-size:21px;
}

.rekap-title p{
    margin:0;
    color:#71818c;
    font-size:13px;
    line-height:1.7;
}

.session-badge{
    display:inline-block;
    background:#e5f2f8;
    color:#003049;
    padding:7px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:bold;
    white-space:nowrap;
}

/* =========================================================
   STATISTICS
========================================================= */

.stats{
    display:grid;
    grid-template-columns:repeat(5,1fr);
    gap:12px;
    margin-bottom:22px;
}

.stat{
    padding:17px;
    border-radius:12px;
    background:#f5f8fa;
    border:1px solid #edf1f3;
}

.stat span{
    font-size:12px;
    color:#657887;
}

.stat strong{
    display:block;
    font-size:26px;
    color:#003049;
    margin-top:7px;
}

/* =========================================================
   TABLE
========================================================= */

.table-wrap{
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
    min-width:650px;
}

th,
td{
    text-align:left;
    padding:13px 11px;
    border-bottom:1px solid #edf0f2;
    font-size:13px;
}

th{
    color:#667b89;
    background:#f7f9fa;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.5px;
}

tbody tr:hover{
    background:#fafcfd;
}

/* =========================================================
   STATUS
========================================================= */

.status{
    display:inline-block;
    padding:6px 10px;
    border-radius:20px;
    font-size:11px;
    font-weight:bold;
}

.status-hadir{
    background:#e5f5eb;
    color:#17643a;
}

.status-izin{
    background:#fff4df;
    color:#86531f;
}

.status-sakit{
    background:#eaf2fb;
    color:#24577d;
}

.status-alfa{
    background:#ffe9e5;
    color:#a33b2d;
}

.status-empty{
    background:#f0f2f4;
    color:#71818c;
}

/* =========================================================
   NOTE
========================================================= */

.note-box{
    background:#f7f9fa;
    border:1px solid #e5ebef;
    padding:14px 16px;
    border-radius:10px;
    font-size:13px;
    color:#526777;
    margin-top:18px;
}

.note-box strong{
    color:#003049;
}

/* =========================================================
   EMPTY
========================================================= */

.empty-state{
    background:#f7f9fa;
    border:1px dashed #d7e0e6;
    border-radius:12px;
    padding:30px;
    text-align:center;
    color:#71818c;
    font-size:13px;
    line-height:1.7;
}

.muted{
    color:#748693;
    font-size:12px;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1100px){

    .filters{
        grid-template-columns:1fr 1fr;
    }

    .stats{
        grid-template-columns:repeat(3,1fr);
    }

}

@media(max-width:900px){

    .sidebar{
        width:220px;
        padding:25px 17px;
    }

    .main{
        margin-left:220px;
        width:calc(100% - 220px);
        padding:25px;
    }

}

@media(max-width:650px){

    .sidebar{
        position:relative;
        width:100%;
        height:auto;
        padding:20px;
    }

    .main{
        margin:0;
        width:100%;
        padding:20px 15px;
    }

    .topline{
        align-items:flex-start;
        flex-direction:column;
    }

    .filters{
        grid-template-columns:1fr;
    }

    .stats{
        grid-template-columns:repeat(2,1fr);
    }

    .rekap-header{
        flex-direction:column;
    }

}

</style>

</head>

<body>

<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar">

    <div class="school-logo">

        <img
            src="../assets/logo-sekolah.png"
            alt="Logo sekolah"
            onerror="this.style.display='none'"
        >

        <h2>
            EduSpace.
        </h2>

        <p>
            LEARNING MANAGEMENT SYSTEM
        </p>

    </div>

    <p class="menu-title">
        MAIN MENU
    </p>

    <?php
    $base_url = '/eduspace/';
    ?>

    <nav class="menu">

       <a href="../index.php">🏠 Home</a>
        <a href="materi.php">📚 Materi</a>
        <a href="program.php">🗓️ Program Akademik</a>
        <a href="absensi-guru.php">📋 Input Absensi</a>
        <a href="rekap-absensi.php" class="active">📊 Rekap Absensi</a>
        <a href="diskusi.php">💬 Ruang Diskusi</a>
        <a href="tugas.php">📝 Tugas & Quiz</a>
        <a href="nilai.php">📊 Nilai</a>

    </nav>

    <div class="sidebar-bottom">

        Learn, Explore,<br>
        Achieve. ✨

    </div>

</aside>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="main">

    <!-- HEADER -->

    <div class="topline">

        <div>

            <div class="eyebrow">
                TEACHER & ADMIN DASHBOARD
            </div>

            <h1>
                Rekap Absensi
            </h1>

            <div class="muted">
                Lihat rekap kehadiran berdasarkan pertemuan
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

        <h2>
            Rekap Absensi Pertemuan 📊
        </h2>

        <p>
            Pilih kelas, tanggal, dan pertemuan untuk melihat
            rekap absensi. Setiap pertemuan memiliki rekap
            sendiri dan tidak digabungkan dengan pertemuan lain.
        </p>

    </section>


    <!-- =====================================================
         FILTER
    ====================================================== -->

    <section class="panel">

        <h3>
            🔎 Pilih Data Rekap
        </h3>

        <form
            method="GET"
            class="filters"
        >

            <!-- KELAS -->

            <div>

                <label for="kelas">
                    Kelas
                </label>

                <select
                    name="kelas"
                    id="kelas"
                    required
                >

                    <?php foreach ($classes as $class): ?>

                        <option
                            value="<?=h($class['class_name'])?>"
                            <?=$selectedClass === $class['class_name'] ? 'selected' : ''?>
                        >
                            <?=h($class['class_name'])?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- TANGGAL -->

            <div>

                <label for="tanggal">
                    Tanggal
                </label>

                <input
                    type="date"
                    name="tanggal"
                    id="tanggal"
                    value="<?=h($selectedDate)?>"
                >

                <small class="muted">
                    Otomatis menggunakan tanggal hari ini jika dikosongkan.
                </small>

            </div>


            <!-- PERTEMUAN -->

            <div>

                <label for="session_code">
                    Pertemuan
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
                            value="<?=$sessionValue?>"
                            <?=$selectedSession === $sessionValue ? 'selected' : ''?>
                        >
                            Pertemuan <?=$i?>
                        </option>

                    <?php endfor; ?>

                </select>

            </div>


            <!-- BUTTON -->

            <div>

                <label>
                    &nbsp;
                </label>

                <button type="submit">
                    Tampilkan Rekap
                </button>

            </div>

        </form>

    </section>


    <!-- =====================================================
         HASIL REKAP
    ====================================================== -->

    <?php if ($selectedSession === ''): ?>

        <section class="panel">

            <div class="empty-state">

                📌 Silakan pilih
                <strong>kelas</strong>,
                <strong>tanggal</strong>,
                dan
                <strong>pertemuan</strong>
                untuk menampilkan rekap.

            </div>

        </section>


    <?php elseif ($classId === 0): ?>

        <section class="panel">

            <div class="empty-state">

                Kelas yang dipilih tidak ditemukan
                di database.

            </div>

        </section>


    <?php else: ?>

        <section class="panel">

            <!-- HEADER REKAP -->

            <div class="rekap-header">

                <div class="rekap-title">

                    <h2>
                        Rekap Pertemuan <?=$sessionNumber?>
                    </h2>

                    <p>

                        Kelas:
                        <strong><?=h($selectedClass)?></strong>

                        <br>

                        Tanggal:
                        <strong><?=h($formattedDate)?></strong>

                    </p>

                </div>

                <div class="session-badge">

                    <?=h($selectedSession)?>

                </div>

            </div>


            <!-- =================================================
                 STATISTIK
            ================================================== -->

            <div class="stats">

                <div class="stat">

                    <span>
                        Hadir
                    </span>

                    <strong>
                        <?=$summary['Hadir']?>
                    </strong>

                </div>

                <div class="stat">

                    <span>
                        Izin
                    </span>

                    <strong>
                        <?=$summary['Izin']?>
                    </strong>

                </div>

                <div class="stat">

                    <span>
                        Sakit
                    </span>

                    <strong>
                        <?=$summary['Sakit']?>
                    </strong>

                </div>

                <div class="stat">

                    <span>
                        Alfa
                    </span>

                    <strong>
                        <?=$summary['Alfa']?>
                    </strong>

                </div>

                <div class="stat">

                    <span>
                        Belum Diisi
                    </span>

                    <strong>
                        <?=$summary['Belum Diisi']?>
                    </strong>

                </div>

            </div>


            <!-- =================================================
                 TABEL
            ================================================== -->

            <?php if (count($students) > 0): ?>

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

                                <th style="width:170px">
                                    Status
                                </th>

                                <th>
                                    Catatan
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($students as $index => $student): ?>

                                <tr>

                                    <td>
                                        <?=($index + 1)?>
                                    </td>

                                    <td>

                                        <strong>
                                            <?=h($student['student_name'])?>
                                        </strong>

                                    </td>

                                    <td>

                                        <?php if ($student['status']): ?>

                                            <span
                                                class="status <?=h(statusClass($student['status']))?>"
                                            >
                                                <?=h($student['status'])?>
                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="status status-empty"
                                            >
                                                Belum Diisi
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?php if (
                                            $student['note'] !== null &&
                                            $student['note'] !== ''
                                        ): ?>

                                            <?=h($student['note'])?>

                                        <?php else: ?>

                                            <span class="muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty-state">

                    Tidak ada siswa yang ditemukan
                    pada kelas ini.

                </div>

            <?php endif; ?>


            <!-- =================================================
                 CATATAN PERTEMUAN
            ================================================== -->

            <?php if ($sessionNote !== ''): ?>

                <div class="note-box">

                    📝

                    <strong>
                        Catatan Pertemuan:
                    </strong>

                    <?=h($sessionNote)?>

                </div>

            <?php endif; ?>


            <!-- INFO -->

            <p
                class="muted"
                style="margin-top:18px"
            >

                Menampilkan
                <?=$totalRecorded?>
                dari
                <?=$totalStudents?>
                siswa yang memiliki status absensi pada
                <?=h($selectedSession)?>.

                Rekap ini hanya mencakup
                <strong>
                    <?=h($selectedClass)?>
                </strong>
                pada tanggal
                <strong>
                    <?=h($formattedDate)?>
                </strong>.

            </p>

        </section>

    <?php endif; ?>


    <!-- FOOTER -->

    <p class="muted">

        EduSpace · English Class · Academic Year 2026/2027

    </p>

</main>

</body>
</html>
