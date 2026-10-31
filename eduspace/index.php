

<?php
require_once 'includes/auth.php';

// Hanya guru yang boleh mengakses dashboard ini
require_teacher();

$school = "SMA Bangsa Utama";
$platform = "EduSpace";
$total_students = 60;
$total_classes = 3;
$subject = "Bahasa Inggris";
$grade = "Kelas X";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduSpace | English Learning Management System</title>

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
            color: #17375e;
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

        .welcome {
            background: linear-gradient(120deg, #123454, #3475a5);
            border-radius: 18px;
            padding: 35px;
            color: white;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
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
            font-size: 27px;
            line-height: 1.5;
            margin-bottom: 12px;
        }

        .welcome p {
            font-size: 13px;
            line-height: 1.8;
            max-width: 850px;
            color: #edf5fc;
        }

        .welcome .welcome-subtitle {
            font-size: 17px;
            color: #f3d9a7;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .welcome-decoration {
            position: absolute;
            right: 30px;
            top: 25px;
            font-size: 65px;
            opacity: .8;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e0e8f1;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(20,50,80,.03);
        }

        .stat-icon {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .stat-card h2 {
            font-size: 27px;
            color: #153b61;
        }

        .stat-card p {
            font-size: 12px;
            color: #8193a9;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 20px;
        }

        .content-card {
            background: white;
            border: 1px solid #e0e8f1;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(20,50,80,.03);
        }

        .content-card h3 {
            font-size: 17px;
            margin-bottom: 20px;
            color: #17375e;
        }

        .learning-info {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #edf1f5;
            font-size: 12px;
        }

        .learning-info span:first-child {
            color: #8193a9;
        }

        .learning-info strong {
            text-align: right;
        }

        .teacher-message {
            background: #f7f2e9;
            border-left: 4px solid #d4ad6c;
            border-radius: 10px;
            padding: 17px;
            margin-top: 20px;
        }

        .teacher-message p {
            font-size: 12px;
            line-height: 1.9;
            color: #42566d;
        }

        .teacher-message strong {
            color: #17375e;
        }

        .about-card p {
            font-size: 12px;
            line-height: 1.9;
            color: #667b92;
        }

        .about-quote {
            background: #f7f0e4;
            color: #17375e;
            border-radius: 10px;
            padding: 13px;
            text-align: center;
            font-style: italic;
            font-size: 12px;
            margin-top: 18px;
        }

        .skills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 15px;
        }

        .skills span {
            background: #eaf1f8;
            color: #244e76;
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 10px;
        }

        footer {
            text-align: center;
            padding: 28px 10px 10px;
            color: #8193a9;
            font-size: 11px;
        }

        @media (max-width: 900px) {
            .sidebar {
                width: 200px;
            }

            .main {
                margin-left: 200px;
                padding: 25px;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 650px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .school-logo img {
                width: 80px;
                height: 80px;
            }

            .main {
                margin-left: 0;
                padding: 20px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .welcome {
                padding: 25px;
            }

            .welcome h1 {
                font-size: 21px;
            }

            .welcome-decoration {
                display: none;
            }
        }
    </style>
</head>

<body>

    <aside class="sidebar">

        <div class="school-logo">
            <img src="assets/logo-sekolah.png" alt="Logo SMA Bangsa Utama">
            <h2>EduSpace.</h2>
            <p>LEARNING MANAGEMENT SYSTEM</p>
        </div>

        <p class="menu-title">MAIN MENU</p>

        <nav class="menu">
            <a href="index.php" class="active">🏠 Home</a>
            <a href="pages/materi.php">📚 Materi</a>
            <a href="pages/program.php">🗓️ Program Akademik</a>
            <a href="pages/absensi-guru.php">📋 Absensi</a>
            <a href="pages/diskusi.php">💬 Ruang Diskusi</a>
            <a href="pages/tugas.php">📝 Tugas & Quiz</a>
            <a href="pages/nilai.php">📊 Nilai</a>
        </nav>

        <div class="sidebar-bottom">
            Learn, Explore,<br>Achieve. ✨
        </div>

    </aside>

    <main class="main">

        <header class="topbar">
            <h2>Home</h2>

           <div class="teacher">
    <div class="teacher-info">
        <h4>Safira Maulidiya, M.Pd.</h4>
        <p>English Teacher</p>
        <a href="logout.php" class="logout-link">Logout ↗</a>
    </div>

    <div class="teacher-avatar">S</div>
</div>

        </header>

        <section class="welcome">

            <span class="welcome-label">WELCOME TO EDUSPACE</span>

            <h1>Hi Everyone! Welcome to Our English LMS Page!</h1>

            <p class="welcome-subtitle">
                Welcome to Grade 10 English Class bersama Bu Safira Maulidiya, M.Pd.!
            </p>

            <p>
                Di LMS ini, kalian bisa mengakses seluruh materi pembelajaran,
                mengumpulkan tugas, hingga berdiskusi secara interaktif untuk
                mengasah kemampuan <em>listening</em>, <em>speaking</em>,
                <em>reading</em>, dan <em>writing</em>.
            </p>

            <br>

            <p>
                <strong><em>Don't worry, learning English is fun and easy if we do it together!</em></strong>
                Mari manfaatkan platform ini dengan maksimal, tetap aktif
                berpartisipasi, and let's level up your English skills together
                this semester!
            </p>

            <div class="welcome-decoration">🎓</div>

        </section>

        <section class="stats">

            <div class="stat-card">
                <div class="stat-icon">👩‍🎓</div>
                <h2><?php echo $total_students; ?></h2>
                <p>Total Siswa</p>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🏫</div>
                <h2><?php echo $total_classes; ?></h2>
                <p>Jumlah Kelas</p>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <h2><?php echo $subject; ?></h2>
                <p>Mata Pelajaran</p>
            </div>

        </section>

        <section class="content-grid">

            <div class="content-card">

                <h3>📖 Informasi Pembelajaran</h3>

                <div class="learning-info">
                    <span>Mata Pelajaran</span>
                    <strong>Bahasa Inggris - Kelas X</strong>
                </div>

                <div class="learning-info">
                    <span>Kurikulum</span>
                    <strong>Kurikulum Merdeka - Fase E</strong>
                </div>

                <div class="learning-info">
                    <span>Semester</span>
                    <strong> Tahun Ajaran 2026/2027</strong>
                </div>

                <div class="learning-info">
                    <span>Materi Pembelajaran</span>
                    <strong>Descriptive Text</strong>
                </div>

                <div class="learning-info">
                    <span>Guru Pengampu</span>
                    <strong>Safira Maulidiya, M.Pd.</strong>
                </div>

                <div class="teacher-message">
                    <p>
                        <strong>Pesan dari Bu Safira 💙</strong><br>
                        Selamat belajar, everyone! Jangan takut untuk mencoba,
                        bertanya, dan membuat kesalahan. Karena setiap proses
                        adalah bagian dari perjalanan kalian untuk menjadi
                        lebih baik dalam berbahasa Inggris.
                    </p>
                </div>

            </div>

            <div class="content-card about-card">

                <h3>⭐ Tentang EduSpace</h3>

                <p>
                    EduSpace merupakan platform pembelajaran digital yang
                    dirancang untuk mendukung kegiatan belajar mengajar
                    secara interaktif, efektif, dan menyenangkan.
                </p>

                <br>

                <p>
                    Melalui platform ini, siswa dapat mempelajari materi,
                    mengakses video pembelajaran, mengerjakan tugas,
                    mengikuti kuis, serta berkomunikasi dengan guru.
                </p>

                <h3 style="margin-top:25px;">🎯 Our English Skills</h3>

                <div class="skills">
                    <span>Listening</span>
                    <span>Speaking</span>
                    <span>Reading</span>
                    <span>Writing</span>
                </div>

                <div class="about-quote">
                    "Let's learn and grow together!" ♡
                </div>

            </div>

        </section>

        <footer>
            © 2026 EduSpace · SMA Bangsa Utama · English Learning Platform
        </footer>

    </main>

</body>
</html>
