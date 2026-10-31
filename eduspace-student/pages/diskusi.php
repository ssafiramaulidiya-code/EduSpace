
<?php
require_once '../../eduspace/includes/auth.php';
require_student();

$padlet_url = "https://padlet.com/927tywr627/form-discussion-english-class-s024zor71d99w5c3znzb";
$padlet_embed_url = $padlet_url;

$username = $_SESSION['username'] ?? 'Student';
$studentName = preg_replace('/\s+\d+$/', '', $username);

$class_map = [
    'Adit 01' => 'A',
    'Agus 01' => 'B',
    'Aldo 01' => 'C'
];

$studentClass = $class_map[$username] ?? '';

$base_url = '/eduspace-student/';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Discussion Forum | EduSpace</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        html { overflow-x: hidden; }

        body {
            background: #f3f7fb;
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

        /* MAIN CONTENT */
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
            margin-bottom: 28px;
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
    display: grid;
    place-items: center;
    font-weight: 700;
}

        .student-info strong { font-size: 13px; }
        .student-info p { font-size: 11px; }

        .hero {
            background: linear-gradient(120deg, #003b5c, #087ca3);
            padding: 38px;
            border-radius: 18px;
            color: white;
            margin-bottom: 25px;
        }

        .hero-label {
            display: inline-block;
            background: rgba(255,255,255,0.16);
            padding: 8px 14px;
            border-radius: 30px;
            color: #ffe0b5;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 16px;
        }

        .hero h1 {
            font-size: 30px;
            line-height: 1.3;
            margin-bottom: 10px;
        }

        .hero h3 {
            color: #ffdbad;
            font-size: 18px;
            margin-bottom: 13px;
        }

        .hero p {
            color: #e8f1f7;
            font-size: 14px;
            line-height: 1.8;
            max-width: 850px;
        }

        .section {
            background: white;
            padding: 28px;
            border-radius: 16px;
            margin-bottom: 23px;
            box-shadow: 0 4px 15px rgba(23,43,77,.05);
            min-width: 0;
        }

        .section-heading { margin-bottom: 18px; }

        .section-heading span {
            color: #b29461;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .section-heading h2 {
            color: #172b4d;
            font-size: 21px;
            line-height: 1.4;
            margin-top: 6px;
        }

        .section-heading p {
            color: #718096;
            font-size: 13px;
            line-height: 1.7;
            margin-top: 7px;
        }

        .welcome-copy {
            color: #536176;
            font-size: 14px;
            line-height: 1.9;
        }

        .welcome-copy p { margin-bottom: 12px; }

        .guidelines {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 20px;
        }

        .guideline-card {
            background: #f7f9fc;
            border: 1px solid #e7edf3;
            border-radius: 12px;
            padding: 17px;
        }

        .guideline-card h3 {
            color: #174563;
            font-size: 14px;
            margin-bottom: 7px;
        }

        .guideline-card p,
        .guideline-card li {
            color: #657286;
            font-size: 13px;
            line-height: 1.75;
        }

        .guideline-card ul {
            padding-left: 18px;
            margin-top: 7px;
        }

        .padlet-section {
            padding: 24px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(23,43,77,.05);
            margin-bottom: 23px;
        }

        .padlet-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 17px;
        }

        .padlet-top h2 {
            color: #173c5b;
            font-size: 20px;
        }

        .padlet-frame-wrap {
            width: 100%;
            height: 650px;
            border: 1px solid #dce5ed;
            border-radius: 12px;
            overflow: hidden;
            background: #f7f9fc;
        }

        .padlet-frame {
            display: block;
            width: 100%;
            height: 100%;
            border: 0;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            background: #203c63;
            color: white;
            padding: 11px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            transition: .3s;
            white-space: nowrap;
        }

        .btn:hover { background: #b29461; }

        .note {
            margin-top: 13px;
            padding: 13px 15px;
            background: #fff8eb;
            border-left: 4px solid #d6ad68;
            border-radius: 7px;
            color: #756344;
            font-size: 12px;
            line-height: 1.7;
        }

        .closing {
            background: linear-gradient(120deg, #eaf3f8, #f6f1e7);
            border-radius: 13px;
            padding: 20px;
            color: #36536a;
            font-size: 14px;
            line-height: 1.8;
        }

        footer {
            text-align: center;
            color: #8a94a3;
            font-size: 12px;
            line-height: 1.7;
            padding: 18px;
        }

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

            .guidelines { grid-template-columns: 1fr; }
            .hero { padding: 30px; }
            .hero h1 { font-size: 26px; }
            .padlet-frame-wrap { height: 600px; }
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

            .menu a { padding: 11px 13px; }
            .sidebar-bottom { margin-top: 20px; }

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

            .topbar h2 { font-size: 21px; }
            .hero { padding: 24px 20px; }
            .hero h1 { font-size: 23px; }
            .section, .padlet-section { padding: 21px 18px; }
            .padlet-frame-wrap { height: 560px; }
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
<a href="diskusi.php" class="active">💬 Ruang Diskusi</a>
<a href="tugas.php">📝 Tugas & Quiz</a>
<a href="nilai.php">📊 Nilai</a>
    </nav>

    <div class="sidebar-bottom">
        Learn, Explore,<br>Achieve. ✨
    </div>
</aside>

<main class="main">

    <div class="topbar">
        <div>
            <h2>Class Discussion Forum</h2>
            <p>Ask questions, share ideas, and learn together.</p>
        </div>

        <div class="student">
    <div class="student-info">
        <strong><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></strong>
        <p>
            Student<?= $studentClass !== '' ? ' · Kelas ' . htmlspecialchars($studentClass, ENT_QUOTES, 'UTF-8') : '' ?>
        </p>
    </div>

    <div class="avatar">
        <?= htmlspecialchars(strtoupper(substr($studentName, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
    </div>

    <section class="hero">
        <div class="hero-label">EDUSPACE • GRADE 10 ENGLISH</div>
        <h1>CLASS DISCUSSION FORUM</h1>
        <h3>Ask, Share, &amp; Learn Together via Padlet! 💬✨</h3>
        <p>
            A friendly space to ask questions, exchange ideas, and support
            each other's English learning journey. Your questions matter!
        </p>
    </section>

    <section class="section">
        <div class="section-heading">
            <span>WELCOME MESSAGE</span>
            <h2>Welcome to Our Class Discussion Forum! 👋✨</h2>
        </div>

        <div class="welcome-copy">
            <p>
                Halo semuanya! Punya pertanyaan seputar materi <i>Chapter</i>,
                tugas, atau masih bingung sama <i>grammar &amp; vocabulary</i>?
                Jangan ragu buat drop pertanyaan kalian di <strong>Padlet Board</strong>
                di bawah ini ya!
            </p>

            <p>
                Biar ruang diskusi kita tetap nyaman dan teratur untuk semuanya,
                mohon perhatikan beberapa <strong>Guidelines &amp; Rules</strong> berikut:
            </p>
        </div>

        <div class="guidelines">
            <div class="guideline-card">
                <h3>💬 How it Works</h3>
                <p>
                    Tuliskan pertanyaan kalian secara jelas di Padlet.
                    <strong>Jangan lupa tulis nama kalian serta sebutkan kelas di bagian SUBJECT.</strong>
                    Sebisa mungkin, pertanyaan akan langsung dijawab melalui
                    <strong>kolom komentar</strong> di post tersebut.
                </p>
            </div>

            <div class="guideline-card">
                <h3>⏰ Active Response Hours</h3>
                <p>
                    Forum diskusi ini akan dipantau dan dijawab setiap
                    <strong>weekday (Senin–Jumat), pukul 08.00–17.00 WIB.</strong>
                </p>
            </div>

            <div class="guideline-card">
                <h3>🤝 Classroom Etiquette</h3>
                <p>
                    Tetap jaga kesopanan, tata tertib, dan etika berbahasa
                    (<i>be respectful and polite!</i>). Gunakan Bahasa Indonesia
                    atau Bahasa Inggris yang baik.
                </p>
            </div>

            <div class="guideline-card">
                <h3>📌 Focus on Relevant Topics</h3>
                <p>
                    Tanyakan hal-hal yang benar-benar kalian bingungkan agar
                    pertanyaan penting dari teman-teman yang lain tidak tertimbun
                    (<i>not overlapped</i>).
                </p>
            </div>

            <div class="guideline-card">
                <h3>🔄 Follow-up in Next Class</h3>
                <ul>
                    <li>Jika jawaban di Padlet masih belum memuaskan, kalian bisa tanyakan kembali di pertemuan kelas berikutnya.</li>
                    <li>Pertanyaan kompleks yang tidak memungkinkan dijelaskan secara penuh melalui teks Padlet akan kita bahas langsung di <i>next meeting</i>.</li>
                    <li>Kalau ada pertanyaan yang terlewat (<i>missed</i>), feel free untuk mengingatkan saat kita bertemu di kelas.</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="padlet-section">
        <div class="padlet-top">
            <div>
                <h2>💬 Grade 10 English — Padlet Board</h2>
                <p style="color:#7b8797;font-size:13px;margin-top:5px;">
                    Post your questions here and join the discussion.
                </p>
            </div>

            <a class="btn"
               href="<?= htmlspecialchars($padlet_url, ENT_QUOTES, 'UTF-8') ?>"
               target="_blank"
               rel="noopener noreferrer">
                Open Padlet in New Tab ↗
            </a>
        </div>

        <div class="padlet-frame-wrap">
            <iframe
                class="padlet-frame"
                src="<?= htmlspecialchars($padlet_embed_url, ENT_QUOTES, 'UTF-8') ?>"
                title="Grade 10 English Class Discussion Padlet"
                loading="lazy"
                allow="camera;microphone">
            </iframe>
        </div>

        <div class="note">
            Jika Padlet tidak muncul di dalam halaman ini, klik tombol
            <strong>Open Padlet in New Tab</strong> di atas untuk membuka
            papan diskusi secara langsung.
        </div>
    </section>

    <div class="closing">
        <strong>Don't be shy to ask. No question is a silly question!</strong>
        Let's build an active and supportive learning environment together! 🚀
        <br><br>
        Happy discussing!<br>
        <strong>Keep learning and supporting each other. ✨</strong>
    </div>

    <footer>
        © 2026 EduSpace — SMA Bangsa Utama
        <br>
        Grade 10 English Class Discussion Forum
    </footer>

</main>

</body>
</html>
