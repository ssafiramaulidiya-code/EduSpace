
<?php
require_once '../config/database.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirectWithMessage($message) {
    header('Location: materi.php?msg=' . urlencode($message));
    exit;
}

// ===============================
// PROSES TAMBAH, EDIT, DAN HAPUS
// ===============================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $category = $_POST['category'] ?? '';
        $semester = $_POST['semester'] ?? null;
        $chapter_number = $_POST['chapter_number'] ?? null;
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $url = trim($_POST['url'] ?? '');

        $allowed_categories = ['chapter', 'book', 'video'];

        if (
            !in_array($category, $allowed_categories, true) ||
            $title === '' ||
            $url === '' ||
            !filter_var($url, FILTER_VALIDATE_URL) ||
            !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
        ) {
            redirectWithMessage('invalid');
        }

        if ($category === 'chapter') {
            if (!in_array($semester, ['ganjil', 'genap'], true) || !is_numeric($chapter_number) || (int)$chapter_number < 1) {
                redirectWithMessage('invalid');
            }
            $chapter_number = (int)$chapter_number;
        } else {
            $semester = null;
            $chapter_number = null;
        }

        if ($id > 0) {
            $stmt = $conn->prepare("
                UPDATE learning_materials
                SET category = ?, semester = ?, chapter_number = ?,
                    title = ?, description = ?, url = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "ssisssi",
                $category,
                $semester,
                $chapter_number,
                $title,
                $description,
                $url,
                $id
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO learning_materials
                (category, semester, chapter_number, title, description, url)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "ssisss",
                $category,
                $semester,
                $chapter_number,
                $title,
                $description,
                $url
            );
        }

        if ($stmt->execute()) {
            $stmt->close();
            redirectWithMessage('saved');
        }

        $stmt->close();
        redirectWithMessage('error');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM learning_materials WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            redirectWithMessage('deleted');
        }

        redirectWithMessage('error');
    }
}

// ===============================
// AMBIL DATA MATERI DARI DATABASE
// ===============================

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

