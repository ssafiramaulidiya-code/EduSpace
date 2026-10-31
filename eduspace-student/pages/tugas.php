
<?php
require_once '../../eduspace/config/database.php';
require_once '../../eduspace/includes/auth.php';
require_student();

$username = $_SESSION['username'] ?? 'Student';
$studentName = preg_replace('/\s+\d+$/', '', $username);

$class_map = [
    'Adit 01' => 'A',
    'Agus 01' => 'B',
    'Aldo 01' => 'C'
];

$studentClass = $class_map[$username] ?? '';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// AMBIL DATA QUIZ DAN ASSIGNMENT
$items = [];

$result = $conn->query(
    "SELECT * FROM assignments ORDER BY category ASC, id ASC"
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

// PISAHKAN QUIZ DAN TUGAS
$quizzes = array_filter($items, function($item) {
    return $item['category'] === 'quiz';
});

$assignments = array_filter($items, function($item) {
    return $item['category'] === 'assignment';
});

$base_url = '/eduspace-student/';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzes & Assignments | EduSpace</title>

    <style>
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
        }

        .school-logo {
            text-align: center;
            padding-bottom: 28px;
            border-bottom: 1px solid rgba(255,255,255,0.25);
            margin-bottom: 30px;
        }

        .school-logo img {
            width: 90px;
            height: 90px;
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
            transition: .3s;
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
            padding: 30px 40px;
            min-height: 100vh;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .topbar h2 {
            color: #172b4d;
            font-size: 23px;
            margin-bottom: 5px;
        }

        .topbar p {
            color: #718096;
            font-size: 13px;
        }

        .student {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
    background: #e3edf8;
    color: #17375e;
    width: 43px;
    height: 43px;
    border-radius: 50%;
    display: grid;
    place-items: center;
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
            background: linear-gradient(120deg, #172b4d, #38577e);
            padding: 38px;
            border-radius: 18px;
            color: white;
            margin-bottom: 28px;
        }

        .hero .label {
            color: #e2d3b5;
            font-size: 12px;
            letter-spacing: 2px;
            font-weight: bold;
            margin-bottom: 13px;
        }

        .hero h1 {
            font-size: 30px;
            margin-bottom: 12px;
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
            max-width: 750px;
        }

        /* SECTION */
        .section {
            background: white;
            padding: 27px;
            border-radius: 15px;
            margin-bottom: 23px;
            box-shadow: 0 4px 15px rgba(23, 43, 77, .05);
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
            margin-top: 5px;
            color: #172b4d;
        }

        .section-heading p {
            color: #778397;
            font-size: 13px;
            margin-top: 7px;
            line-height: 1.7;
        }

        /* RESOURCE CARDS */
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
            width: 48px;
            height: 48px;
            min-width: 48px;
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
            color: #8490a1;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .category-label {
            display: inline-block;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #a28554;
            margin-bottom: 5px;
        }

        .resource-actions {
            display: flex;
            gap: 7px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            border: none;
            cursor: pointer;
            background: #203c63;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 12px;
            white-space: nowrap;
            transition: .3s;
        }

        .btn:hover {
            background: #b29461;
        }

        .empty {
            padding: 25px;
            text-align: center;
            color: #8490a1;
            background: #f8f7f4;
            border-radius: 10px;
            font-size: 13px;
        }

        footer {
            text-align: center;
            color: #8a94a3;
            font-size: 12px;
            padding: 20px;
            line-height: 1.8;
        }

        @media (max-width: 900px) {
            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
                padding: 25px;
            }
        }

        @media (max-width: 650px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
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
                padding: 20px 16px;
            }

            .topbar {
                align-items: flex-start;
                flex-wrap: wrap;
                gap: 15px;
                margin-bottom: 22px;
            }

            .hero {
                padding: 25px;
            }

            .hero h1 {
                font-size: 23px;
            }

            .section {
                padding: 21px 18px;
            }

            .resource-card {
                align-items: flex-start;
                flex-direction: column;
            }

            .resource-actions {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">
    <div class="school-logo">
        <img
            src="../../eduspace/assets/logo-sekolah.png"
            alt="Logo SMA Bangsa Utama"
            onerror="this.style.display='none'"
        >
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
<a href="tugas.php" class="active">📝 Tugas & Quiz</a>
<a href="nilai.php">📊 Nilai</a>
    </nav>

    <div class="sidebar-bottom">
        Learn, Explore,<br>Achieve. ✨
    </div>
</aside>

<main class="main">

    <div class="topbar">
        <div>
            <h2>Quizzes & Assignments</h2>
            <p>Access your quizzes, assignments, and learning activities.</p>
        </div>

        <div class="student">
    <div class="student-info">
        <strong><?= h($studentName) ?></strong>
        <p>
            Student<?= $studentClass !== '' ? ' · Kelas ' . h($studentClass) : '' ?>
        </p>
    </div>

    <div class="avatar">
        <?= h(strtoupper(substr($studentName, 0, 1))) ?>
    </div>
</div>
    </div>

    <section class="hero">
        <div class="label">EDUSPACE • GRADE 10 ENGLISH</div>
        <h1>QUIZZES &amp; ASSIGNMENTS</h1>
        <h3>Test Your Understanding | Grade 10 English</h3>
        <p>
            Welcome to the Quiz Center! Complete each 2-Chapter quiz
            to test your comprehension, practice grammar, and track
            your academic progress throughout the term. 🎯✍️
        </p>
    </section>

    <!-- QUIZZES -->
    <section class="section">
        <div class="section-heading">
            <span>SECTION 01 • ONLINE QUIZZES</span>
            <h2>🎯 Test Your Knowledge!</h2>
            <p>
                Complete each quiz to check your understanding of the
                lessons and practice your English skills.
            </p>
        </div>

        <?php if (count($quizzes) > 0): ?>

            <?php foreach ($quizzes as $item): ?>
                <div class="resource-card">
                    <div class="resource-info">
                        <div class="resource-icon">📝</div>

                        <div>
                            <span class="category-label">ONLINE QUIZ</span>
                            <h3><?= h($item['title']) ?></h3>
                            <p><?= h($item['description']) ?></p>
                        </div>
                    </div>

                    <div class="resource-actions">
                        <a
                            class="btn"
                            href="<?= h($item['url']) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Start Quiz ↗
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="empty">
                Belum ada quiz yang tersedia. Silakan cek kembali nanti.
            </div>
        <?php endif; ?>
    </section>

    <!-- ASSIGNMENTS -->
    <section class="section">
        <div class="section-heading">
            <span>SECTION 02 • ASSIGNMENTS & EXAMINATIONS</span>
            <h2>📚 Your Assignments</h2>
            <p>
                Find your assignments and complete each task according
                to the instructions provided.
            </p>
        </div>

        <?php if (count($assignments) > 0): ?>

            <?php foreach ($assignments as $item): ?>
                <div class="resource-card">
                    <div class="resource-info">
                        <div class="resource-icon">📄</div>

                        <div>
                            <span class="category-label">ASSIGNMENT</span>
                            <h3><?= h($item['title']) ?></h3>
                            <p><?= h($item['description']) ?></p>
                        </div>
                    </div>

                    <div class="resource-actions">
                        <a
                            class="btn"
                            href="<?= h($item['url']) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Open Assignment ↗
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="empty">
                Belum ada tugas yang tersedia. Silakan cek kembali nanti.
            </div>
        <?php endif; ?>
    </section>

    <footer>
        © 2026 EduSpace — SMA Bangsa Utama
        <br>
        Designed for Grade 10 English Learning
        <br><br>
        Happy Learning! 💙
    </footer>

</main>

</body>
</html>
