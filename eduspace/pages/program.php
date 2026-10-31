<?php
require_once '../config/database.php';

/* =========================
   HELPER
========================= */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function jsArg($value) {
    return e(json_encode(
        (string)$value,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ));
}

$message = '';
$message_type = '';

/* =========================
   SIMPAN / EDIT PROGRAM
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_program'])) {

    $id = (int)($_POST['id'] ?? 0);
    $category = $_POST['category'] ?? '';
    $chapter = trim($_POST['chapter'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $url = trim($_POST['url'] ?? '');

    $allowed_categories = [
        'syllabus',
        'calendar',
        'schedule',
        'module',
        'agreement'
    ];

    if (
        !in_array($category, $allowed_categories, true) ||
        $title === '' ||
        !filter_var($url, FILTER_VALIDATE_URL)
    ) {
        $message = 'Periksa kembali judul, kategori, dan link dokumen.';
        $message_type = 'error';

    } else {

        if ($category !== 'module') {
            $chapter = '';
        }

        if ($id > 0) {

            $stmt = $conn->prepare("
                UPDATE academic_programs
                SET category=?, chapter=?, title=?, description=?, url=?
                WHERE id=?
            ");

            $stmt->bind_param(
                "sssssi",
                $category,
                $chapter,
                $title,
                $description,
                $url,
                $id
            );

        } else {

            $stmt = $conn->prepare("
                INSERT INTO academic_programs
                (category, chapter, title, description, url)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "sssss",
                $category,
                $chapter,
                $title,
                $description,
                $url
            );
        }

        if ($stmt->execute()) {
            header("Location: program.php?status=saved");
            exit;
        } else {
            $message = 'Data gagal disimpan.';
            $message_type = 'error';
        }
    }
}

/* =========================
   HAPUS PROGRAM
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_program'])) {

    $id = (int)($_POST['id'] ?? 0);

    $stmt = $conn->prepare("
        DELETE FROM academic_programs
        WHERE id=?
    ");

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: program.php?status=deleted");
        exit;
    } else {
        $message = 'Data gagal dihapus.';
        $message_type = 'error';
    }
}

/* =========================
   NOTIFIKASI
========================= */
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'saved') {
        $message = 'Program akademik berhasil disimpan!';
        $message_type = 'success';
    } elseif ($_GET['status'] === 'deleted') {
        $message = 'Program akademik berhasil dihapus!';
        $message_type = 'success';
    }
}

/* =========================
   AMBIL DATA DATABASE
========================= */
$programs = [];

$result = $conn->query("
    SELECT * FROM academic_programs
    ORDER BY id ASC
");

while ($row = $result->fetch_assoc()) {
    $programs[$row['category']][] = $row;
}

$syllabus = $programs['syllabus'][0] ?? null;
$calendar = $programs['calendar'][0] ?? null;
$schedule = $programs['schedule'][0] ?? null;
$agreement = $programs['agreement'][0] ?? null;
$modules = $programs['module'] ?? [];

/* =========================
   FUNGSI TAMPILAN RESOURCE
========================= */
function renderResource($data, $category, $icon, $buttonText) {

    if (!$data) {
        return;
    }

    $id = (int)$data['id'];
    $title = jsArg($data['title']);
    $description = jsArg($data['description']);
    $url = jsArg($data['url']);
    $categoryJs = jsArg($category);

    echo '
    <div class="resource-card">

        <div class="resource-info">
            <div class="resource-icon">' . $icon . '</div>

            <div>
                <h3>' . e($data['title']) . '</h3>
                <p>' . e($data['description']) . '</p>
            </div>
        </div>

        <div class="resource-actions">

            <a class="btn"
               href="' . e($data['url']) . '"
               target="_blank"
               rel="noopener noreferrer">
               ' . e($buttonText) . ' ↗
            </a>

            <button class="btn-edit"
                type="button"
                onclick="openEditModal(
                    ' . $id . ',
                    ' . $categoryJs . ',
                    \'\',
                    ' . $title . ',
                    ' . $description . ',
                    ' . $url . '
                )">
                Edit
            </button>

            <form method="POST"
                  onsubmit="return confirm(\'Yakin ingin menghapus data ini?\')">

                <input type="hidden" name="id" value="' . $id . '">

                <button type="submit"
                        class="btn-delete"
                        name="delete_program">
                    Delete
                </button>
            </form>

        </div>
    </div>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Academic Programs | EduSpace</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

html {
    overflow-x: hidden;
}

body {
    background: #f5f3ee;
    color: #26364d;
    overflow-x: hidden;
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
    overflow-x: hidden;
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
    color: white;
}

.sidebar-bottom {
    text-align: center;
    color: #ffca8a;
    font-size: 13px;
    line-height: 1.8;
    margin-top: 45px;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.2);
}