$base_url = '/eduspace/';
$message = $_GET['msg'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Materi Pembelajaran | EduSpace</title>

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

.teacher {
    display: flex;
    align-items: center;
    gap: 12px;
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

.teacher-avatar {
    width: 43px;
    height: 43px;
    border-radius: 50%;
    background: #e3edf8;
    display: grid;
    place-items: center;
    font-weight: 700;
}

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
    border: none;
    cursor: pointer;
    font-family: inherit;
}

.open-button:hover {
    background: #3475a5;
}

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

footer {
    text-align: center;
    padding: 35px 10px 10px;
    color: #8193a9;
    font-size: 11px;
}

/* FITUR KELOLA MATERI */

.add-material-button {
    background: #f2d59b;
    color: #17375e;
    border: none;
    padding: 11px 17px;
    border-radius: 9px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.add-material-button:hover {
    background: #e8c47e;
}

.card-actions {
    display: flex;
    gap: 7px;
    flex-wrap: wrap;
    margin-top: 12px;
}

.edit-button,
.delete-button {
    border: none;
    border-radius: 7px;
    padding: 8px 11px;
    font-family: inherit;
    font-size: 10px;
    font-weight: 600;
    cursor: pointer;
}

.edit-button {
    background: #eaf1f8;
    color: #173f65;
}

.delete-button {
    background: #fff0f0;
    color: #b33a3a;
}

.edit-button:hover {
    background: #d9e7f5;
}

.delete-button:hover {
    background: #ffe0e0;
}

.notice {
    padding: 13px 17px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 12px;
}

.notice.success {
    background: #eaf8ef;
    color: #18794e;
}

.notice.error {
    background: #fff0f0;
    color: #b33a3a;
}

.modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 1000;
    background: rgba(10, 30, 50, .6);
    padding: 20px;
    align-items: center;
    justify-content: center;
    overflow-y: auto;
}

.modal.show {
    display: flex;
}

.modal-content {
    width: 100%;
    max-width: 540px;
    background: white;
    border-radius: 17px;
    padding: 27px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}

.modal-header h2 {
    font-size: 19px;
}

.close-modal {
    border: none;
    background: transparent;
    font-size: 25px;
    color: #8193a9;
    cursor: pointer;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 7px;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 11px 12px;
    border: 1px solid #dce5ef;
    border-radius: 8px;
    font-family: inherit;
    font-size: 12px;
    color: #17375e;
    outline: none;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    border-color: #3475a5;
}

.form-group textarea {
    min-height: 85px;
    resize: vertical;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    margin-top: 22px;
}

.cancel-button {
    background: #edf1f5;
    color: #53677e;
    border: none;
    border-radius: 8px;
    padding: 10px 16px;
    font-family: inherit;
    font-size: 12px;
    cursor: pointer;
}

.save-button {
    background: #173f65;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 18px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.save-button:hover {
    background: #3475a5;
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
        <img src="../assets/logo-sekolah.png" alt="Logo SMA Bangsa Utama">
        <h2>EduSpace.</h2>
        <p>LEARNING MANAGEMENT SYSTEM</p>
    </div>

    <p class="menu-title">MAIN MENU</p>

    <nav class="menu">
        <a href="<?= $base_url ?>index.php">🏠 Home</a>
        <a href="<?= $base_url ?>pages/materi.php" class="active">📚 Materi</a>
        <a href="<?= $base_url ?>pages/program.php">🗓️ Program Akademik</a>
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

<main class="main">

    <header class="topbar">
        <h2>Materi Pembelajaran</h2>

        <div class="teacher">
            <div class="teacher-info">
                <h4>Safira Maulidiya, M.Pd.</h4>
                <p>English Teacher</p>
            </div>
            <div class="teacher-avatar">S</div>
        </div>
    </header>

    <?php if ($message === 'saved'): ?>
        <div class="notice success">Materi berhasil disimpan ke database.</div>
    <?php elseif ($message === 'deleted'): ?>
        <div class="notice success">Materi berhasil dihapus.</div>
    <?php elseif ($message === 'invalid'): ?>
        <div class="notice error">Data belum lengkap atau tautan tidak valid. Silakan periksa kembali.</div>
    <?php elseif ($message === 'error'): ?>
        <div class="notice error">Terjadi kesalahan saat menyimpan data. Silakan coba lagi.</div>
    <?php endif; ?>

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

    <div style="display:flex; justify-content:flex-end; margin-bottom:20px;">
        <button type="button" class="add-material-button" onclick="openAddModal()">
            + Tambah Materi
        </button>
    </div>

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
                        <div class="chapter-number"><?= e($chapter['chapter_number']) ?></div>

                        <h3>Chapter <?= e($chapter['chapter_number']) ?> – <?= e($chapter['title']) ?></h3>

                        <p><?= e($chapter['description']) ?></p>

                        <a class="open-button" href="<?= e($chapter['url']) ?>" target="_blank" rel="noopener noreferrer">
                            Open Learning Material ↗
                        </a>

                        <div class="card-actions">
                            <button type="button" class="edit-button"
                                onclick="openEditModal(
                                    <?= (int)$chapter['id'] ?>,
                                    'chapter',
                                    '<?= e($chapter['semester']) ?>',
                                    '<?= (int)$chapter['chapter_number'] ?>',
                                    '<?= e($chapter['title']) ?>',
                                    '<?= e($chapter['description']) ?>',
                                    '<?= e($chapter['url']) ?>'
                                )">✏️ Edit</button>

                            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus materi ini?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$chapter['id'] ?>">
                                <button type="submit" class="delete-button">🗑 Hapus</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-message">Belum ada materi untuk semester ganjil.</div>
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
                        <div class="chapter-number"><?= e($chapter['chapter_number']) ?></div>

                        <h3>Chapter <?= e($chapter['chapter_number']) ?> – <?= e($chapter['title']) ?></h3>

                        <p><?= e($chapter['description']) ?></p>

                        <a class="open-button" href="<?= e($chapter['url']) ?>" target="_blank" rel="noopener noreferrer">
                            Open Learning Material ↗
                        </a>

                        <div class="card-actions">
                            <button type="button" class="edit-button"
                                onclick="openEditModal(
                                    <?= (int)$chapter['id'] ?>,
                                    'chapter',
                                    '<?= e($chapter['semester']) ?>',
                                    '<?= (int)$chapter['chapter_number'] ?>',
                                    '<?= e($chapter['title']) ?>',
                                    '<?= e($chapter['description']) ?>',
                                    '<?= e($chapter['url']) ?>'
                                )">✏️ Edit</button>

                            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus materi ini?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$chapter['id'] ?>">
                                <button type="submit" class="delete-button">🗑 Hapus</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-message">Belum ada materi untuk semester genap.</div>
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

                        <a class="open-button" href="<?= e($book['url']) ?>" target="_blank" rel="noopener noreferrer">
                            Read E-Book ↗
                        </a>

                        <div class="card-actions">
                            <button type="button" class="edit-button"
                                onclick="openEditModal(
                                    <?= (int)$book['id'] ?>,
                                    'book',
                                    '',
                                    '',
                                    '<?= e($book['title']) ?>',
                                    '<?= e($book['description']) ?>',
                                    '<?= e($book['url']) ?>'
                                )">✏️ Edit</button>

                            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus buku ini?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$book['id'] ?>">
                                <button type="submit" class="delete-button">🗑 Hapus</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-message">Belum ada buku referensi.</div>
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

                        <a class="open-button" href="<?= e($video['url']) ?>" target="_blank" rel="noopener noreferrer">
                            Watch Video ↗
                        </a>

                        <div class="card-actions">
                            <button type="button" class="edit-button"
                                onclick="openEditModal(
                                    <?= (int)$video['id'] ?>,
                                    'video',
                                    '',
                                    '',
                                    '<?= e($video['title']) ?>',
                                    '<?= e($video['description']) ?>',
                                    '<?= e($video['url']) ?>'
                                )">✏️ Edit</button>

                            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus video ini?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$video['id'] ?>">
                                <button type="submit" class="delete-button">🗑 Hapus</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-message">Belum ada video pembelajaran.</div>
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
        © 2026 EduSpace · SMA Bangsa Utama · English Learning Platform
    </footer>

