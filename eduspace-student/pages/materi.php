<?php
require_once '../../eduspace/includes/auth.php';
require_student();

require_once '../../eduspace/config/database.php';

// Data nama dan kelas siswa
$username = $_SESSION['username'] ?? 'Student';
$studentName = preg_replace('/\s+\d+$/', '', $username);

$class_map = [
    'Adit 01' => 'A',
    'Agus 01' => 'B',
    'Aldo 01' => 'C'
];

$studentClass = $class_map[$username] ?? '';

// Fungsi untuk mengamankan output
if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

// Ambil data materi dari database
$ganjil = [];
$genap = [];
$books = [];
$videos = [];

$result = $conn->query("
    SELECT id, category, semester, chapter_number, title, description, url
    FROM learning_materials
    ORDER BY
        CASE
            WHEN category = 'chapter' AND semester = 'ganjil' THEN 1
            WHEN category = 'chapter' AND semester = 'genap' THEN 2
            WHEN category = 'book' THEN 3
            WHEN category = 'video' THEN 4
            ELSE 5
        END,
        chapter_number ASC,
        id ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['category'] === 'chapter' && $row['semester'] === 'ganjil') {
            $ganjil[] = $row;
        } elseif ($row['category'] === 'chapter' && $row['semester'] === 'genap') {
            $genap[] = $row;
        } elseif ($row['category'] === 'book') {
            $books[] = $row;
        } elseif ($row['category'] === 'video') {
            $videos[] = $row;
        }
    }
}

$base_url = '/eduspace-student/';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Materi Pembelajaran | EduSpace Student</title>

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
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
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

/* WELCOME */

.welcome {
    background: linear-gradient(120deg, #123454, #3475a5);
    border-radius: 18px;
    padding: 32px;
    color: white;
    margin-bottom: 30px;
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
    font-size: 25px;
    margin-bottom: 14px;
    line-height: 1.5;
}

.welcome p {
    font-size: 13px;
    line-height: 1.9;
    color: #edf5fc;
}

.welcome ul {
    margin: 12px 0 12px 20px;
    font-size: 13px;
    line-height: 2;
}

.welcome strong {
    color: #f3d9a7;
}

/* SECTION */

.section-heading {
    margin: 35px 0 18px;
}

.section-heading h2 {
    font-size: 21px;
    margin-bottom: 5px;
}

.section-heading p {
    font-size: 12px;
    color: #8193a9;
}

/* CHAPTER CARD */

.chapter-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 17px;
}

.chapter-card {
    background: white;
    border: 1px solid #e0e8f1;
    border-radius: 15px;
    padding: 22px;
    transition: .25s;
    box-shadow: 0 4px 12px rgba(20,50,80,.03);
}

.chapter-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(20,50,80,.09);
}

.chapter-number {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    background: #eaf1f8;
    color: #17375e;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 15px;
}

.chapter-card h3 {
    font-size: 15px;
    margin-bottom: 9px;
}

.chapter-card p {
    color: #8193a9;
    font-size: 11px;
    line-height: 1.8;
    min-height: 60px;
    margin-bottom: 17px;
}

/* BUTTON */

.open-button {
    display: inline-block;
    background: #173f65;
    color: white;
    text-decoration: none;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    transition: .2s;
}

.open-button:hover {
    background: #3475a5;
}

/* BOOK AND VIDEO */

.resource-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 17px;
}

.resource-card {
    background: white;
    border: 1px solid #e0e8f1;
    border-radius: 15px;
    padding: 22px;
    transition: .25s;
}

.resource-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(20,50,80,.09);
}

.resource-icon {
    font-size: 28px;
    margin-bottom: 12px;
}

.resource-card h3 {
    font-size: 14px;
    margin-bottom: 9px;
}

.resource-card p {
    font-size: 11px;
    line-height: 1.8;
    color: #8193a9;
    min-height: 58px;
    margin-bottom: 15px;
}

.resource-note {
    background: #f7f0e4;
    border-radius: 12px;
    padding: 18px;
    margin-top: 20px;
    font-size: 12px;
    line-height: 1.8;
    color: #53677e;
}

.empty-message {
    background: white;
    border: 1px dashed #cbd8e6;
    border-radius: 12px;
    padding: 22px;
    color: #8193a9;
    font-size: 12px;
    text-align: center;
    grid-column: 1 / -1;
}

footer {
    text-align: center;
    padding: 35px 10px 10px;
    color: #8193a9;
    font-size: 11px;
}

/* RESPONSIVE */

