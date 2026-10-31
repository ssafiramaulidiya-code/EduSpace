
<?php
require_once __DIR__ . '/../config/database.php';

$base_url = '/eduspace/';

/* =========================
   DAFTAR QUIZ DAN TUGAS
========================= */

$quiz_list = [
    ['code' => 'quiz1', 'title' => 'Quiz 1'],
    ['code' => 'quiz2', 'title' => 'Quiz 2'],
    ['code' => 'quiz3', 'title' => 'Quiz 3'],
    ['code' => 'quiz4', 'title' => 'Quiz 4'],
    ['code' => 'quiz5', 'title' => 'Quiz 5'],
    ['code' => 'quiz6', 'title' => 'Quiz 6']
];

$task_list = [
    ['code' => 'uts_genap', 'title' => 'UTS Genap'],
    ['code' => 'uts_ganjil', 'title' => 'UTS Ganjil'],
    ['code' => 'project', 'title' => 'Project']
];

$all_activities = array_merge($quiz_list, $task_list);
$valid_codes = array_column($all_activities, 'code');

/* =========================
   SIMPAN STATUS, NILAI, DAN CATATAN
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $selected_class_id = (int)($_POST['class_id'] ?? 0);
    $selected_activity = $_POST['activity'] ?? 'quiz1';

    if (
        $selected_class_id > 0 &&
        in_array($selected_activity, $valid_codes, true)
    ) {
        $stmt = $conn->prepare("
            INSERT INTO quiz_progress
                (student_id, quiz_code, status, score, teacher_note)
            SELECT ?, ?, ?, ?, ?
            FROM students
            WHERE id = ? AND class_id = ?
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                score = COALESCE(VALUES(score), score),
                teacher_note = COALESCE(VALUES(teacher_note), teacher_note)
        ");

        foreach ($_POST['status'][$selected_activity] ?? [] as $student_id => $status) {

            $student_id = (int)$student_id;

            if (
                $status !== 'Submitted' &&
                $status !== 'Not Submitted'
            ) {
                continue;
            }

            $raw_score = trim($_POST['score'][$student_id] ?? '');
            $raw_note = trim($_POST['teacher_note'][$student_id] ?? '');

            $score = ($raw_score === '') ? null : (float)$raw_score;
            $teacher_note = ($raw_note === '') ? null : $raw_note;

            if ($score !== null && ($score < 0 || $score > 100)) {
                continue;
            }

            $stmt->bind_param(
                "issdsii",
                $student_id,
                $selected_activity,
                $status,
                $score,
                $teacher_note,
                $student_id,
                $selected_class_id
            );

            $stmt->execute();
        }

        $stmt->close();

        header(
            "Location: " . $base_url .
            "pages/nilai.php?class_id=" .
            $selected_class_id .
            "&activity=" . urlencode($selected_activity) .
            "&saved=1"
        );

        exit;
    }
}

/* =========================
   AMBIL DAFTAR KELAS
========================= */

$classes = [];

