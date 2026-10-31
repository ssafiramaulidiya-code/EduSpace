CREATE DATABASE IF NOT EXISTS eduspace CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eduspace;

CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    CONSTRAINT fk_students_class FOREIGN KEY (class_id) REFERENCES classes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uq_student_per_class (class_id, student_name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    session_code VARCHAR(10) DEFAULT NULL,
    status ENUM('Hadir','Izin','Sakit','Alfa') NOT NULL,
    note VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_attendance_student FOREIGN KEY (student_id) REFERENCES students(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY uq_student_date (student_id, attendance_date),
    INDEX idx_attendance_date (attendance_date)
) ENGINE=InnoDB;

INSERT IGNORE INTO classes (class_name) VALUES ('X-A'), ('X-B'), ('X-C');

INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Adit' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Agus' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Aldo' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Bagas' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Bintang' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Bunga' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Cinta' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Citra' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Candra' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Daffa' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Dian' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Dina' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Eka' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Erlang' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Elsa' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Fajar' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Fani' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Fikri' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Gita' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Galang' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Grace' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Hana' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Hesti' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Haikal' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Indra' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Irfan' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Intan' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Jaka' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Joni' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Johan' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Kiki' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Kartika' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Kania' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Lia' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Laras' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Lutfi' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Maya' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Mitha' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Monika' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Naufal' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Nico' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Nabila' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Olivia' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Oky' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Okta' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Putra' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Pras' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Pram' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Qila' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Qori' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Qinan' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Rian' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Rizky' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Rere' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Siti' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Sari' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Surya' FROM classes WHERE class_name = 'X-C';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Tio' FROM classes WHERE class_name = 'X-A';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Tania' FROM classes WHERE class_name = 'X-B';
INSERT IGNORE INTO students (class_id, student_name)
SELECT id, 'Tyas' FROM classes WHERE class_name = 'X-C';