@media (max-width: 1100px) {
    .chapter-grid,
    .resource-grid {
        grid-template-columns: repeat(2, 1fr);
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

    .chapter-grid,
    .resource-grid {
        grid-template-columns: 1fr;
    }

    .welcome h1 {
        font-size: 20px;
    }

    .topbar {
        align-items: flex-start;
        gap: 15px;
    }

    .topbar h2 {
        font-size: 18px;
    }

    .teacher-info h4 {
        font-size: 11px;
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
<a href="materi.php" class="active">📚 Materi</a>
<a href="program.php">🗓️ Program Akademik</a>
<a href="rekap-absensi.php">📊 Rekap Absensi</a>
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
        <h2>Materi Pembelajaran</h2>

        <div class="student">
    <div class="student-info">
        <h4><?= e($studentName) ?></h4>
        <p>
            Student<?= $studentClass !== '' ? ' · Kelas ' . e($studentClass) : '' ?>
        </p>
    </div>

    <div class="teacher-avatar">
        <?= e(strtoupper(substr($studentName, 0, 1))) ?>
    </div>
</div>
    </header>

    <section class="welcome">

        <span class="welcome-label">ENGLISH LEARNING RESOURCE CENTER</span>

        <h1>Welcome to Grade 10 English Learning Resource Center! 📚✨</h1>

        <p>
            Halo semuanya! Di halaman ini, kalian bisa mengakses semua materi
            pembelajaran Bahasa Inggris Kelas 10 secara mandiri. Supaya belajar
            kalian lebih maksimal, di setiap <em>Chapter</em> sudah disediakan
            berbagai sumber belajar yang dapat kalian gunakan.
        </p>

        <ul>
            <li>📊 <strong>Slide PPT Interaktif:</strong> Ringkasan materi utama yang kita pelajari di kelas.</li>
            <li>📘 <strong>E-Book & Text Reference:</strong> Buku panduan dan bacaan pendukung untuk memperdalam pemahaman.</li>
            <li>🎥 <strong>YouTube Video Lesson:</strong> Video penjelasan materi dan <em>listening practice</em> agar kalian bisa belajar kapan saja (<em>anytime & anywhere</em>).</li>
        </ul>

        <p>
            Feel free to explore these materials for your <em>post-class review</em>,
            <em>self-study</em>, atau persiapan kuis dan ujian.
            <strong>Happy learning, stay curious, and let's level up your English together! 🚀</strong>
        </p>

        <p style="margin-top:18px;">
            Warm regards,<br>
            <strong>Bu Safira Maulidiya, M.Pd.</strong>
        </p>

    </section>

    <!-- SEMESTER GANJIL -->

    <section>
        <div class="section-heading">
            <h2>📘 Semester Ganjil 2026</h2>
            <p>Explore Chapters 1–6 and strengthen your English foundation.</p>
        </div>

        <div class="chapter-grid">
            <?php if (count($ganjil) > 0): ?>
                <?php foreach ($ganjil as $chapter): ?>
                    <article class="chapter-card">
                        <div class="chapter-number">
                            <?= e($chapter['chapter_number']) ?>
                        </div>

                        <h3>
                            Chapter <?= e($chapter['chapter_number']) ?>
                            – <?= e($chapter['title']) ?>
                        </h3>

                        <p><?= e($chapter['description']) ?></p>

                        <a class="open-button"
                           href="<?= e($chapter['url']) ?>"
                           target="_blank"
                           rel="noopener noreferrer">
                            Open Learning Material ↗
                        </a>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-message">
                    Belum ada materi untuk semester ganjil.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- SEMESTER GENAP -->

    <section>
        <div class="section-heading">
            <h2>📗 Semester Genap 2027</h2>
            <p>Continue your English learning journey through Chapters 7–12.</p>
        </div>

        <div class="chapter-grid">
            <?php if (count($genap) > 0): ?>
                <?php foreach ($genap as $chapter): ?>
                    <article class="chapter-card">
                        <div class="chapter-number">
                            <?= e($chapter['chapter_number']) ?>
                        </div>

                        <h3>
                            Chapter <?= e($chapter['chapter_number']) ?>
                            – <?= e($chapter['title']) ?>
                        </h3>

                        <p><?= e($chapter['description']) ?></p>

                        <a class="open-button"
                           href="<?= e($chapter['url']) ?>"
                           target="_blank"
                           rel="noopener noreferrer">
                            Open Learning Material ↗
                        </a>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-message">
                    Belum ada materi untuk semester genap.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- BUKU REFERENSI -->

    <section>
        <div class="section-heading">
            <h2>📚 English Book References</h2>
            <p>Additional reading resources to support your learning.</p>
        </div>

        <div class="resource-grid">
            <?php if (count($books) > 0): ?>
                <?php foreach ($books as $book): ?>
                    <article class="resource-card">
                        <div class="resource-icon">📖</div>

                        <h3><?= e($book['title']) ?></h3>

                        <p><?= e($book['description']) ?></p>

                        <a class="open-button"
                           href="<?= e($book['url']) ?>"
                           target="_blank"
                           rel="noopener noreferrer">
                            Read E-Book ↗
                        </a>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-message">
                    Belum ada buku referensi.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- VIDEO PEMBELAJARAN -->

    <section>
        <div class="section-heading">
            <h2>🎥 Video Learning Center</h2>
            <p>Watch, listen, and practice English through these selected videos.</p>
        </div>

        <div class="resource-grid">
            <?php if (count($videos) > 0): ?>
                <?php foreach ($videos as $video): ?>
                    <article class="resource-card">
                        <div class="resource-icon">▶️</div>

                        <h3><?= e($video['title']) ?></h3>

                        <p><?= e($video['description']) ?></p>

                        <a class="open-button"
                           href="<?= e($video['url']) ?>"
                           target="_blank"
                           rel="noopener noreferrer">
                            Watch Video ↗
                        </a>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-message">
                    Belum ada video pembelajaran.
                </div>
            <?php endif; ?>
        </div>

        <div class="resource-note">
            💡 <strong>Learning Tip from Bu Safira:</strong><br>
            Jangan hanya membaca materi. Cobalah mencatat kosakata baru,
            berlatih mengucapkannya, dan mengulang video pembelajaran
            agar kemampuan Bahasa Inggris kalian semakin berkembang.
        </div>
    </section>

    <footer>
        © 2026 EduSpace Student · SMA Bangsa Utama · English Learning Platform
    </footer>

</main>

</body>
</html>
