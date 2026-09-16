CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(150) NULL,
    telepon VARCHAR(30) NULL,
    bio VARCHAR(255) NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_permissions (
    user_id INT NOT NULL,
    permission VARCHAR(50) NOT NULL,
    PRIMARY KEY (user_id, permission),
    CONSTRAINT fk_permission_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL UNIQUE,
    keterangan VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lokasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gedung VARCHAR(100) NOT NULL,
    ruang VARCHAR(100) NULL,
    rak VARCHAR(100) NULL,
    keterangan VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(50) NOT NULL UNIQUE,
    nama VARCHAR(150) NOT NULL,
    kategori_id INT NULL,
    stok INT NOT NULL DEFAULT 0,
    satuan VARCHAR(30) NOT NULL DEFAULT 'Unit',
    lokasi_id INT NULL,
    kondisi ENUM('Baik', 'Rusak Ringan', 'Rusak Berat') NOT NULL DEFAULT 'Baik',
    status ENUM('Tersedia', 'Sedang Digunakan', 'Dipinjam', 'Dalam Perbaikan', 'Terpakai', 'Rusak') NOT NULL DEFAULT 'Tersedia',
    tipe_barang VARCHAR(20) NOT NULL DEFAULT 'umum',
    tanggal_masuk DATE NULL,
    tanggal_expired DATE NULL,
    deskripsi TEXT NULL,
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_barang_kategori FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL,
    CONSTRAINT fk_barang_lokasi FOREIGN KEY (lokasi_id) REFERENCES lokasi(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS barang_unit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barang_id INT NOT NULL,
    kode_unit VARCHAR(80) NOT NULL UNIQUE,
    nama_unit VARCHAR(150) NULL,
    lokasi_id INT NULL,
    spesifikasi TEXT NULL,
    kondisi ENUM('Baik', 'Rusak Ringan', 'Rusak Berat') NOT NULL DEFAULT 'Baik',
    catatan TEXT NULL,
    tanggal_expired DATE NULL,
    status ENUM('tersedia', 'keluar', 'terpakai', 'rusak') NOT NULL DEFAULT 'tersedia',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_unit_barang FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE CASCADE,
    CONSTRAINT fk_unit_lokasi FOREIGN KEY (lokasi_id) REFERENCES lokasi(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barang_id INT NOT NULL,
    jenis ENUM('masuk', 'keluar') NOT NULL,
    jumlah INT NOT NULL,
    tanggal DATE NOT NULL,
    keterangan TEXT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_trans_barang FOREIGN KEY (barang_id) REFERENCES barang(id),
    CONSTRAINT fk_trans_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS room_visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(120) NOT NULL,
    identitas VARCHAR(120) NULL,
    ruangan VARCHAR(150) NOT NULL,
    keperluan VARCHAR(255) NULL,
    kode_scan VARCHAR(100) NOT NULL,
    barang_id INT NULL,
    unit_id INT NULL,
    tanggal DATE NOT NULL,
    jam TIME NOT NULL,
    user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY room_visits_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS unit_status_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    barang_id INT NOT NULL,
    old_status VARCHAR(20) NOT NULL,
    new_status VARCHAR(20) NOT NULL,
    user_id INT NOT NULL,
    keterangan VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY unit_status_logs_created (created_at),
    CONSTRAINT fk_unit_status_log_unit FOREIGN KEY (unit_id) REFERENCES barang_unit(id) ON DELETE CASCADE,
    CONSTRAINT fk_unit_status_log_barang FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE CASCADE,
    CONSTRAINT fk_unit_status_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
