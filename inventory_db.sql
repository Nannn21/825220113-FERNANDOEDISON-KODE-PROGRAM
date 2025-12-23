-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 23, 2025 at 07:30 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventory_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `barang_id` int(11) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `brand_id` int(11) NOT NULL,
  `rasa_id` int(11) DEFAULT NULL,
  `ukuran_id` int(11) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `harga_jual` decimal(12,2) NOT NULL,
  `stok` int(11) DEFAULT 0,
  `Safety_Stock` int(11) DEFAULT 0,
  `harga_jual_satuan` decimal(12,2) DEFAULT NULL,
  `ROP` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`barang_id`, `kategori_id`, `brand_id`, `rasa_id`, `ukuran_id`, `nama_barang`, `harga_jual`, `stok`, `Safety_Stock`, `harga_jual_satuan`, `ROP`) VALUES
(16, 8, 8, NULL, 8, 'A+ Gentle Care Neura Pro Tahap 3', 448000.00, 18, 59, NULL, 62),
(17, 10, 9, NULL, 9, 'Sarung Tangan Anak', 9000.00, 17, 1, NULL, 7),
(18, 11, 10, NULL, 10, 'Air Fit Preemie', 108000.00, 12, 1, NULL, 9),
(19, 4, 11, NULL, 11, 'Softener', 15000.00, 22, 93, NULL, 98),
(20, 4, 10, NULL, 9, 'Tissue Basah Royal Soft', 25000.00, 12, 1, NULL, 6),
(21, 4, 12, NULL, 12, 'Set Cream + Cologne + Hair Lotion', 90000.00, 7, 1, NULL, 4),
(22, 12, 11, NULL, 13, 'Pelicin & Pewangi Pakaian', 10000.00, 8, 1, NULL, 4),
(23, 4, 11, NULL, 14, 'Kids Mouthwash', 10000.00, 4, 63, NULL, 68),
(24, 13, 13, NULL, 15, 'Kantong Asi', 25000.00, 4, 1, NULL, 2),
(25, 11, 10, NULL, 10, 'Air Fit Pack', 150000.00, 30, 5, NULL, 5),
(26, 13, 12, NULL, 10, 'Kantong Asi', 100000.00, 10, 0, NULL, 0),
(29, 10, 12, NULL, 17, 'Setelan anak 5 tahun', 120000.00, 10, 0, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `brand`
--

CREATE TABLE `brand` (
  `brand_id` int(11) NOT NULL,
  `nama_brand` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brand`
--

INSERT INTO `brand` (`brand_id`, `nama_brand`) VALUES
(9, 'Bagus'),
(12, 'Bambi'),
(8, 'Enfagrow'),
(13, 'Gabag'),
(10, 'Mamy Poko'),
(11, 'My Baby');

-- --------------------------------------------------------

--
-- Table structure for table `detailkeluar`
--

CREATE TABLE `detailkeluar` (
  `detailkeluar_id` int(11) NOT NULL,
  `keluar_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_jual` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `detailkeluar`
--

INSERT INTO `detailkeluar` (`detailkeluar_id`, `keluar_id`, `barang_id`, `jumlah`, `harga_jual`) VALUES
(11, 9, 22, 20, 10000.00),
(12, 10, 21, 30, 90000.00),
(13, 11, 23, 30, 10000.00),
(14, 12, 23, 13, 10000.00),
(15, 13, 17, 13, 9000.00),
(16, 14, 19, 20, 15000.00),
(18, 16, 23, 3, 10000.00),
(19, 17, 16, 21, 448000.00),
(20, 18, 24, 10, 25000.00),
(21, 19, 20, 45, 25000.00),
(22, 20, 19, 28, 15000.00),
(23, 21, 17, 40, 9000.00),
(24, 22, 16, 1, 448000.00),
(25, 23, 18, 70, 108000.00),
(26, 24, 18, 8, 108000.00);

-- --------------------------------------------------------

--
-- Table structure for table `detailmasuk`
--

CREATE TABLE `detailmasuk` (
  `detailmasuk_id` int(11) NOT NULL,
  `masuk_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_beli` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `detailmasuk`
--

INSERT INTO `detailmasuk` (`detailmasuk_id`, `masuk_id`, `barang_id`, `jumlah`, `harga_beli`) VALUES
(16, 14, 21, 10, 0.00),
(17, 15, 22, 3, 0.00),
(18, 16, 18, 50, 0.00),
(19, 17, 17, 40, 0.00),
(20, 18, 21, 12, 0.00),
(21, 19, 20, 23, 0.00),
(23, 21, 22, 5, 0.00),
(24, 22, 19, 10, 0.00),
(25, 23, 23, 40, 0.00),
(26, 24, 20, 14, 0.00),
(27, 25, 19, 20, 0.00),
(28, 26, 24, 4, 0.00),
(29, 27, 16, 10, 0.00),
(30, 28, 18, 10, 0.00),
(31, 29, 25, 10, 0.00),
(32, 30, 25, 10, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `karyawan`
--

CREATE TABLE `karyawan` (
  `karyawan_id` int(11) NOT NULL,
  `nama_karyawan` varchar(100) NOT NULL,
  `kontak_karyawan` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `karyawan`
--

INSERT INTO `karyawan` (`karyawan_id`, `nama_karyawan`, `kontak_karyawan`) VALUES
(1, 'Fernando Admin', '081234567890'),
(2, 'Manager User', '081234567891'),
(3, 'Staff User', '081234567892'),
(4, 'Fernando', '081234567890'),
(5, 'Karyawan User', '081234567891'),
(6, 'Fernando', '000000000000'),
(7, 'Fernandod', '000000000000'),
(8, 'Tes', '000000000000'),
(9, 'Jonathan', '000000000000'),
(10, 'Fernando', '000000000000'),
(11, 'Jonathan', '000000000000'),
(12, 'Yusuf', '000000000000'),
(13, 'Yuan', '000000000000'),
(14, 'Tess1', '000000000000'),
(15, 'Tess2', '000000000000'),
(16, 'Tess3', '000000000000'),
(17, 'Tess4', '000000000000'),
(18, 'Jonathan', '000000000000');

-- --------------------------------------------------------

--
-- Table structure for table `kategoribarang`
--

CREATE TABLE `kategoribarang` (
  `kategori_id` int(11) NOT NULL,
  `nama_kategori` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kategoribarang`
--

INSERT INTO `kategoribarang` (`kategori_id`, `nama_kategori`) VALUES
(3, 'Makanan Bayi'),
(10, 'Pakaian'),
(13, 'Perlengkapan Ibu'),
(4, 'Perlengkapan Mandi'),
(11, 'Popok'),
(12, 'Sabun Pakaian'),
(8, 'Susu');

-- --------------------------------------------------------

--
-- Table structure for table `pelanggan`
--

CREATE TABLE `pelanggan` (
  `pelanggan_id` int(11) NOT NULL,
  `nama_pelanggan` varchar(100) NOT NULL,
  `kontak_pelanggan` varchar(20) NOT NULL,
  `umur_anak` int(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pelanggan`
--

INSERT INTO `pelanggan` (`pelanggan_id`, `nama_pelanggan`, `kontak_pelanggan`, `umur_anak`) VALUES
(1, 'Toko Cahayaa', '08123456721', 8),
(2, 'Warung Berkah', '081234567891', 8),
(3, 'Minimarket Sejahtera', '081234567892', 10),
(4, 'Jamal', '12121232311', 12),
(6, 'Nita', '21321321321', 13),
(7, 'Aaron', '21332132113', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rasa`
--

CREATE TABLE `rasa` (
  `rasa_id` int(11) NOT NULL,
  `nama_rasa` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rasa`
--

INSERT INTO `rasa` (`rasa_id`, `nama_rasa`) VALUES
(2, 'Cokelat'),
(4, 'Madu'),
(3, 'Strawberry'),
(7, 'Vanilla');

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `role_id` int(11) NOT NULL,
  `nama_role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`role_id`, `nama_role`) VALUES
(2, 'Karyawan'),
(1, 'Superadmin');

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `supplier_id` int(11) NOT NULL,
  `nama_supplier` varchar(100) NOT NULL,
  `kontak_supplier` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`supplier_id`, `nama_supplier`, `kontak_supplier`) VALUES
(2, 'CV Supplier Jaya', '88888888888'),
(6, 'PT Supplier Utama', '02122121123'),
(7, 'PT Bambi', '12112121222'),
(8, 'PT Pampers', '12121232321'),
(9, 'PT Maju Sejahtera Babyshop', '1323123131131'),
(10, 'CV Sumber Rezeki Mandiri', '3634355345453'),
(11, 'PT Anugerah Ibu & Anak', '5364364354353'),
(12, 'CV Nusantara Retail Supply', '65733453532'),
(13, 'PT Global Sentosa Distribusi', '352535436435'),
(14, 'CV Berkah Putra Abadi', '6435242342423'),
(15, 'PT Prima Keluarga Indonesia', '6346353534242');

-- --------------------------------------------------------

--
-- Table structure for table `transaksikeluar`
--

CREATE TABLE `transaksikeluar` (
  `keluar_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pelanggan_id` int(11) NOT NULL,
  `tanggal` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transaksikeluar`
--

INSERT INTO `transaksikeluar` (`keluar_id`, `user_id`, `pelanggan_id`, `tanggal`) VALUES
(9, 4, 3, '2025-11-06 15:28:47'),
(10, 4, 6, '2025-11-09 15:28:58'),
(11, 4, 3, '2025-11-10 15:29:07'),
(12, 4, 3, '2025-11-06 15:29:32'),
(13, 4, 3, '2025-11-20 15:29:37'),
(14, 4, 6, '2025-11-04 15:29:45'),
(16, 4, 3, '2025-11-26 15:30:06'),
(17, 4, 4, '2025-11-18 15:30:23'),
(18, 4, 3, '2025-11-10 15:30:49'),
(19, 4, 3, '2025-11-19 15:32:12'),
(20, 4, 4, '2025-11-20 15:39:04'),
(21, 4, 7, '2025-11-20 16:05:58'),
(22, 11, 4, '2025-11-20 16:08:21'),
(23, 4, 7, '2025-12-11 17:38:55'),
(24, 4, 4, '2025-12-11 17:39:23');

-- --------------------------------------------------------

--
-- Table structure for table `transaksimasuk`
--

CREATE TABLE `transaksimasuk` (
  `masuk_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tanggal` datetime DEFAULT current_timestamp(),
  `tanggal_pesan` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transaksimasuk`
--

INSERT INTO `transaksimasuk` (`masuk_id`, `supplier_id`, `user_id`, `tanggal`, `tanggal_pesan`) VALUES
(14, 11, 4, '2025-11-01 15:26:36', '2025-11-01 15:26:36'),
(15, 13, 4, '2025-11-02 15:27:00', '2025-11-02 15:27:00'),
(16, 10, 4, '2025-11-03 15:27:20', '2025-11-03 15:27:20'),
(17, 11, 4, '2025-11-20 15:27:27', '2025-11-20 15:27:27'),
(18, 7, 4, '2025-11-04 15:27:36', '2025-11-04 15:27:36'),
(19, 7, 4, '2025-11-20 15:27:43', '2025-11-20 15:27:43'),
(21, 15, 4, '2025-11-13 15:28:00', '2025-11-13 15:28:00'),
(22, 6, 4, '2025-11-20 15:28:08', '2025-11-20 15:28:08'),
(23, 11, 4, '2025-11-16 15:28:17', '2025-11-16 15:28:17'),
(24, 11, 4, '2025-11-04 15:28:29', '2025-11-04 15:28:29'),
(25, 7, 4, '2025-11-20 16:04:50', '2025-11-20 16:04:50'),
(26, 7, 11, '2025-11-13 16:08:06', '2025-11-13 16:08:06'),
(27, 12, 4, '2025-12-12 05:25:20', '2025-12-12 05:25:20'),
(28, 12, 14, '2025-12-10 05:31:17', '2025-12-10 05:31:17'),
(29, 12, 14, '2025-12-08 05:33:39', '2025-12-08 05:33:39'),
(30, 12, 4, '2025-12-24 19:29:48', '2025-12-22 19:29:48');

-- --------------------------------------------------------

--
-- Table structure for table `ukuran`
--

CREATE TABLE `ukuran` (
  `ukuran_id` int(11) NOT NULL,
  `nama_ukuran` varchar(20) NOT NULL,
  `satuan` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ukuran`
--

INSERT INTO `ukuran` (`ukuran_id`, `nama_ukuran`, `satuan`) VALUES
(8, '800', 'gr'),
(9, '50', 'Pcs'),
(10, '26', 'Pcs'),
(11, '675', 'ML'),
(12, '30', 'ML'),
(13, '300', 'ML'),
(14, '100', 'ML'),
(15, '120', 'ML'),
(16, '700', 'gr'),
(17, '1', 'Pcs'),
(20, '10', 'Gr');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `karyawan_id` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `role_id`, `karyawan_id`, `nama_lengkap`, `username`, `password`) VALUES
(4, 1, 4, 'Fernando', 'fernando@gmail.com', 'password123'),
(11, 2, 11, 'Jonathan', 'karyawan@gmail.com', '$2y$10$v0aaMRoKlB2tGB9X17fPiOo.tm56j6/LVhO86X4iRccetSBmc8vSG'),
(12, 2, 12, 'Yusuf', 'Yusuf123@gmail.com', '$2y$10$itn1jjbKU1Nzv0Yz5q7lSev0VyUZ36hLV89Afgz4ss/2VXavOCKn.'),
(13, 1, 13, 'Yuan', 'yuan123@gmail.com', '$2y$10$1QuTtSAiVaftcV7nDQiM2O56GoycaY1KEusVzaHR2Bk.H1GBwHJJS'),
(14, 1, 14, 'Tess1', 'Tess1@gmail.com', '$2y$10$3jSazG2IoO/JwuCsv8gxoO9RFN5ttJ7SaPCYjqCHamX/Hi9TsZRZW'),
(17, 1, 17, 'Tess4', 'Tess4@gmail.com', '$2y$10$krviGihCmRguOPTJ1U00W.FuyPXNNIy92BxvxZKfGrpKHk6W/lSuS'),
(18, 2, 18, 'Jonathan', 'karyawan1@gmail.com', '$2y$10$LAXi0A3lLp/jjoAagNwdn.ny0yiesKgRkY2TD.YaxeIghUa./aO6W');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`barang_id`),
  ADD KEY `rasa_id` (`rasa_id`),
  ADD KEY `ukuran_id` (`ukuran_id`),
  ADD KEY `idx_barang_kategori` (`kategori_id`),
  ADD KEY `idx_barang_brand` (`brand_id`),
  ADD KEY `idx_barang_stok` (`stok`);

--
-- Indexes for table `brand`
--
ALTER TABLE `brand`
  ADD PRIMARY KEY (`brand_id`),
  ADD UNIQUE KEY `nama_brand` (`nama_brand`);

--
-- Indexes for table `detailkeluar`
--
ALTER TABLE `detailkeluar`
  ADD PRIMARY KEY (`detailkeluar_id`),
  ADD KEY `keluar_id` (`keluar_id`),
  ADD KEY `idx_detailkelhuar_barang` (`barang_id`);

--
-- Indexes for table `detailmasuk`
--
ALTER TABLE `detailmasuk`
  ADD PRIMARY KEY (`detailmasuk_id`),
  ADD KEY `masuk_id` (`masuk_id`),
  ADD KEY `idx_detailmasuk_barang` (`barang_id`);

--
-- Indexes for table `karyawan`
--
ALTER TABLE `karyawan`
  ADD PRIMARY KEY (`karyawan_id`);

--
-- Indexes for table `kategoribarang`
--
ALTER TABLE `kategoribarang`
  ADD PRIMARY KEY (`kategori_id`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`);

--
-- Indexes for table `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`pelanggan_id`);

--
-- Indexes for table `rasa`
--
ALTER TABLE `rasa`
  ADD PRIMARY KEY (`rasa_id`),
  ADD UNIQUE KEY `nama_rasa` (`nama_rasa`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `nama_role` (`nama_role`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `transaksikeluar`
--
ALTER TABLE `transaksikeluar`
  ADD PRIMARY KEY (`keluar_id`),
  ADD KEY `idx_transaksikelhuar_user` (`user_id`),
  ADD KEY `idx_transaksikelhuar_pelanggan` (`pelanggan_id`);

--
-- Indexes for table `transaksimasuk`
--
ALTER TABLE `transaksimasuk`
  ADD PRIMARY KEY (`masuk_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_transaksimasuk_supplier` (`supplier_id`);

--
-- Indexes for table `ukuran`
--
ALTER TABLE `ukuran`
  ADD PRIMARY KEY (`ukuran_id`),
  ADD UNIQUE KEY `nama_ukuran` (`nama_ukuran`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `karyawan_id` (`karyawan_id`),
  ADD KEY `idx_user_role` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `barang_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `brand`
--
ALTER TABLE `brand`
  MODIFY `brand_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `detailkeluar`
--
ALTER TABLE `detailkeluar`
  MODIFY `detailkeluar_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `detailmasuk`
--
ALTER TABLE `detailmasuk`
  MODIFY `detailmasuk_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `karyawan`
--
ALTER TABLE `karyawan`
  MODIFY `karyawan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `kategoribarang`
--
ALTER TABLE `kategoribarang`
  MODIFY `kategori_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `pelanggan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `rasa`
--
ALTER TABLE `rasa`
  MODIFY `rasa_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `transaksikeluar`
--
ALTER TABLE `transaksikeluar`
  MODIFY `keluar_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `transaksimasuk`
--
ALTER TABLE `transaksimasuk`
  MODIFY `masuk_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `ukuran`
--
ALTER TABLE `ukuran`
  MODIFY `ukuran_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang`
--
ALTER TABLE `barang`
  ADD CONSTRAINT `barang_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategoribarang` (`kategori_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `barang_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brand` (`brand_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `barang_ibfk_3` FOREIGN KEY (`rasa_id`) REFERENCES `rasa` (`rasa_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `barang_ibfk_4` FOREIGN KEY (`ukuran_id`) REFERENCES `ukuran` (`ukuran_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `detailkeluar`
--
ALTER TABLE `detailkeluar`
  ADD CONSTRAINT `detailkeluar_ibfk_1` FOREIGN KEY (`keluar_id`) REFERENCES `transaksikeluar` (`keluar_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `detailkeluar_ibfk_2` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`barang_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `detailmasuk`
--
ALTER TABLE `detailmasuk`
  ADD CONSTRAINT `detailmasuk_ibfk_1` FOREIGN KEY (`masuk_id`) REFERENCES `transaksimasuk` (`masuk_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `detailmasuk_ibfk_2` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`barang_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transaksikeluar`
--
ALTER TABLE `transaksikeluar`
  ADD CONSTRAINT `transaksikeluar_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `transaksikeluar_ibfk_2` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`pelanggan_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transaksimasuk`
--
ALTER TABLE `transaksimasuk`
  ADD CONSTRAINT `transaksimasuk_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`supplier_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `transaksimasuk_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_ibfk_2` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`karyawan_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
