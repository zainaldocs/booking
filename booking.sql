CREATE DATABASE IF NOT EXISTS booking_ruangan;
USE booking_ruangan;

CREATE TABLE users_admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL
);

-- Default admin password: password
INSERT INTO users_admin (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com');

CREATE TABLE settings (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT
);

INSERT INTO settings (`setting_key`, `setting_value`) VALUES 
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('smtp_user', 'emailanda@gmail.com'),
('smtp_pass', 'passwordappgmail'),
('admin_email', 'admin@example.com');

CREATE TABLE ruangan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_ruangan VARCHAR(100) NOT NULL,
    kapasitas INT NOT NULL,
    keterangan TEXT
);

CREATE TABLE jenis_fasilitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_jenis VARCHAR(100) NOT NULL
);

CREATE TABLE fasilitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenis_fasilitas_id INT NOT NULL,
    nomor_seri VARCHAR(50) NOT NULL,
    status_kondisi ENUM('Baik', 'Rusak') DEFAULT 'Baik',
    FOREIGN KEY (jenis_fasilitas_id) REFERENCES jenis_fasilitas(id) ON DELETE CASCADE
);

CREATE TABLE departemen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_departemen VARCHAR(100) NOT NULL
);

INSERT INTO departemen (nama_departemen) VALUES 
('Produksi Solid'),
('Produksi LSS'),
('Information System'),
('RND');

CREATE TABLE peminjaman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    departemen_id INT,
    petugas_id INT,
    nama_peminjam VARCHAR(100) NOT NULL,
    email_peminjam VARCHAR(100) NOT NULL,
    keterangan TEXT,
    ruangan_id INT NOT NULL,
    tanggal DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    alasan_ditolak TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ruangan_id) REFERENCES ruangan(id) ON DELETE CASCADE,
    FOREIGN KEY (departemen_id) REFERENCES departemen(id) ON DELETE SET NULL,
    FOREIGN KEY (petugas_id) REFERENCES users_admin(id) ON DELETE SET NULL
);

CREATE TABLE peminjaman_jenis_fasilitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peminjaman_id INT NOT NULL,
    jenis_fasilitas_id INT NOT NULL,
    jumlah INT DEFAULT 1,
    FOREIGN KEY (peminjaman_id) REFERENCES peminjaman(id) ON DELETE CASCADE,
    FOREIGN KEY (jenis_fasilitas_id) REFERENCES jenis_fasilitas(id) ON DELETE CASCADE
);

CREATE TABLE detail_fasilitas_peminjaman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peminjaman_id INT NOT NULL,
    fasilitas_id INT NOT NULL,
    FOREIGN KEY (peminjaman_id) REFERENCES peminjaman(id) ON DELETE CASCADE,
    FOREIGN KEY (fasilitas_id) REFERENCES fasilitas(id) ON DELETE CASCADE
);