/* MAIN */
.main {
    margin-left: 250px;
    width: calc(100% - 250px);
    min-width: 0;
    min-height: 100vh;
    padding: 30px 40px;
    position: relative;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
}

.topbar h2 {
    color: #073b5c;
    font-size: 24px;
}

.topbar p {
    color: #718096;
    font-size: 13px;
    margin-top: 4px;
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

.hero {
    background: linear-gradient(120deg, #003b5c, #087ca3);
    padding: 38px;
    border-radius: 18px;
    color: white;
    margin-bottom: 28px;
    width: 100%;
}

.hero .label {
    color: #ffca8a;
    font-size: 12px;
    letter-spacing: 2px;
    font-weight: bold;
    margin-bottom: 13px;
}

.hero h1 {
    font-size: 30px;
    line-height: 1.3;
    margin-bottom: 12px;
    overflow-wrap: anywhere;
}

.hero h3 {
    font-size: 16px;
    margin-bottom: 12px;
    color: #e5d9c4;
}

.hero p {
    font-size: 14px;
    line-height: 1.8;
    color: #e5ebf3;
    max-width: 850px;
}

.section {
    background: white;
    padding: 27px;
    border-radius: 15px;
    margin-bottom: 23px;
    box-shadow: 0 4px 15px rgba(23,43,77,.05);
    width: 100%;
    min-width: 0;
}

.section-heading {
    margin-bottom: 20px;
}

.section-heading span {
    color: #b29461;
    font-size: 12px;
    font-weight: bold;
    letter-spacing: 1px;
}

.section-heading h2 {
    font-size: 21px;
    line-height: 1.4;
    margin-top: 5px;
    color: #172b4d;
}

.section-heading p {
    color: #778397;
    font-size: 13px;
    line-height: 1.7;
    margin-top: 7px;
}

/* PROFILE */
.profile {
    display: flex;
    gap: 25px;
    align-items: flex-start;
}

.profile-icon {
    flex: 0 0 105px;
    height: 105px;
    background: #e9e2d4;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 42px;
}

.profile-content {
    min-width: 0;
}

.profile-content h3 {
    color: #172b4d;
    font-size: 19px;
    margin-bottom: 5px;
}

.profile-content .position {
    color: #a28554;
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 13px;
}

.profile-content p {
    color: #667085;
    font-size: 14px;
    line-height: 1.8;
    margin-bottom: 10px;
}

.quote {
    background: #f5f1e8;
    padding: 15px 18px;
    border-left: 4px solid #b29461;
    border-radius: 7px;
    color: #34445b;
    font-style: italic;
    margin-top: 15px;
    line-height: 1.7;
    font-size: 14px;
}

/* RESOURCE */
.resource-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    padding: 18px;
    border: 1px solid #e9e6df;
    border-radius: 12px;
    margin-bottom: 13px;
    transition: .3s;
    min-width: 0;
}

.resource-card:last-child {
    margin-bottom: 0;
}

.resource-card:hover {
    border-color: #9aabc0;
    transform: translateY(-2px);
}

.resource-info {
    display: flex;
    align-items: center;
    gap: 15px;
    min-width: 0;
}

.resource-icon {
    flex: 0 0 48px;
    width: 48px;
    height: 48px;
    background: #edf1f6;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 23px;
}

.resource-info h3 {
    font-size: 15px;
    color: #26364d;
    margin-bottom: 5px;
}

.resource-info p {
    font-size: 12px;
    line-height: 1.6;
    color: #8490a1;
}

.btn {
    display: inline-block;
    text-decoration: none;
    background: #203c63;
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 12px;
    white-space: nowrap;
    transition: .3s;
    flex-shrink: 0;
}

.btn:hover {
    background: #b29461;
}

/* MODULE */
.module-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 15px;
}