</main>

<!-- MODAL TAMBAH / EDIT MATERI -->
<div class="modal" id="materialModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Tambah Materi</h2>
            <button type="button" class="close-modal" onclick="closeModal()">&times;</button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="materialId" value="0">

            <div class="form-group">
                <label for="category">Jenis Materi</label>
                <select name="category" id="category" required onchange="toggleChapterFields()">
                    <option value="chapter">Chapter / Materi Bab</option>
                    <option value="book">Book Reference</option>
                    <option value="video">Video Pembelajaran</option>
                </select>
            </div>

            <div id="chapterFields">
                <div class="form-group">
                    <label for="semester">Semester</label>
                    <select name="semester" id="semester">
                        <option value="ganjil">Semester Ganjil</option>
                        <option value="genap">Semester Genap</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="chapterNumber">Nomor Chapter</label>
                    <input type="number" name="chapter_number" id="chapterNumber" min="1" placeholder="Contoh: 13">
                </div>
            </div>

            <div class="form-group">
                <label for="title">Judul Materi</label>
                <input type="text" name="title" id="materialTitle" required placeholder="Masukkan judul materi">
            </div>

            <div class="form-group">
                <label for="description">Deskripsi</label>
                <textarea name="description" id="materialDescription" placeholder="Masukkan deskripsi materi"></textarea>
            </div>

            <div class="form-group">
                <label for="url">Link Materi</label>
                <input type="url" name="url" id="materialUrl" required placeholder="https://...">
            </div>

            <div class="form-actions">
                <button type="button" class="cancel-button" onclick="closeModal()">Batal</button>
                <button type="submit" class="save-button">Simpan Materi</button>
            </div>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById('materialModal');

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Materi';
    document.getElementById('materialId').value = '0';
    document.getElementById('category').value = 'chapter';
    document.getElementById('semester').value = 'ganjil';
    document.getElementById('chapterNumber').value = '';
    document.getElementById('materialTitle').value = '';
    document.getElementById('materialDescription').value = '';
    document.getElementById('materialUrl').value = '';

    toggleChapterFields();
    modal.classList.add('show');
}

function openEditModal(id, category, semester, chapterNumber, title, description, url) {
    document.getElementById('modalTitle').textContent = 'Edit Materi';
    document.getElementById('materialId').value = id;
    document.getElementById('category').value = category;
    document.getElementById('semester').value = semester || 'ganjil';
    document.getElementById('chapterNumber').value = chapterNumber || '';
    document.getElementById('materialTitle').value = title;
    document.getElementById('materialDescription').value = description;
    document.getElementById('materialUrl').value = url;

    toggleChapterFields();
    modal.classList.add('show');
}

function closeModal() {
    modal.classList.remove('show');
}

function toggleChapterFields() {
    const category = document.getElementById('category').value;
    const chapterFields = document.getElementById('chapterFields');
    const semester = document.getElementById('semester');
    const chapterNumber = document.getElementById('chapterNumber');

    if (category === 'chapter') {
        chapterFields.style.display = 'block';
        semester.required = true;
        chapterNumber.required = true;
    } else {
        chapterFields.style.display = 'none';
        semester.required = false;
        chapterNumber.required = false;
    }
}

modal.addEventListener('click', function(event) {
    if (event.target === modal) {
        closeModal();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});

toggleChapterFields();
</script>

</body>
</html>
