-- ========================================
-- DATABASE SETUP
-- Sistem Inventaris Barang
-- ========================================

-- Buat database baru
CREATE DATABASE IF NOT EXISTS inventory_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Gunakan database
USE inventory_db;

-- ========================================
-- TABLE: Role (Peran)
-- ========================================
CREATE TABLE Role (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    nama_role VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: Pelanggan (Customer)
-- ========================================
CREATE TABLE Pelanggan (
    pelanggan_id INT PRIMARY KEY AUTO_INCREMENT,
    nama_pelanggan VARCHAR(100) NOT NULL,
    kontak_pelanggan VARCHAR(20) NOT NULL,
    poin INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: Karyawan (Employee)
-- ========================================
CREATE TABLE Karyawan (
    karyawan_id INT PRIMARY KEY AUTO_INCREMENT,
    nama_karyawan VARCHAR(100) NOT NULL,
    kontak_karyawan VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: User (User Account)
-- ========================================
CREATE TABLE User (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    karyawan_id INT NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    FOREIGN KEY (role_id) REFERENCES Role(role_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (karyawan_id) REFERENCES Karyawan(karyawan_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: Supplier
-- ========================================
CREATE TABLE Supplier (
    supplier_id INT PRIMARY KEY AUTO_INCREMENT,
    nama_supplier VARCHAR(100) NOT NULL,
    kontak_supplier VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: Brand
-- ========================================
CREATE TABLE Brand (
    brand_id INT PRIMARY KEY AUTO_INCREMENT,
    nama_brand VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: Ukuran (Size)
-- ========================================
CREATE TABLE Ukuran (
    ukuran_id INT PRIMARY KEY AUTO_INCREMENT,
    nama_ukuran VARCHAR(20) NOT NULL UNIQUE,
    satuan VARCHAR(10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: Rasa (Flavor - optional for products)
-- ========================================
CREATE TABLE Rasa (
    rasa_id INT PRIMARY KEY AUTO_INCREMENT,
    nama_rasa VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: KategoriBarang (Product Category)
-- ========================================
CREATE TABLE KategoriBarang (
    kategori_id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: Barang (Product/Item)
-- ========================================
CREATE TABLE Barang (
    barang_id INT PRIMARY KEY AUTO_INCREMENT,
    kategori_id INT NOT NULL,
    brand_id INT NOT NULL,
    rasa_id INT,
    ukuran_id INT NOT NULL,
    nama_barang VARCHAR(100) NOT NULL,
    harga_jual DECIMAL(12,2) NOT NULL,
    stok INT DEFAULT 0,
    Safety_Stock INT DEFAULT 0,
    harga_jual_satuan DECIMAL(12,2),
    ROP INT DEFAULT 0,
    FOREIGN KEY (kategori_id) REFERENCES KategoriBarang(kategori_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (brand_id) REFERENCES Brand(brand_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (rasa_id) REFERENCES Rasa(rasa_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (ukuran_id) REFERENCES Ukuran(ukuran_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: TransaksiMasuk (Incoming Transaction)
-- ========================================
CREATE TABLE TransaksiMasuk (
    masuk_id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_id INT NOT NULL,
    user_id INT NOT NULL,
    tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES Supplier(supplier_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: DetailMasuk (Incoming Transaction Detail)
-- ========================================
CREATE TABLE DetailMasuk (
    detailmasuk_id INT PRIMARY KEY AUTO_INCREMENT,
    masuk_id INT NOT NULL,
    barang_id INT NOT NULL,
    jumlah INT NOT NULL,
    harga_beli DECIMAL(12,2) NOT NULL,
    tgl_kadarluarsa DATE,
    FOREIGN KEY (masuk_id) REFERENCES TransaksiMasuk(masuk_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (barang_id) REFERENCES Barang(barang_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: TransaksiKeluar (Outgoing Transaction)
-- ========================================
CREATE TABLE TransaksiKeluar (
    keluar_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    pelanggan_id INT NOT NULL,
    tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (pelanggan_id) REFERENCES Pelanggan(pelanggan_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: DetailKeluar (Outgoing Transaction Detail)
-- ========================================
CREATE TABLE DetailKeluar (
    detailkeluar_id INT PRIMARY KEY AUTO_INCREMENT,
    keluar_id INT NOT NULL,
    barang_id INT NOT NULL,
    jumlah INT NOT NULL,
    harga_jual DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (keluar_id) REFERENCES TransaksiKeluar(keluar_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (barang_id) REFERENCES Barang(barang_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- INDEXES FOR PERFORMANCE
-- ========================================
CREATE INDEX idx_barang_kategori ON Barang(kategori_id);
CREATE INDEX idx_barang_brand ON Barang(brand_id);
CREATE INDEX idx_barang_stok ON Barang(stok);
CREATE INDEX idx_user_role ON User(role_id);
CREATE INDEX idx_transaksikelhuar_user ON TransaksiKeluar(user_id);
CREATE INDEX idx_transaksikelhuar_pelanggan ON TransaksiKeluar(pelanggan_id);
CREATE INDEX idx_transaksimasuk_supplier ON TransaksiMasuk(supplier_id);
CREATE INDEX idx_transaksimasuk_tanggal ON TransaksiMasuk(tanggal);
CREATE INDEX idx_detailkelhuar_barang ON DetailKeluar(barang_id);
CREATE INDEX idx_detailmasuk_barang ON DetailMasuk(barang_id);

-- ========================================
-- SAMPLE DATA FOR TESTING
-- ========================================

-- Insert Role
INSERT INTO Role (nama_role) VALUES 
('Administrator'),
('Manager'),
('Staff');

-- Insert Karyawan
INSERT INTO Karyawan (nama_karyawan, kontak_karyawan) VALUES 
('Fernando Admin', '081234567890'),
('Manager User', '081234567891'),
('Staff User', '081234567892');

-- Insert User (password: admin123, manager123, staff123)
INSERT INTO User (role_id, karyawan_id, nama_lengkap, username, password) VALUES 
(1, 1, 'Fernando', 'admin@gmail.com', 'admin123'),
(2, 2, 'Manager User', 'manager@gmail.com', 'manager123'),
(3, 3, 'Staff User', 'staff@gmail.com', 'staff123');

-- Insert Supplier
INSERT INTO Supplier (nama_supplier, kontak_supplier) VALUES 
('PT Supplier Utama', '021-12345678'),
('CV Supplier Jaya', '021-87654321'),
('Toko Grosir ABC', '021-55555555');

-- Insert Pelanggan
INSERT INTO Pelanggan (nama_pelanggan, kontak_pelanggan, poin) VALUES 
('Toko Cahaya', '081234567890', 100),
('Warung Berkah', '081234567891', 50),
('Minimarket Sejahtera', '081234567892', 200);

-- Insert Brand
INSERT INTO Brand (nama_brand) VALUES 
('Mamy Poko'),
('S26 Promina'),
('Bebelac'),
('Pepsodent'),
('Lifebuoy');

-- Insert Kategori
INSERT INTO KategoriBarang (nama_kategori) VALUES 
('Popok Bayi'),
('Susu Formula'),
('Makanan Bayi'),
('Perlengkapan Mandi'),
('Vitamin & Suplemen');

-- Insert Ukuran
INSERT INTO Ukuran (nama_ukuran, satuan) VALUES 
('Small', 'pcs'),
('Medium', 'pcs'),
('Large', 'pcs'),
('1600g', 'box'),
('700g', 'box'),
('Pack M/L', 'pack');

-- Insert Rasa (opsional untuk produk tertentu)
INSERT INTO Rasa (nama_rasa) VALUES 
('Vanilla'),
('Cokelat'),
('Strawberry'),
('Madu'),
('Original');

-- Insert Barang (Sesuai dengan gambar Figma: 4 barang dengan stok minimum)
INSERT INTO Barang (kategori_id, brand_id, rasa_id, ukuran_id, nama_barang, harga_jual, stok, Safety_Stock, harga_jual_satuan, ROP) VALUES 
(2, 2, 5, 4, 'S26 Promina Gold Tahap 4 1600g', 450000.00, 100, 150, 450000.00, 200),
(1, 1, NULL, 6, 'Mamy Poko Air Fit Pack M/L', 85000.00, 20, 50, 85000.00, 80),
(3, 3, 1, 5, 'Bebelac Gold 3 Madu & Vanilla 700g', 180000.00, 35, 100, 180000.00, 150),
(4, 1, NULL, 1, 'Mamy Poko Wipes Pouch', 25000.00, 15, 30, 25000.00, 50);

-- Insert beberapa barang lagi untuk testing
INSERT INTO Barang (kategori_id, brand_id, rasa_id, ukuran_id, nama_barang, harga_jual, stok, Safety_Stock, harga_jual_satuan, ROP) VALUES 
(4, 4, NULL, 1, 'Pepsodent Whitening 190g', 18000.00, 200, 50, 18000.00, 100),
(4, 5, NULL, 1, 'Lifebuoy Soap Bar 110g', 5500.00, 300, 100, 5500.00, 150),
(2, 2, 1, 4, 'S26 Promina Gold Tahap 3 1600g', 420000.00, 80, 100, 420000.00, 150),
(1, 1, NULL, 2, 'Mamy Poko Pants Extra Dry M', 90000.00, 150, 80, 90000.00, 120),
(3, 3, 2, 5, 'Bebelac Gold 4 Cokelat 700g', 190000.00, 60, 80, 190000.00, 120);

-- Insert sample TransaksiMasuk (untuk data grafik)
INSERT INTO TransaksiMasuk (supplier_id, user_id, tanggal) VALUES 
(1, 1, '2025-09-15 10:00:00'),
(2, 1, '2025-10-10 14:30:00'),
(1, 1, '2025-11-05 09:15:00');

-- Insert DetailMasuk
INSERT INTO DetailMasuk (masuk_id, barang_id, jumlah, harga_beli, tgl_kadarluarsa) VALUES 
(1, 1, 50, 420000.00, '2027-12-31'),
(1, 2, 100, 80000.00, '2026-06-30'),
(2, 3, 150, 170000.00, '2027-03-31'),
(2, 4, 200, 23000.00, '2026-12-31'),
(3, 1, 150, 420000.00, '2027-12-31');

-- Insert sample TransaksiKeluar (untuk data grafik)
INSERT INTO TransaksiKeluar (user_id, pelanggan_id, tanggal) VALUES 
(1, 1, '2025-09-20 11:00:00'),
(1, 2, '2025-10-15 15:30:00'),
(1, 3, '2025-11-08 10:15:00');

-- Insert DetailKeluar
INSERT INTO DetailKeluar (keluar_id, barang_id, jumlah, harga_jual) VALUES 
(1, 1, 20, 450000.00),
(1, 2, 80, 85000.00),
(2, 3, 50, 180000.00),
(2, 4, 100, 25000.00),
(3, 1, 30, 450000.00);

-- ========================================
-- VERIFICATION
-- ========================================
SELECT 'Database setup completed successfully!' as Status;
SELECT COUNT(*) as Total_Tables FROM information_schema.tables WHERE table_schema = 'inventory_db';
SELECT COUNT(*) as Total_Users FROM User;
SELECT COUNT(*) as Total_Barang FROM Barang;