.module-card {
    border: 1px solid #e9e6df;
    border-radius: 12px;
    padding: 20px;
    background: #fff;
    min-width: 0;
}

.module-card .chapter {
    color: #a28554;
    font-size: 12px;
    font-weight: bold;
    letter-spacing: 1px;
}

.module-card h3 {
    color: #172b4d;
    font-size: 16px;
    margin: 10px 0;
}

.module-card p {
    color: #778397;
    font-size: 13px;
    line-height: 1.6;
    min-height: 42px;
    margin-bottom: 18px;
}

.agreement {
    background: #f5f1e8;
    border: 1px solid #e9dfcb;
}

footer {
    text-align: center;
    color: #8a94a3;
    font-size: 12px;
    line-height: 1.7;
    padding: 20px;
}

/* EDITABLE FEATURES */
.btn-add {
    background: #b29461;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 12px;
    font-weight: bold;
}

.btn-edit {
    background: #31577e;
    color: white;
    border: none;
    padding: 9px 12px;
    border-radius: 7px;
    cursor: pointer;
    font-size: 12px;
}

.btn-delete {
    background: #b94a48;
    color: white;
    border: none;
    padding: 9px 12px;
    border-radius: 7px;
    cursor: pointer;
    font-size: 12px;
}

.btn-add:hover,
.btn-edit:hover,
.btn-delete:hover {
    opacity: .85;
}

.resource-actions {
    display: flex;
    gap: 7px;
    align-items: center;
    flex-wrap: wrap;
}

.resource-actions form {
    margin: 0;
}

.program-notice {
    padding: 13px 17px;
    border-radius: 9px;
    margin-bottom: 20px;
    font-size: 13px;
}

.program-notice.success {
    background: #e6f4ea;
    color: #276738;
}

.program-notice.error {
    background: #fce8e6;
    color: #a33b35;
}

.program-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.55);
    z-index: 2000;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.program-modal.show {
    display: flex;
}

.program-form {
    background: white;
    width: 100%;
    max-width: 520px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 28px;
    border-radius: 15px;
}

.program-form h2 {
    color: #172b4d;
    margin-bottom: 20px;
}

.program-form label {
    display: block;
    font-size: 13px;
    font-weight: bold;
    margin: 13px 0 7px;
    color: #34445b;
}

.program-form input,
.program-form select,
.program-form textarea {
    width: 100%;
    padding: 11px;
    border: 1px solid #d8dee8;
    border-radius: 8px;
    font-size: 13px;
}

.program-form textarea {
    resize: vertical;
    min-height: 85px;
}

.form-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 22px;
}

.btn-cancel {
    background: #e9e6df;
    color: #34445b;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
}

/* TABLET */
@media (max-width: 900px) {
    .sidebar {
        width: 220px;
        padding-left: 18px;
        padding-right: 18px;
    }

    .main {
        margin-left: 220px;
        width: calc(100% - 220px);
        padding: 25px;
    }

    .module-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .hero {
        padding: 30px;
    }

    .hero h1 {
        font-size: 26px;
    }
}