$result_classes = $conn->query("
    SELECT id, class_name
    FROM classes
    ORDER BY class_name
");

while ($row = $result_classes->fetch_assoc()) {
    $classes[] = $row;
}

/* =========================
   PILIH KELAS DAN AKTIVITAS
========================= */

$selected_class_id = isset($_GET['class_id'])
    ? (int)$_GET['class_id']
    : 0;

$selected_activity = $_GET['activity'] ?? 'quiz1';

if (!in_array($selected_activity, $valid_codes, true)) {
    $selected_activity = 'quiz1';
}

$selected_class_name = '';
$selected_activity_title = '';

foreach ($classes as $class) {
    if ((int)$class['id'] === $selected_class_id) {
        $selected_class_name = $class['class_name'];
        break;
    }
}

foreach ($all_activities as $activity) {
    if ($activity['code'] === $selected_activity) {
        $selected_activity_title = $activity['title'];
        break;
    }
}

/* =========================
   REKAP SELURUH KELAS
========================= */

$overview = [];

$stmt_overview = $conn->prepare("
    SELECT
        COUNT(DISTINCT s.id) AS total_students,
        COUNT(DISTINCT CASE
            WHEN qp.status = 'Submitted'
            THEN s.id
        END) AS submitted
    FROM students s
    LEFT JOIN quiz_progress qp
        ON qp.student_id = s.id
        AND qp.quiz_code = ?
    WHERE s.class_id = ?
");

foreach ($classes as $class) {

    $class_id = (int)$class['id'];

    foreach ($all_activities as $activity) {

        $stmt_overview->bind_param(
            "si",
            $activity['code'],
            $class_id
        );

        $stmt_overview->execute();

        $result = $stmt_overview->get_result();
        $data = $result->fetch_assoc();

        $overview[$class_id][$activity['code']] = [
            'total' => (int)$data['total_students'],
            'submitted' => (int)$data['submitted']
        ];
    }
}

$stmt_overview->close();

/* =========================
   AMBIL DATA SISWA
========================= */

$student_details = [];
$total_students = 0;

if ($selected_class_id > 0 && $selected_class_name !== '') {

    $stmt_students = $conn->prepare("
    SELECT
        s.id,
        s.student_name,
        qp.status,
        qp.score,
        qp.teacher_note
    FROM students s
    LEFT JOIN quiz_progress qp
        ON qp.student_id = s.id
        AND qp.quiz_code = ?
    WHERE s.class_id = ?
    ORDER BY s.student_name
");

    $stmt_students->bind_param(
        "si",
        $selected_activity,
        $selected_class_id
    );

    $stmt_students->execute();

    $result_students = $stmt_students->get_result();

    while ($row = $result_students->fetch_assoc()) {

        $student_id = (int)$row['id'];

        $student_details[$student_id] = [
    'name' => $row['student_name'],
    'status' => $row['status'] ?? 'Not Submitted',
    'score' => $row['score'] ?? '',
    'teacher_note' => $row['teacher_note'] ?? ''
];
    }

    $stmt_students->close();

    $total_students = count($student_details);
}

/* =========================
   HITUNG STATUS
========================= */

$submitted_count = 0;
$not_submitted_count = 0;

foreach ($student_details as $student) {

    if ($student['status'] === 'Submitted') {
        $submitted_count++;
    } else {
        $not_submitted_count++;
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Nilai | EduSpace</title>

<style>
    .topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 28px;
}

.topbar h1 {
    color: #073b5c;
    font-size: 30px;
    margin-bottom: 7px;
}

.topbar p {
    color: #718096;
    font-size: 14px;
}

.teacher {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.teacher-info {
    text-align: right;
}

.teacher-info h4 {
    font-size: 13px;
}

.teacher-info p {
    font-size: 11px;
    color: #8193a9;
}

.logout-link {
    display: inline-block;
    margin-top: 3px;
    color: #b34b4b;
    font-size: 10px;
    text-decoration: none;
    font-weight: 600;
}

.logout-link:hover {
    color: #8f2929;
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
}

@media (max-width: 650px) {
    .topbar {
        align-items: flex-start;
        flex-direction: column;
    }

    .teacher {
        align-self: flex-end;
    }
}
    
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

body {
    background: #f5f3ee;
    color: #26364d;
}

/* SIDEBAR */

.sidebar {
    width: 250px;
    height: 100vh;
    background: #003049;
    position: fixed;
    left: 0;
    top: 0;
    padding: 35px 24px;
    color: white;
    overflow-y: auto;
    z-index: 1000;
}

.school-logo {
    text-align: center;
    padding-bottom: 28px;
    border-bottom: 1px solid rgba(255,255,255,0.25);
    margin-bottom: 30px;
}

.school-logo img {
    width: 150px;
    height: 150px;
    object-fit: contain;
    margin-bottom: 15px;
}

.school-logo h2 {
    color: #ffca8a;
    font-size: 25px;
    margin-bottom: 8px;
}

.school-logo p {
    font-size: 10px;
    letter-spacing: 1px;
    color: #dce8f2;
}

.menu-title {
    color: #b7cbdc;
    font-size: 11px;
    font-weight: bold;
    letter-spacing: 1.5px;
    margin: 0 10px 18px;
}

.menu a {
    display: block;
    text-decoration: none;
    color: white;
    padding: 14px 15px;
    margin-bottom: 8px;
    border-radius: 10px;
    font-size: 14px;
    transition: 0.3s;
    white-space: nowrap;
}

.menu a:hover,
.menu a.active {
    background: #205778;
}

/* MAIN */

.main {
    margin-left: 250px;
    width: calc(100% - 250px);
    min-height: 100vh;
    padding: 35px 40px;
}

.topbar {
    margin-bottom: 28px;
}

.topbar h1 {
    color: #073b5c;
    font-size: 30px;
    margin-bottom: 7px;
}

.topbar p {
    color: #718096;
    font-size: 14px;
}

.section-title {
    color: #073b5c;
    font-size: 20px;
    margin-bottom: 17px;
}

/* REKAP */

.table-card {
    background: white;
    border-radius: 16px;
    padding: 22px;
    box-shadow: 0 5px 18px rgba(0,0,0,0.05);
    overflow-x: auto;
    margin-bottom: 28px;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 650px;
}

th {
    background: #edf4f8;
    color: #205778;
    padding: 15px 12px;
    font-size: 13px;
    text-align: center;
}

th:first-child {
    text-align: left;
}

td {
    padding: 15px 12px;
    text-align: center;
    border-bottom: 1px solid #edf0f3;
    font-size: 13px;
}

td:first-child {
    text-align: left;
    font-weight: 600;
    color: #073b5c;
}

.progress-status {
    display: inline-block;
    min-width: 65px;
    padding: 8px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
}

.complete {
    background: #e8f7ee;
    color: #18794e;
}

.incomplete {
    background: #fff0f0;
    color: #b33a3a;
}

.no-students {
    background: #f0f2f4;
    color: #718096;
}

/* FILTER */

.filter-card {
    background: white;
    border-radius: 16px;
    padding: 22px;
    margin-bottom: 25px;
    box-shadow: 0 5px 18px rgba(0,0,0,0.05);
}

.filter-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.filter-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #52657a;
    margin-bottom: 8px;
}

.filter-group select {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #d5dde5;
    border-radius: 10px;
    background: white;
    color: #26364d;
    font-size: 14px;
}

/* CARD */

.quiz-card {
    background: white;
    border-radius: 16px;
    margin-bottom: 18px;
    overflow: hidden;
    box-shadow: 0 5px 18px rgba(0,0,0,0.05);
}

.quiz-header {
    padding: 21px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid #edf0f3;
}

.quiz-header h2 {
    color: #073b5c;
    font-size: 17px;
}

.quiz-header span {
    font-size: 12px;
    font-weight: 700;
    padding: 8px 12px;
    border-radius: 20px;
}

.quiz-content {
    padding: 20px 24px;
}

.quiz-summary {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 15px;
}

.summary-item {
    background: #f5f7f9;
    padding: 12px 15px;
    border-radius: 9px;
    font-size: 13px;
}

.summary-item strong {
    color: #073b5c;
}

.student-list-title {
    font-size: 13px;
    font-weight: 700;
    color: #52657a;
    margin: 18px 0 10px;
}

.student-row {
    display: grid;
    grid-template-columns: minmax(150px, 1.2fr) 170px 110px minmax(180px, 1fr);
    align-items: center;
    gap: 14px;
    padding: 13px 0;
    border-bottom: 1px solid #edf0f3;
}

.student-row:last-child {
    border-bottom: none;
}

.score-input,
.note-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d6dde4;
    border-radius: 9px;
    font-size: 13px;
    background: white;
    color: #26364d;
}

.score-input {
    text-align: center;
}

.note-input {
    min-width: 0;
}

@media (max-width: 1000px) {
    .student-row {
        grid-template-columns: 1fr;
        gap: 8px;
    }
}

.student-name {
    font-size: 14px;
    font-weight: 500;
}

.status-select {
    width: 100%;
    padding: 10px 12px;
    border-radius: 9px;
    font-size: 13px;
    border: 1px solid #d6dde4;
    background: white;
}

.status-select.submitted {
    border-color: #8ac9a8;
    background: #eefaf3;
    color: #18794e;
}

.status-select.not-submitted {
    border-color: #e6a2a2;
    background: #fff3f3;
    color: #b33a3a;
}

/* SAVE */

.save-area {
    position: sticky;
    bottom: 20px;
    display: flex;
    justify-content: flex-end;
    margin-top: 15px;
}

.save-button {
    border: none;
    background: #003b5c;
    color: white;
    padding: 14px 25px;
    border-radius: 11px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(0,59,92,0.2);
}

.save-button:hover {
    background: #205778;
}

.success {
    background: #e8f7ee;
    color: #18794e;
    padding: 13px 16px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
}

.empty {
    background: white;
    border-radius: 16px;
    padding: 40px;
    text-align: center;
    color: #718096;
    box-shadow: 0 5px 18px rgba(0,0,0,0.05);
}

.empty h2 {
    color: #073b5c;
    margin-bottom: 10px;
}

@media (max-width: 800px) {

   .sidebar {
    width: 210px;
    padding: 25px 15px;
    display: flex;
    flex-direction: column;
}

.sidebar-footer {
    margin-top: auto;
    padding: 20px 10px;
    text-align: center;
    color: #718096;
    font-size: 12px;
}

    .main {
        margin-left: 210px;
        width: calc(100% - 210px);
        padding: 25px 20px;
    }

    .student-row {
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .filter-row {
        grid-template-columns: 1fr;
    }
}

</style>

</head>

<body>

<aside class="sidebar">

    <div class="school-logo">

        <img
            src="<?= $base_url ?>assets/logo-sekolah.png"
            alt="Logo Sekolah"
        >

        <h2>EduSpace</h2>

        <p>LEARNING MANAGEMENT SYSTEM</p>

    </div>

    <div class="menu-title">
        MAIN MENU
    </div>

    <nav class="menu">

        <a href="<?= $base_url ?>index.php">🏠 Home</a>

        <a href="<?= $base_url ?>pages/materi.php">📚 Materi</a>

        <a href="<?= $base_url ?>pages/program.php">🗓️ Program Akademik</a>

        <a href="<?= $base_url ?>pages/absensi-guru.php">📋 Input Absensi</a>

        <a href="<?= $base_url ?>pages/rekap-absensi.php">📊 Rekap Absensi</a>

        <a href="<?= $base_url ?>pages/diskusi.php">💬 Ruang Diskusi</a>

        <a href="<?= $base_url ?>pages/tugas.php">📝 Tugas & Quiz</a>

        <a href="<?= $base_url ?>pages/nilai.php" class="active">📊 Nilai</a>

    </nav>

    <div class="sidebar-footer">
        Learn, Explore, Achieve. ✨
    </div>

</aside>


<main class="main">
    <header class="topbar">
    <div>
        <h1>Rekapitulasi Nilai</h1>
        <p>Pantau dan perbarui perkembangan pengumpulan quiz dan tugas seluruh kelas.</p>
    </div>

    <div class="teacher">
        <div class="teacher-info">
            <h4>Safira Maulidiya, M.Pd.</h4>
            <p>English Teacher</p>
        </div>

        <div class="teacher-avatar">S</div>
    </div>
</header>

    <?php if (isset($_GET['saved'])): ?>

        <div class="success">
            ✓ Status pengumpulan berhasil diperbarui.
        </div>

    <?php endif; ?>


    <!-- REKAP QUIZ -->

    <h2 class="section-title">
        Rekap Pengumpulan Quiz Seluruh Kelas
    </h2>

    <div class="table-card">

        <table>

            <thead>
                <tr>
                    <th>Kelas</th>

                    <?php foreach ($quiz_list as $quiz): ?>
                        <th><?= htmlspecialchars($quiz['title']) ?></th>
                    <?php endforeach; ?>

                </tr>
            </thead>

            <tbody>

                <?php foreach ($classes as $class): ?>

                    <?php $class_id = (int)$class['id']; ?>

                    <tr>

                        <td><?= htmlspecialchars($class['class_name']) ?></td>

                        <?php foreach ($quiz_list as $quiz): ?>

                            <?php
                            $total = $overview[$class_id][$quiz['code']]['total'];
                            $submitted = $overview[$class_id][$quiz['code']]['submitted'];

                            $status_class = $total === 0
                                ? 'no-students'
                                : ($submitted === $total ? 'complete' : 'incomplete');
                            ?>

                            <td>
                                <span class="progress-status <?= $status_class ?>">
                                    <?= $submitted ?>/<?= $total ?>
                                </span>
                            </td>

                        <?php endforeach; ?>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>


    <!-- REKAP TUGAS -->

    <h2 class="section-title">
        Rekap Pengumpulan Tugas Seluruh Kelas
    </h2>

    <div class="table-card">

        <table>

            <thead>
                <tr>
                    <th>Kelas</th>

                    <?php foreach ($task_list as $task): ?>
                        <th><?= htmlspecialchars($task['title']) ?></th>
                    <?php endforeach; ?>

                </tr>
            </thead>

            <tbody>

                <?php foreach ($classes as $class): ?>

                    <?php $class_id = (int)$class['id']; ?>

                    <tr>

                        <td><?= htmlspecialchars($class['class_name']) ?></td>

                        <?php foreach ($task_list as $task): ?>

                            <?php
                            $total = $overview[$class_id][$task['code']]['total'];
                            $submitted = $overview[$class_id][$task['code']]['submitted'];

                            $status_class = $total === 0
                                ? 'no-students'
                                : ($submitted === $total ? 'complete' : 'incomplete');
                            ?>

                            <td>
                                <span class="progress-status <?= $status_class ?>">
                                    <?= $submitted ?>/<?= $total ?>
                                </span>
                            </td>

                        <?php endforeach; ?>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>


    <!-- DETAIL PENGUMPULAN -->

    <h2 class="section-title">
        Detail Pengumpulan
    </h2>

    <form method="GET" action="nilai.php">

        <div class="filter-card">

            <div class="filter-row">

                <div class="filter-group">

                    <label for="class_id">Pilih Kelas</label>

                    <select
                        name="class_id"
                        id="class_id"
                        onchange="this.form.submit()"
                    >

                        <option value="">-- Pilih Kelas --</option>

                        <?php foreach ($classes as $class): ?>

                            <option
                                value="<?= (int)$class['id'] ?>"
                                <?= $selected_class_id == $class['id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($class['class_name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="filter-group">

                    <label for="activity">Pilih Quiz atau Tugas</label>

                    <select
                        name="activity"
                        id="activity"
                        onchange="this.form.submit()"
                    >

                        <optgroup label="Quiz">

                            <?php foreach ($quiz_list as $quiz): ?>

                                <option
                                    value="<?= htmlspecialchars($quiz['code']) ?>"
                                    <?= $selected_activity === $quiz['code'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($quiz['title']) ?>
                                </option>

                            <?php endforeach; ?>

                        </optgroup>

                        <optgroup label="Tugas">

                            <?php foreach ($task_list as $task): ?>

                                <option
                                    value="<?= htmlspecialchars($task['code']) ?>"
                                    <?= $selected_activity === $task['code'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($task['title']) ?>
                                </option>

                            <?php endforeach; ?>

                        </optgroup>

                    </select>

                </div>

            </div>

        </div>

    </form>


    <?php if ($selected_class_id > 0 && $selected_class_name !== ''): ?>

        <div style="margin-bottom:20px;">

            <h2 style="color:#073b5c;font-size:21px;">
                Kelas <?= htmlspecialchars($selected_class_name) ?>
            </h2>

            <p style="color:#718096;font-size:13px;margin-top:6px;">
                Total siswa: <?= $total_students ?> orang
            </p>

        </div>


        <!-- DETAIL AKTIVITAS TERPILIH -->

        <form method="POST" action="nilai.php">

            <input
                type="hidden"
                name="class_id"
                value="<?= $selected_class_id ?>"
            >

            <input
                type="hidden"
                name="activity"
                value="<?= htmlspecialchars($selected_activity) ?>"
            >

            <?php

            $is_complete = $total_students > 0 &&
                $submitted_count === $total_students;

            ?>

            <section class="quiz-card">

                <div class="quiz-header">

                    <h2>
                        <?= htmlspecialchars($selected_activity_title) ?>
                    </h2>

                    <span class="<?= $is_complete ? 'complete' : 'incomplete' ?>">
                        <?= $submitted_count ?>/<?= $total_students ?> Submitted
                    </span>

                </div>

                <div class="quiz-content">

                    <div class="quiz-summary">

                        <div class="summary-item">
                            Sudah mengumpulkan:
                            <strong><?= $submitted_count ?> siswa</strong>
                        </div>

                        <div class="summary-item">
                            Belum mengumpulkan:
                            <strong><?= $not_submitted_count ?> siswa</strong>
                        </div>

                    </div>

                    <div class="student-list-title">
                        Perbarui Status Pengumpulan Siswa
                    </div>

                    <?php if ($total_students === 0): ?>

                        <p class="empty">
                            Belum ada siswa pada kelas ini.
                        </p>

                    <?php else: ?>

                        <?php foreach ($student_details as $student_id => $student): ?>

                            <?php
                            $status = $student['status'];
                            ?>

                            <div class="student-row">

    <div class="student-name">
        <?= htmlspecialchars($student['name']) ?>
    </div>

    <select
        name="status[<?= htmlspecialchars($selected_activity) ?>][<?= (int)$student_id ?>]"
        class="status-select <?= $student['status'] === 'Submitted'
            ? 'submitted'
            : 'not-submitted' ?>"
        onchange="
            this.classList.toggle('submitted', this.value === 'Submitted');
            this.classList.toggle('not-submitted', this.value === 'Not Submitted');
        "
    >
        <option
            value="Submitted"
            <?= $student['status'] === 'Submitted' ? 'selected' : '' ?>
        >
            Submitted
        </option>

        <option
            value="Not Submitted"
            <?= $student['status'] === 'Not Submitted' ? 'selected' : '' ?>
        >
            Not Submitted
        </option>
    </select>

    <input
        type="number"
        name="score[<?= (int)$student_id ?>]"
        class="score-input"
        min="0"
        max="100"
        step="0.01"
        placeholder="Nilai"
        value="<?= htmlspecialchars((string)$student['score']) ?>"
    >

    <input
        type="text"
        name="teacher_note[<?= (int)$student_id ?>]"
        class="note-input"
        placeholder="Catatan nilai (opsional)"
        value="<?= htmlspecialchars($student['teacher_note']) ?>"
    >

</div>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </section>

            <?php if ($total_students > 0): ?>

                <div class="save-area">

                    <button type="submit" class="save-button">
                        💾 Save Progress
                    </button>

                </div>

            <?php endif; ?>

        </form>

    <?php else: ?>

        <div class="empty">

            <h2>Choose a Class</h2>

            <p>
                Pilih kelas untuk melihat dan memperbarui status pengumpulan quiz dan tugas siswa.
            </p>

        </div>

    <?php endif; ?>

</main>

</body>
</html>
