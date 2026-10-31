
<?php
require_once '../../eduspace/includes/auth.php';
require_student();

require_once '../../eduspace/config/database.php';

/* =========================
   IDENTITAS SISWA
========================= */
$username = $_SESSION['username'] ?? '';
$studentName = preg_replace('/\s+\d+$/', '', $username);

$class_map = [
    'Adit 01' => 'A',
    'Agus 01' => 'B',
    'Aldo 01' => 'C'
];

$studentClass = $class_map[$username] ?? '';

/* =========================
   HELPER
========================= */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* =========================
   AMBIL DATA PROGRAM
========================= */
$programs = [];

$result = $conn->query("
    SELECT * FROM academic_programs
    ORDER BY id ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $programs[$row['category']][] = $row;
    }
}

$syllabus = $programs['syllabus'][0] ?? null;
$calendar = $programs['calendar'][0] ?? null;
$schedule = $programs['schedule'][0] ?? null;
$agreement = $programs['agreement'][0] ?? null;
$modules = $programs['module'] ?? [];

/* =========================
   FUNGSI TAMPILAN RESOURCE
========================= */
function renderResource($data, $icon, $buttonText) {

    if (!$data) {
        echo '<p class="empty-message">Belum ada dokumen yang tersedia.</p>';
        return;
    }

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
        </div>

    </div>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Academic Programs | EduSpace Student</title>

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

.student {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.avatar {
    background: #e3edf8;
    color: #17375e;
    width: 43px;
    height: 43px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
}

.student-info strong {
    font-size: 13px;
}

.student-info p {
    font-size: 11px;
}

/* HERO */
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

/* SECTION */
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

.resource-actions {
    display: flex;
    gap: 7px;
    align-items: center;
    flex-wrap: wrap;
}

.empty-message {
    background: #f5f3ee;
    padding: 15px;
    border-radius: 9px;
    color: #778397;
    font-size: 13px;
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
    transition: .3s;
}

.module-card:hover {
    border-color: #9aabc0;
    transform: translateY(-2px);
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
        <img src="../../eduspace/assets/logo-sekolah.png"
             alt="Logo SMA Bangsa Utama">

        <h2>EduSpace.</h2>
        <p>LEARNING MANAGEMENT SYSTEM</p>
    </div>

    <p class="menu-title">MAIN MENU</p>

    <nav class="menu">
        <a href="../index.php">🏠 Home</a>
<a href="materi.php">📚 Materi</a>
<a href="program.php" class="active">🗓️ Program Akademik</a>
<a href="rekap-absensi.php">📊 Rekap Absensi</a>
<a href="diskusi.php">💬 Ruang Diskusi</a>
<a href="tugas.php">📝 Tugas & Quiz</a>
<a href="nilai.php">📊 Nilai</a>
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

        <div class="student">
    <div class="student-info">
        <strong><?= e($studentName) ?></strong>
        <p>
            Student<?= $studentClass !== '' ? ' · Kelas ' . e($studentClass) : '' ?>
        </p>
    </div>

    <div class="avatar">
        <?= e(strtoupper(substr($studentName, 0, 1))) ?>
    </div>
</div>
    </div>

    <!-- HERO -->
    <section class="hero">

        <div class="label">EDUSPACE • ACADEMIC YEAR 2026/2027</div>

        <h1>ACADEMIC PROGRAMS &amp; COURSE OVERVIEW</h1>

        <h3>English Grade 10 — SMA Bangsa Utama</h3>

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
            '🎯',
            'View Syllabus'
        );
        ?>

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
            '🔴',
            'View Calendar'
        );

        renderResource(
            $schedule,
            '🔵',
            'View Schedule'
        );
        ?>

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

        <?php if (!empty($modules)): ?>

            <div class="module-grid">

                <?php foreach ($modules as $module): ?>

                    <div class="module-card">

                        <div class="chapter">
                            <?= e($module['chapter']) ?>
                        </div>

                        <h3><?= e($module['title']) ?></h3>

                        <p><?= e($module['description']) ?></p>

                        <a class="btn"
                           href="<?= e($module['url']) ?>"
                           target="_blank"
                           rel="noopener noreferrer">
                            Open Module ↗
                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <p class="empty-message">
                Belum ada teaching module yang tersedia.
            </p>

        <?php endif; ?>

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
            '🤝',
            'Read Agreement'
        );
        ?>

    </section>

    <footer>
        © 2026 EduSpace — SMA Bangsa Utama
        <br>
        Designed for Grade 10 English Learning
        <br><br>
        Warm regards, Bu Safira Maulidiya, M.Pd. 💙
    </footer>

</main>

</body>
</html>