/* MOBILE */
@media (max-width: 650px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        max-height: none;
        padding: 22px 18px;
    }

    .school-logo {
        padding-bottom: 18px;
        margin-bottom: 20px;
    }

    .school-logo img {
        width: 70px;
        height: 70px;
    }

    .menu a {
        padding: 11px 13px;
    }

    .sidebar-bottom {
        margin-top: 20px;
    }

    .main {
        margin-left: 0;
        width: 100%;
        padding: 20px 16px;
    }

    .topbar {
        align-items: flex-start;
        flex-wrap: wrap;
        margin-bottom: 22px;
    }

    .topbar h2 {
        font-size: 21px;
    }

    .hero {
        padding: 24px 20px;
    }

    .hero h1 {
        font-size: 23px;
    }

    .profile {
        flex-direction: column;
    }

    .resource-card {
        align-items: flex-start;
        flex-direction: column;
    }

    .module-grid {
        grid-template-columns: 1fr;
    }

    .section {
        padding: 21px 18px;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<aside class="sidebar">

    <div class="school-logo">
        <img src="../assets/logo-sekolah.png" alt="Logo SMA Bangsa Utama">
        <h2>EduSpace.</h2>
        <p>LEARNING MANAGEMENT SYSTEM</p>
    </div>

    <p class="menu-title">MAIN MENU</p>

    <?php $base_url = '/eduspace/'; ?>

    <nav class="menu">
        <a href="<?= $base_url ?>index.php">🏠 Home</a>
        <a href="<?= $base_url ?>pages/materi.php">📚 Materi</a>
        <a href="<?= $base_url ?>pages/program.php" class="active">🗓️ Program Akademik</a>
        <a href="<?= $base_url ?>pages/absensi-guru.php">📋 Input Absensi</a>
        <a href="<?= $base_url ?>pages/rekap-absensi.php">📊 Rekap Absensi</a>
        <a href="<?= $base_url ?>pages/diskusi.php">💬 Ruang Diskusi</a>
        <a href="<?= $base_url ?>pages/tugas.php">📝 Tugas & Quiz</a>
        <a href="<?= $base_url ?>pages/nilai.php">📊 Nilai</a>
    </nav>

    <div class="sidebar-bottom">
        Learn, Explore,<br>Achieve. ✨
    </div>

</aside>

<!-- MAIN CONTENT -->
<main class="main">

    <div class="topbar">
        <div>
            <h2>Academic Programs</h2>
            <p>Explore your learning journey and academic information.</p>
        </div>

        <div class="teacher">
    <div class="teacher-info">
        <h4>Safira Maulidiya, M.Pd.</h4>
        <p>English Teacher</p>
    </div>

    <div class="teacher-avatar">S</div>
</div>
    </div>

    <?php if ($message): ?>
        <div class="program-notice <?= e($message_type) ?>">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <!-- HERO -->
    <section class="hero">
        <div class="label">EDUSPACE • ACADEMIC YEAR 2026/2027</div>

        <h1>ACADEMIC PROGRAMS &amp; COURSE OVERVIEW</h1>

        <h3>English Grade 10 — SMA Utama Bangsa</h3>

        <p>
            Welcome to our learning hub! Explore our curriculum map,
            grading policy, syllabus, and classroom agreement.
            Let's make this academic year meaningful, productive,
            and full of exciting learning experiences! 🚀✨
        </p>
    </section>

    <!-- SECTION 01 -->
    <section class="section">

        <div class="section-heading">
            <span>SECTION 01 • TEACHER PROFILE</span>
            <h2>👩‍🏫 Meet Your English Teacher!</h2>
            <p>Get to know the person who will guide your English learning journey.</p>
        </div>

        <div class="profile">
            <div class="profile-icon">👩🏻‍🏫</div>

            <div class="profile-content">

                <h3>Safira Maulidiya, M.Pd.</h3>

                <div class="position">English Teacher | Grade 10</div>

                <p>
                    Halo semuanya! <i>Welcome to Grade 10 English Class!</i>
                    Kelas Bahasa Inggris tahun ajaran ini diampu oleh
                    <strong>Safira Maulidiya, M.Pd.</strong> Beliau merupakan
                    lulusan S-1 dan S-2 Pendidikan Bahasa Inggris dari
                    <strong>UIN Syarif Hidayatullah Jakarta.</strong>
                </p>

                <p>
                    <i>With a deep passion for language education</i>,
                    Bu Safira berkomitmen untuk menciptakan suasana belajar
                    yang interaktif, menyenangkan, dan <i>supportive</i>
                    untuk membantu kalian mengeksplorasi kemampuan Bahasa Inggris—
                    baik <i>speaking, listening, reading,</i> maupun <i>writing.</i>
                </p>

                <p>
                    Jika kalian punya pertanyaan seputar materi, butuh teman
                    diskusi, atau ingin konsultasi tugas,
                    <i>don't hesitate to reach out!</i>
                </p>

                <div class="quote">
                    "English is not just a subject to study, but a tool to
                    explore the world!"<br>
                    Let's make this academic year exciting and full of progress together! 🚀
                </div>

            </div>
        </div>

    </section>

    <!-- SECTION 02 -->
    <section class="section">

        <div class="section-heading">
            <span>SECTION 02 • CURRICULUM</span>
            <h2>🎯 Learning Objectives &amp; Syllabus Mapping</h2>
            <p>
                Understand what you will learn and the competencies
                you are expected to achieve throughout the academic year.
            </p>
        </div>

        <?php
        renderResource(
            $syllabus,
            'syllabus',
            '🎯',
            'View Syllabus'
        );
        ?>

        <button class="btn-add" type="button"
                onclick="openAddModal('syllabus')">
            + Add Syllabus
        </button>

    </section>

    <!-- SECTION 03 -->
    <section class="section">

        <div class="section-heading">
            <span>SECTION 03 • ACADEMIC SCHEDULE</span>
            <h2>📅 Academic &amp; English Course Calendars</h2>
            <p>
                Stay organized! Check the school calendar and your
                English subject schedule to keep track of learning activities.
            </p>
        </div>

        <?php
        renderResource(
            $calendar,
            'calendar',
            '🔴',
            'View Calendar'
        );

        renderResource(
            $schedule,
            'schedule',
            '🔵',
            'View Schedule'
        );
        ?>

        <button class="btn-add" type="button"
                onclick="openAddModal('calendar')">
            + Add Calendar / Schedule
        </button>

    </section>

    <!-- SECTION 04 -->
    <section class="section">

        <div class="section-heading">
            <span>SECTION 04 • TEACHING MATERIALS</span>
            <h2>📊 Teaching Modules</h2>
            <p>
                Access the teaching modules for each chapter as your
                learning guide throughout the semester.
            </p>
        </div>

        <div class="manage-header" style="margin-bottom:18px;">
            <button class="btn-add" type="button"
                    onclick="openAddModal('module')">
                + Add Teaching Module
            </button>
        </div>

        <div class="module-grid">

            <?php foreach ($modules as $module): ?>

                <div class="module-card">

                    <div class="chapter">
                        <?= e($module['chapter']) ?>
                    </div>

                    <h3><?= e($module['title']) ?></h3>

                    <p><?= e($module['description']) ?></p>

                    <div class="resource-actions">

                        <a class="btn"
                           href="<?= e($module['url']) ?>"
                           target="_blank"
                           rel="noopener noreferrer">
                            Open Module ↗
                        </a>

                        <button class="btn-edit"
                            type="button"
                            onclick="openEditModal(
                                <?= (int)$module['id'] ?>,
                                'module',
                                <?= jsArg($module['chapter']) ?>,
                                <?= jsArg($module['title']) ?>,
                                <?= jsArg($module['description']) ?>,
                                <?= jsArg($module['url']) ?>
                            )">
                            Edit
                        </button>

                        <form method="POST"
                              onsubmit="return confirm('Yakin ingin menghapus modul ini?')">

                            <input type="hidden"
                                   name="id"
                                   value="<?= (int)$module['id'] ?>">

                            <button class="btn-delete"
                                    type="submit"
                                    name="delete_program">
                                Delete
                            </button>

                        </form>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </section>

    <!-- SECTION 05 -->
    <section class="section agreement">

        <div class="section-heading">
            <span>SECTION 05 • CLASSROOM RULES</span>
            <h2>📜 Classroom Agreement</h2>
            <p>
                A positive learning environment starts with mutual respect,
                responsibility, and active participation.
            </p>
        </div>

        <?php
        renderResource(
            $agreement,
            'agreement',
            '🤝',
            'Read Agreement'
        );
        ?>

        <button class="btn-add" type="button"
                onclick="openAddModal('agreement')">
            + Add Classroom Agreement
        </button>

    </section>

    <footer>
        © 2026 EduSpace — SMA Bangsa Utama
        <br>
        Designed for Grade 10 English Learning
        <br><br>
        Warm regards, Bu Safira Maulidiya, M.Pd. 💙
    </footer>

</main>

<!-- MODAL TAMBAH / EDIT -->
<div class="program-modal" id="programModal">

    <form class="program-form" method="POST">

        <h2 id="modalTitle">Tambah Program Akademik</h2>

        <input type="hidden" name="id" id="programId">

        <label>Kategori</label>

        <select name="category" id="programCategory" required>
            <option value="syllabus">Syllabus</option>
            <option value="calendar">Academic Calendar</option>
            <option value="schedule">English Schedule</option>
            <option value="module">Teaching Module</option>
            <option value="agreement">Classroom Agreement</option>
        </select>

        <div id="chapterField">

            <label>Chapter</label>

            <input type="text"
                   name="chapter"
                   id="programChapter"
                   placeholder="Contoh: Chapter 4">

        </div>

        <label>Judul</label>

        <input type="text"
               name="title"
               id="programTitle"
               required>

        <label>Deskripsi</label>

        <textarea name="description"
                  id="programDescription"></textarea>

        <label>Link Google Drive / Dokumen</label>

        <input type="url"
               name="url"
               id="programUrl"
               placeholder="https://..."
               required>

        <div class="form-buttons">

            <button type="button"
                    class="btn-cancel"
                    onclick="closeProgramModal()">
                Cancel
            </button>

            <button type="submit"
                    name="save_program"
                    class="btn-add">
                Save Program
            </button>

        </div>

    </form>

</div>

<script>
const modal = document.getElementById('programModal');
const category = document.getElementById('programCategory');
const chapterField = document.getElementById('chapterField');

function updateChapterField() {
    chapterField.style.display =
        category.value === 'module' ? 'block' : 'none';
}

category.addEventListener('change', updateChapterField);

function openAddModal(defaultCategory = 'module') {

    document.getElementById('modalTitle').textContent =
        'Tambah Program Akademik';

    document.getElementById('programId').value = '';
    document.getElementById('programCategory').value = defaultCategory;
    document.getElementById('programChapter').value = '';
    document.getElementById('programTitle').value = '';
    document.getElementById('programDescription').value = '';
    document.getElementById('programUrl').value = '';

    updateChapterField();

    modal.classList.add('show');
}

function openEditModal(id, cat, chapter, title, description, url) {

    document.getElementById('modalTitle').textContent =
        'Edit Program Akademik';

    document.getElementById('programId').value = id;
    document.getElementById('programCategory').value = cat;
    document.getElementById('programChapter').value = chapter;
    document.getElementById('programTitle').value = title;
    document.getElementById('programDescription').value = description;
    document.getElementById('programUrl').value = url;

    updateChapterField();

    modal.classList.add('show');
}

function closeProgramModal() {
    modal.classList.remove('show');
}

modal.addEventListener('click', function(event) {
    if (event.target === modal) {
        closeProgramModal();
    }
});

updateChapterField();
</script>

</body>
</html>