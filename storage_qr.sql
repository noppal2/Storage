-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 17, 2026 at 08:45 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `storage_qr`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id` int(11) NOT NULL,
  `kode` varchar(50) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `satuan` varchar(30) NOT NULL DEFAULT 'Unit',
  `lokasi_id` int(11) DEFAULT NULL,
  `kondisi` enum('Baik','Rusak Ringan','Rusak Berat') NOT NULL DEFAULT 'Baik',
  `status` enum('Tersedia','Sedang Digunakan','Dipinjam','Dalam Perbaikan','Terpakai','Rusak') NOT NULL DEFAULT 'Tersedia',
  `tipe_barang` varchar(20) NOT NULL DEFAULT 'umum',
  `tanggal_masuk` date DEFAULT NULL,
  `tanggal_expired` date DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id`, `kode`, `nama`, `kategori_id`, `stok`, `satuan`, `lokasi_id`, `kondisi`, `status`, `tipe_barang`, `tanggal_masuk`, `tanggal_expired`, `deskripsi`, `aktif`, `created_at`, `updated_at`) VALUES
(1, 'BRG-000001', 'CCTV', 1, 47, 'Unit', 1, 'Baik', 'Dalam Perbaikan', 'umum', '2026-09-10', NULL, NULL, 1, '2026-09-10 14:45:28', '2026-09-16 19:56:17'),
(2, 'BRG-000002', 'a', 2, 36, 'Unit', 1, 'Baik', 'Tersedia', 'umum', '2026-09-10', NULL, NULL, 1, '2026-09-10 16:22:19', '2026-09-16 16:54:45'),
(3, 'BRG-000003', 'pc pa samsul', 2, 2, 'Unit', 1, 'Rusak Ringan', 'Dalam Perbaikan', 'umum', '2026-09-12', NULL, 'ram 18(1 keping) ssd 1tb', 0, '2026-09-12 09:02:00', '2026-09-12 09:16:30'),
(4, 'BRG-000004', 'router', 1, 15, 'Unit', 1, 'Baik', 'Tersedia', 'umum', '2026-09-12', NULL, 'barang baru masuk hari ini', 1, '2026-09-12 09:24:07', '2026-09-12 09:51:11'),
(5, 'BRG-000005', 'kacamata', 1, 114, 'Unit', 1, 'Baik', 'Tersedia', 'umum', '2026-09-12', NULL, 'kacamata minum kuda', 1, '2026-09-12 09:45:05', '2026-09-16 20:53:06'),
(6, 'BRG-000006', 'poe', 1, 0, 'Unit', 1, 'Baik', 'Tersedia', 'umum', '2026-09-12', NULL, NULL, 0, '2026-09-12 10:27:40', '2026-09-12 10:28:00'),
(7, 'BRG-000007', 'TINTA', 3, 19, 'Unit', 3, 'Baik', 'Tersedia', 'habis_pakai', '2026-09-13', '2026-09-14', 'TINTA PRINTER', 1, '2026-09-12 18:20:29', '2026-09-16 01:20:23'),
(8, 'BRG-000008', 'LISENSI ANTI VIRUS', 4, 2, 'Unit', NULL, 'Baik', 'Sedang Digunakan', 'lisensi', '2026-09-16', '2026-12-16', NULL, 1, '2026-09-16 01:22:53', '2026-09-16 01:23:11'),
(9, 'BRG-000009', 'ACCESS POINT', 1, 34, 'Unit', 5, 'Baik', 'Tersedia', 'umum', '2026-09-16', NULL, NULL, 1, '2026-09-16 01:30:01', '2026-09-17 07:55:46'),
(10, 'BRG-000010', 'tes', 1, 12, 'Unit', 3, 'Baik', 'Tersedia', 'umum', '2026-09-17', NULL, NULL, 1, '2026-09-16 20:28:43', '2026-09-16 20:28:43');

-- --------------------------------------------------------

--
-- Table structure for table `barang_unit`
--

CREATE TABLE `barang_unit` (
  `id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `kode_unit` varchar(80) NOT NULL,
  `nama_unit` varchar(150) DEFAULT NULL,
  `lokasi_id` int(11) DEFAULT NULL,
  `spesifikasi` text DEFAULT NULL,
  `kondisi` enum('Baik','Rusak Ringan','Rusak Berat') NOT NULL DEFAULT 'Baik',
  `catatan` text DEFAULT NULL,
  `tanggal_expired` date DEFAULT NULL,
  `status` enum('tersedia','keluar','terpakai','rusak') NOT NULL DEFAULT 'tersedia',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `barang_unit`
--

INSERT INTO `barang_unit` (`id`, `barang_id`, `kode_unit`, `nama_unit`, `lokasi_id`, `spesifikasi`, `kondisi`, `catatan`, `tanggal_expired`, `status`, `created_at`) VALUES
(1, 1, 'BRG-000001-001', 'CCTV 001', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(2, 1, 'BRG-000001-002', 'CCTV 002', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(3, 1, 'BRG-000001-003', 'CCTV 003', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(4, 1, 'BRG-000001-004', 'CCTV 004', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(5, 1, 'BRG-000001-005', 'CCTV 005', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(6, 1, 'BRG-000001-006', 'CCTV 006', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(7, 1, 'BRG-000001-007', 'CCTV 007', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(8, 1, 'BRG-000001-008', 'CCTV 008', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(9, 1, 'BRG-000001-009', 'CCTV 009', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(10, 1, 'BRG-000001-010', 'CCTV 010', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(11, 1, 'BRG-000001-011', 'CCTV 011', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(12, 1, 'BRG-000001-012', 'CCTV 012', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(13, 1, 'BRG-000001-013', 'CCTV 013', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(14, 1, 'BRG-000001-014', 'CCTV 014', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(15, 1, 'BRG-000001-015', 'CCTV 015', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(16, 1, 'BRG-000001-016', 'CCTV 016', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(17, 1, 'BRG-000001-017', 'CCTV 017', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(18, 1, 'BRG-000001-018', 'CCTV 018', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(19, 1, 'BRG-000001-019', 'CCTV 019', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(20, 1, 'BRG-000001-020', 'CCTV 020', 1, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(21, 1, 'BRG-000001-021', 'CCTV 021', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-11 02:35:30'),
(22, 1, 'BRG-000001-022', 'CCTV 022', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-11 02:35:30'),
(23, 1, 'BRG-000001-023', 'CCTV 023', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-11 02:35:30'),
(24, 1, 'BRG-000001-024', 'CCTV 024', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-11 02:35:30'),
(25, 1, 'BRG-000001-025', 'CCTV 025', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-11 02:35:30'),
(26, 1, 'BRG-000001-026', 'CCTV 026', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-11 02:35:30'),
(27, 1, 'BRG-000001-027', 'CCTV 027', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-11 02:35:30'),
(28, 1, 'BRG-000001-028', 'CCTV 028', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-11 02:35:30'),
(29, 1, 'BRG-000001-029', 'CCTV 029', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-11 02:35:30'),
(30, 1, 'BRG-000001-030', 'CCTV 030', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-11 02:35:30'),
(31, 1, 'BRG-000001-031', 'CCTV 031', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-11 02:35:30'),
(32, 1, 'BRG-000001-032', 'CCTV 032', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-11 02:35:30'),
(33, 2, 'BRG-000002-001', 'Laptop pa son', 1, 'Ram 16gb  (2 keping), Hdd 1tb', 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(34, 2, 'BRG-000002-002', NULL, NULL, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(35, 2, 'BRG-000002-003', NULL, NULL, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(36, 2, 'BRG-000002-004', NULL, NULL, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(37, 2, 'BRG-000002-005', NULL, NULL, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(38, 2, 'BRG-000002-006', NULL, NULL, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(39, 2, 'BRG-000002-007', NULL, NULL, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(40, 2, 'BRG-000002-008', NULL, NULL, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(41, 2, 'BRG-000002-009', NULL, NULL, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(42, 2, 'BRG-000002-010', NULL, NULL, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(43, 2, 'BRG-000002-011', NULL, NULL, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(44, 2, 'BRG-000002-012', NULL, NULL, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-11 02:35:30'),
(45, 2, 'BRG-000002-013', NULL, NULL, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-12 08:46:20'),
(46, 2, 'BRG-000002-014', NULL, NULL, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-12 08:46:20'),
(47, 2, 'BRG-000002-015', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 08:46:20'),
(48, 2, 'BRG-000002-016', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 08:46:20'),
(49, 2, 'BRG-000002-017', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 08:46:20'),
(50, 2, 'BRG-000002-018', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 08:46:20'),
(51, 2, 'BRG-000002-019', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 08:46:20'),
(52, 2, 'BRG-000002-020', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 08:46:20'),
(53, 2, 'BRG-000002-021', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 08:46:20'),
(54, 2, 'BRG-000002-022', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 08:46:20'),
(55, 2, 'BRG-000002-023', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 08:46:20'),
(56, 2, 'BRG-000002-024', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 08:46:20'),
(57, 3, 'BRG-000003-001', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:02:00'),
(58, 3, 'BRG-000003-002', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:12:44'),
(59, 1, 'BRG-000001-033', 'CCTV 033', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:16:54'),
(60, 1, 'BRG-000001-034', 'CCTV 034', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:16:54'),
(61, 1, 'BRG-000001-035', 'CCTV 035', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:16:54'),
(62, 4, 'BRG-000004-001', NULL, NULL, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-12 09:24:07'),
(63, 4, 'BRG-000004-002', NULL, NULL, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-12 09:24:07'),
(64, 4, 'BRG-000004-003', NULL, NULL, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-12 09:24:07'),
(65, 4, 'BRG-000004-004', NULL, NULL, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-12 09:26:16'),
(66, 4, 'BRG-000004-005', NULL, NULL, NULL, 'Baik', NULL, NULL, 'keluar', '2026-09-12 09:26:16'),
(67, 4, 'BRG-000004-006', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:26:16'),
(68, 5, 'BRG-000005-001', 'kacamata pak itah', 1, NULL, 'Baik', 'saya pinjem bentar', NULL, 'terpakai', '2026-09-12 09:45:05'),
(69, 5, 'BRG-000005-002', 'kacamata 002', 1, NULL, 'Rusak Ringan', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(70, 5, 'BRG-000005-003', 'kacamata 003', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(71, 5, 'BRG-000005-004', 'kacamata 004', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(72, 5, 'BRG-000005-005', 'kacamata 005', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(73, 5, 'BRG-000005-006', 'kacamata 006', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(74, 5, 'BRG-000005-007', 'kacamata 007', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(75, 5, 'BRG-000005-008', 'kacamata 008', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(76, 5, 'BRG-000005-009', 'kacamata 009', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(77, 5, 'BRG-000005-010', 'kacamata 010', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(78, 5, 'BRG-000005-011', 'kacamata 011', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(79, 5, 'BRG-000005-012', 'kacamata 012', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(80, 5, 'BRG-000005-013', 'kacamata 013', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(81, 5, 'BRG-000005-014', 'kacamata 014', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(82, 5, 'BRG-000005-015', 'kacamata 015', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(83, 5, 'BRG-000005-016', 'kacamata 016', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(84, 5, 'BRG-000005-017', 'kacamata 017', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(85, 5, 'BRG-000005-018', 'kacamata 018', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(86, 5, 'BRG-000005-019', 'kacamata 019', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(87, 5, 'BRG-000005-020', 'kacamata 020', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(88, 5, 'BRG-000005-021', 'kacamata 021', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(89, 5, 'BRG-000005-022', 'kacamata 022', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(90, 5, 'BRG-000005-023', 'kacamata 023', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(91, 5, 'BRG-000005-024', 'kacamata 024', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(92, 5, 'BRG-000005-025', 'kacamata 025', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(93, 5, 'BRG-000005-026', 'kacamata 026', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(94, 5, 'BRG-000005-027', 'kacamata 027', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(95, 5, 'BRG-000005-028', 'kacamata 028', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(96, 5, 'BRG-000005-029', 'kacamata 029', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(97, 5, 'BRG-000005-030', 'kacamata 030', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(98, 5, 'BRG-000005-031', 'kacamata 031', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(99, 5, 'BRG-000005-032', 'kacamata 032', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(100, 5, 'BRG-000005-033', 'kacamata 033', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(101, 5, 'BRG-000005-034', 'kacamata 034', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(102, 5, 'BRG-000005-035', 'kacamata 035', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(103, 5, 'BRG-000005-036', 'kacamata 036', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(104, 5, 'BRG-000005-037', 'kacamata 037', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(105, 5, 'BRG-000005-038', 'kacamata 038', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(106, 5, 'BRG-000005-039', 'kacamata 039', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(107, 5, 'BRG-000005-040', 'kacamata 040', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(108, 5, 'BRG-000005-041', 'kacamata 041', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(109, 5, 'BRG-000005-042', 'kacamata 042', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(110, 5, 'BRG-000005-043', 'kacamata 043', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(111, 5, 'BRG-000005-044', 'kacamata 044', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(112, 5, 'BRG-000005-045', 'kacamata 045', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(113, 5, 'BRG-000005-046', 'kacamata 046', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(114, 5, 'BRG-000005-047', 'kacamata 047', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(115, 5, 'BRG-000005-048', 'kacamata 048', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(116, 5, 'BRG-000005-049', 'kacamata 049', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(117, 5, 'BRG-000005-050', 'kacamata 050', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(118, 5, 'BRG-000005-051', 'kacamata 051', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(119, 5, 'BRG-000005-052', 'kacamata 052', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(120, 5, 'BRG-000005-053', 'kacamata 053', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(121, 5, 'BRG-000005-054', 'kacamata 054', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(122, 5, 'BRG-000005-055', 'kacamata 055', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(123, 5, 'BRG-000005-056', 'kacamata 056', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(124, 5, 'BRG-000005-057', 'kacamata 057', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(125, 5, 'BRG-000005-058', 'kacamata 058', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(126, 5, 'BRG-000005-059', 'kacamata 059', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(127, 5, 'BRG-000005-060', 'kacamata 060', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(128, 5, 'BRG-000005-061', 'kacamata 061', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(129, 5, 'BRG-000005-062', 'kacamata 062', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(130, 5, 'BRG-000005-063', 'kacamata 063', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(131, 5, 'BRG-000005-064', 'kacamata 064', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(132, 5, 'BRG-000005-065', 'kacamata 065', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(133, 5, 'BRG-000005-066', 'kacamata 066', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(134, 5, 'BRG-000005-067', 'kacamata 067', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(135, 5, 'BRG-000005-068', 'kacamata 068', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(136, 5, 'BRG-000005-069', 'kacamata 069', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:05'),
(137, 5, 'BRG-000005-070', 'kacamata 070', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(138, 5, 'BRG-000005-071', 'kacamata 071', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(139, 5, 'BRG-000005-072', 'kacamata 072', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(140, 5, 'BRG-000005-073', 'kacamata 073', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(141, 5, 'BRG-000005-074', 'kacamata 074', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(142, 5, 'BRG-000005-075', 'kacamata 075', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(143, 5, 'BRG-000005-076', 'kacamata 076', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(144, 5, 'BRG-000005-077', 'kacamata 077', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(145, 5, 'BRG-000005-078', 'kacamata 078', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(146, 5, 'BRG-000005-079', 'kacamata 079', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(147, 5, 'BRG-000005-080', 'kacamata 080', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(148, 5, 'BRG-000005-081', 'kacamata 081', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(149, 5, 'BRG-000005-082', 'kacamata 082', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(150, 5, 'BRG-000005-083', 'kacamata 083', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(151, 5, 'BRG-000005-084', 'kacamata 084', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(152, 5, 'BRG-000005-085', 'kacamata 085', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(153, 5, 'BRG-000005-086', 'kacamata 086', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(154, 5, 'BRG-000005-087', 'kacamata 087', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(155, 5, 'BRG-000005-088', 'kacamata 088', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(156, 5, 'BRG-000005-089', 'kacamata 089', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(157, 5, 'BRG-000005-090', 'kacamata 090', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(158, 5, 'BRG-000005-091', 'kacamata 091', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(159, 5, 'BRG-000005-092', 'kacamata 092', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(160, 5, 'BRG-000005-093', 'kacamata 093', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(161, 5, 'BRG-000005-094', 'kacamata 094', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(162, 5, 'BRG-000005-095', 'kacamata 095', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(163, 5, 'BRG-000005-096', 'kacamata 096', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(164, 5, 'BRG-000005-097', 'kacamata 097', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(165, 5, 'BRG-000005-098', 'kacamata 098', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(166, 5, 'BRG-000005-099', 'kacamata 099', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(167, 5, 'BRG-000005-100', 'kacamata 100', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:45:06'),
(168, 5, 'BRG-000005-101', 'kacamata 101', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:48:44'),
(169, 5, 'BRG-000005-102', 'kacamata 102', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:48:44'),
(170, 5, 'BRG-000005-103', 'kacamata 103', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:48:44'),
(171, 5, 'BRG-000005-104', 'kacamata 104', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:48:44'),
(172, 5, 'BRG-000005-105', 'kacamata 105', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:48:44'),
(173, 5, 'BRG-000005-106', 'kacamata 106', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:48:44'),
(174, 5, 'BRG-000005-107', 'kacamata 107', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:48:44'),
(175, 5, 'BRG-000005-108', 'kacamata 108', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:48:44'),
(176, 5, 'BRG-000005-109', 'kacamata 109', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:48:44'),
(177, 5, 'BRG-000005-110', 'kacamata 110', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:48:44'),
(178, 5, 'BRG-000005-111', 'kacamata 111', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:48:44'),
(179, 5, 'BRG-000005-112', 'kacamata 112', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:48:44'),
(180, 5, 'BRG-000005-113', 'kacamata 113', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:48:44'),
(181, 5, 'BRG-000005-114', 'kacamata 114', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:48:44'),
(182, 4, 'BRG-000004-007', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:51:11'),
(183, 4, 'BRG-000004-008', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:51:11'),
(184, 4, 'BRG-000004-009', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:51:11'),
(185, 4, 'BRG-000004-010', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:51:11'),
(186, 4, 'BRG-000004-011', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:51:11'),
(187, 4, 'BRG-000004-012', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:51:11'),
(188, 4, 'BRG-000004-013', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:51:11'),
(189, 4, 'BRG-000004-014', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:51:11'),
(190, 4, 'BRG-000004-015', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:51:11'),
(191, 2, 'BRG-000002-025', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:56:31'),
(192, 2, 'BRG-000002-026', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:56:31'),
(193, 2, 'BRG-000002-027', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:56:31'),
(194, 2, 'BRG-000002-028', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:56:31'),
(195, 2, 'BRG-000002-029', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:56:31'),
(196, 2, 'BRG-000002-030', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:56:31'),
(197, 2, 'BRG-000002-031', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:56:31'),
(198, 2, 'BRG-000002-032', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:56:31'),
(199, 2, 'BRG-000002-033', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:56:31'),
(200, 2, 'BRG-000002-034', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:56:31'),
(201, 2, 'BRG-000002-035', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:56:31'),
(202, 2, 'BRG-000002-036', NULL, NULL, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-12 09:56:31'),
(203, 7, 'BRG-000007-001', 'TINTA 001', 3, NULL, 'Baik', NULL, '2026-09-14', 'keluar', '2026-09-12 18:20:29'),
(204, 7, 'BRG-000007-002', 'TINTA 002', 3, NULL, 'Baik', NULL, '2026-09-14', 'keluar', '2026-09-12 18:20:29'),
(205, 7, 'BRG-000007-003', 'TINTA 003', 3, NULL, 'Baik', NULL, '2026-09-14', 'keluar', '2026-09-12 18:20:29'),
(206, 7, 'BRG-000007-004', 'TINTA 004', 3, NULL, 'Baik', NULL, '2026-09-14', 'keluar', '2026-09-12 18:20:29'),
(207, 7, 'BRG-000007-005', 'TINTA 005', 3, NULL, 'Baik', NULL, '2026-09-14', 'tersedia', '2026-09-12 18:20:29'),
(208, 7, 'BRG-000007-006', 'TINTA 006', 3, NULL, 'Baik', NULL, '2026-09-14', 'tersedia', '2026-09-12 18:20:29'),
(209, 7, 'BRG-000007-007', 'TINTA 007', 3, NULL, 'Baik', NULL, '2026-09-14', 'tersedia', '2026-09-12 18:20:29'),
(210, 7, 'BRG-000007-008', 'TINTA 008', 3, NULL, 'Baik', NULL, '2026-09-14', 'tersedia', '2026-09-12 18:20:29'),
(211, 7, 'BRG-000007-009', 'TINTA 009', 3, NULL, 'Baik', NULL, '2026-09-14', 'tersedia', '2026-09-12 18:20:29'),
(212, 7, 'BRG-000007-010', 'TINTA 010', 3, NULL, 'Baik', NULL, '2026-09-14', 'tersedia', '2026-09-12 18:20:29'),
(213, 7, 'BRG-000007-011', 'TINTA 011', 3, NULL, 'Baik', NULL, '2026-09-14', 'tersedia', '2026-09-12 18:20:29'),
(214, 7, 'BRG-000007-012', 'TINTA 012', 3, NULL, 'Baik', NULL, '2026-09-14', 'tersedia', '2026-09-12 18:20:29'),
(215, 7, 'BRG-000007-013', 'TINTA 013', 3, NULL, 'Baik', NULL, '2026-09-14', 'tersedia', '2026-09-12 18:20:29'),
(216, 7, 'BRG-000007-014', 'TINTA 014', 3, NULL, 'Baik', NULL, '2026-09-14', 'tersedia', '2026-09-12 18:20:29'),
(217, 7, 'BRG-000007-015', 'TINTA 015', 3, NULL, 'Baik', NULL, '2026-09-14', 'tersedia', '2026-09-12 18:20:29'),
(218, 7, 'BRG-000007-016', 'TINTA 016', 3, NULL, 'Baik', NULL, '2026-09-14', 'tersedia', '2026-09-12 18:20:29'),
(219, 7, 'BRG-000007-017', 'TINTA 017', 3, NULL, 'Baik', NULL, '2026-09-14', 'tersedia', '2026-09-12 18:20:29'),
(220, 7, 'BRG-000007-018', 'TINTA 018', 3, NULL, 'Baik', NULL, '2026-09-14', 'tersedia', '2026-09-12 18:20:29'),
(221, 7, 'BRG-000007-019', 'TINTA 019', 3, NULL, 'Baik', NULL, '2026-09-14', 'tersedia', '2026-09-12 18:20:29'),
(226, 8, 'BRG-000008-001', 'LISENSI ANTI FIRUS 1', NULL, NULL, 'Baik', NULL, '2026-12-16', 'tersedia', '2026-09-16 01:22:53'),
(227, 8, 'BRG-000008-002', 'LISENSI ANTI FIRUS 2', NULL, NULL, 'Baik', NULL, '2026-12-16', 'tersedia', '2026-09-16 01:22:53'),
(228, 9, 'BRG-000009-001', 'ACCESS POINT 1', 5, NULL, 'Rusak Ringan', NULL, NULL, 'rusak', '2026-09-16 01:30:01'),
(229, 9, 'BRG-000009-002', 'ACCESS POINT 2', 5, NULL, 'Baik', NULL, NULL, 'terpakai', '2026-09-16 01:30:01'),
(230, 9, 'BRG-000009-003', 'ACCESS POINT 3', 5, NULL, 'Baik', 'dipakai ke jeti', NULL, 'terpakai', '2026-09-16 01:30:01'),
(231, 9, 'BRG-000009-004', 'ACCESS POINT 4', 5, NULL, 'Baik', 'terpakai di ccr', NULL, 'terpakai', '2026-09-16 01:30:01'),
(232, 9, 'BRG-000009-005', 'ACCESS POINT 5', 5, NULL, 'Baik', NULL, '2026-09-17', 'rusak', '2026-09-16 01:30:01'),
(233, 9, 'BRG-000009-006', 'ACCESS POINT 6', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 01:30:01'),
(234, 9, 'BRG-000009-007', 'ACCESS POINT 7', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 01:30:01'),
(235, 9, 'BRG-000009-008', 'ACCESS POINT 8', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 01:30:01'),
(236, 9, 'BRG-000009-009', 'ACCESS POINT 9', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 01:30:01'),
(237, 9, 'BRG-000009-010', 'ACCESS POINT 10', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 01:30:01'),
(238, 9, 'BRG-000009-011', 'ACCESS POINT 11', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 01:30:01'),
(239, 9, 'BRG-000009-012', 'ACCESS POINT 12', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 01:30:01'),
(240, 9, 'BRG-000009-013', 'ACCESS POINT 13', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 01:30:01'),
(241, 9, 'BRG-000009-014', 'ACCESS POINT 14', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 01:30:01'),
(242, 9, 'BRG-000009-015', 'ACCESS POINT 15', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 01:30:01'),
(243, 9, 'BRG-000009-016', 'ACCESS POINT 16', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 01:30:01'),
(244, 9, 'BRG-000009-017', 'ACCESS POINT 17', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 01:30:01'),
(245, 9, 'BRG-000009-018', 'ACCESS POINT 18', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 01:30:01'),
(246, 9, 'BRG-000009-019', 'ACCESS POINT 19', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 01:30:01'),
(247, 9, 'BRG-000009-020', 'ACCESS POINT 20', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 01:30:01'),
(248, 1, 'BRG-000001-036', 'CCTV 36', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:53:01'),
(249, 1, 'BRG-000001-037', 'CCTV 37', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:53:01'),
(250, 1, 'BRG-000001-038', 'CCTV 38', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:53:01'),
(251, 1, 'BRG-000001-039', 'CCTV 39', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:53:01'),
(252, 1, 'BRG-000001-040', 'CCTV 40', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:53:01'),
(253, 1, 'BRG-000001-041', 'CCTV 41', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:53:01'),
(254, 1, 'BRG-000001-042', 'CCTV 42', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:53:01'),
(255, 1, 'BRG-000001-043', 'CCTV 43', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:53:01'),
(256, 1, 'BRG-000001-044', 'CCTV 44', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:53:01'),
(257, 1, 'BRG-000001-045', 'CCTV 45', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:53:01'),
(258, 1, 'BRG-000001-046', 'CCTV 46', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:53:01'),
(259, 1, 'BRG-000001-047', 'CCTV 47', 1, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:53:01'),
(260, 9, 'BRG-000009-021', 'ACCESS POINT 21', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:54:17'),
(261, 9, 'BRG-000009-022', 'ACCESS POINT 22', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:54:17'),
(262, 9, 'BRG-000009-023', 'ACCESS POINT 23', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:54:17'),
(263, 9, 'BRG-000009-024', 'ACCESS POINT 24', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:54:17'),
(264, 9, 'BRG-000009-025', 'ACCESS POINT 25', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:54:17'),
(265, 9, 'BRG-000009-026', 'ACCESS POINT 26', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:54:17'),
(266, 9, 'BRG-000009-027', 'ACCESS POINT 27', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:54:17'),
(267, 9, 'BRG-000009-028', 'ACCESS POINT 28', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:54:17'),
(268, 9, 'BRG-000009-029', 'ACCESS POINT 29', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:54:17'),
(269, 9, 'BRG-000009-030', 'ACCESS POINT 30', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:54:17'),
(270, 9, 'BRG-000009-031', 'ACCESS POINT 31', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:54:17'),
(271, 9, 'BRG-000009-032', 'ACCESS POINT 32', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 19:54:17'),
(272, 10, 'BRG-000010-001', 'tes 1', 3, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 20:28:43'),
(273, 10, 'BRG-000010-002', 'tes 2', 3, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 20:28:43'),
(274, 10, 'BRG-000010-003', 'tes 3', 3, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 20:28:43'),
(275, 10, 'BRG-000010-004', 'tes 4', 3, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 20:28:43'),
(276, 10, 'BRG-000010-005', 'tes 5', 3, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 20:28:43'),
(277, 10, 'BRG-000010-006', 'tes 6', 3, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 20:28:43'),
(278, 10, 'BRG-000010-007', 'tes 7', 3, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 20:28:43'),
(279, 10, 'BRG-000010-008', 'tes 8', 3, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 20:28:43'),
(280, 10, 'BRG-000010-009', 'tes 9', 3, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 20:28:43'),
(281, 10, 'BRG-000010-010', 'tes 10', 3, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 20:28:43'),
(282, 10, 'BRG-000010-011', 'tes 11', 3, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 20:28:43'),
(283, 10, 'BRG-000010-012', 'tes 12', 3, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-16 20:28:43'),
(284, 9, 'BRG-000009-033', 'ACCESS POINT 33', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-17 07:55:46'),
(285, 9, 'BRG-000009-034', 'ACCESS POINT 34', 5, NULL, 'Baik', NULL, NULL, 'tersedia', '2026-09-17 07:55:46');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama`, `keterangan`) VALUES
(1, 'BARANG IT', ''),
(2, 'BARANG CCR', ''),
(3, 'BARANG HABIS PAKAI', ''),
(4, 'LISENSI', ''),
(5, 'BARANG ELEKTRO', '');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `failed_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `first_failed_at` datetime DEFAULT NULL,
  `locked_until` datetime DEFAULT NULL,
  `last_attempt_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `failed_attempts`, `first_failed_at`, `locked_until`, `last_attempt_at`) VALUES
(4, 'sdmin2', '::1', 1, '2026-09-13 01:38:13', NULL, '2026-09-13 01:38:13'),
(9, 'dffa', '::1', 1, '2026-09-17 01:34:59', NULL, '2026-09-17 01:34:59');

-- --------------------------------------------------------

--
-- Table structure for table `lokasi`
--

CREATE TABLE `lokasi` (
  `id` int(11) NOT NULL,
  `gedung` varchar(100) NOT NULL,
  `ruang` varchar(100) DEFAULT NULL,
  `rak` varchar(100) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lokasi`
--

INSERT INTO `lokasi` (`id`, `gedung`, `ruang`, `rak`, `keterangan`) VALUES
(1, 'CCR', '1', '12', ''),
(2, 'ELEKTRIK', '2', '12', 'dibagian atas'),
(3, 'ADMIN', 'RUANG B', 'RAK B-04', 'DEKET PINTU'),
(4, 'GUDANG CCR', 'RUANGAN B', 'RAK C-06', 'DIBAGIAN PALING SAMPING'),
(5, 'STORAGE IT', 'RUANGAN SAMPIN KANTOR IT', 'RAK C-06', '');

-- --------------------------------------------------------

--
-- Table structure for table `room_visits`
--

CREATE TABLE `room_visits` (
  `id` int(11) NOT NULL,
  `nama` varchar(120) NOT NULL,
  `identitas` varchar(120) DEFAULT NULL,
  `ruangan` varchar(150) NOT NULL,
  `keperluan` varchar(255) DEFAULT NULL,
  `kode_scan` varchar(100) NOT NULL,
  `barang_id` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jam` time NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room_visits`
--

INSERT INTO `room_visits` (`id`, `nama`, `identitas`, `ruangan`, `keperluan`, `kode_scan`, `barang_id`, `unit_id`, `tanggal`, `jam`, `user_id`, `created_at`) VALUES
(1, 'dfa', 'daffa', 'ADMIN / RUANG B / RAK B-04', 'Pengambilan barang', 'ROOM-3', NULL, NULL, '2026-09-17', '01:40:25', 3, '2026-09-16 18:40:25'),
(2, 'dfa', 'daffa', 'STORAGE IT / RUANGAN SAMPIN KANTOR IT / RAK C-06', 'Pengambilan barang', 'ROOM-5', NULL, NULL, '2026-09-17', '02:10:08', 3, '2026-09-16 19:10:08'),
(3, 'Administrator', 'admin', 'STORAGE IT / RUANGAN SAMPIN KANTOR IT / RAK C-06', 'Pengambilan barang', 'ROOM-5', NULL, NULL, '2026-09-17', '02:12:37', 1, '2026-09-16 19:12:37'),
(4, 'dfa', 'daffa', 'CCR / 1 / 12', 'Pengambilan barang', 'ROOM-1', NULL, NULL, '2026-09-17', '02:12:43', 3, '2026-09-16 19:12:43'),
(5, 'Ilham', 'Ilham', 'STORAGE IT / RUANGAN SAMPIN KANTOR IT / RAK C-06', 'Pengambilan barang', 'ROOM-5', NULL, NULL, '2026-09-17', '14:51:46', 2, '2026-09-17 07:51:46');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `jenis` enum('masuk','keluar') NOT NULL,
  `jumlah` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id`, `barang_id`, `jenis`, `jumlah`, `tanggal`, `keterangan`, `user_id`, `created_at`) VALUES
(1, 2, 'masuk', 12, '2026-09-12', '', 2, '2026-09-12 08:46:20'),
(2, 4, 'keluar', 2, '2026-09-12', 'dipinjem pak kabin', 3, '2026-09-12 09:30:21'),
(3, 4, 'keluar', 2, '2026-09-12', 'dipasang di di jetty', 1, '2026-09-12 09:32:20'),
(4, 4, 'keluar', 1, '2026-09-12', 'tak pake dirumah', 3, '2026-09-12 09:32:24'),
(5, 1, 'keluar', 15, '2026-09-12', 'ada keperluan dari atasan', 3, '2026-09-12 09:36:47'),
(6, 5, 'masuk', 14, '2026-09-12', 'barang masuk lagi', 3, '2026-09-12 09:48:44'),
(7, 4, 'masuk', 12, '2026-09-11', 'lupa masukin barang kemarin', 3, '2026-09-12 09:51:11'),
(8, 2, 'masuk', 12, '2026-09-11', 'barang masuk hari selasa', 3, '2026-09-12 09:56:31'),
(9, 2, 'keluar', 2, '2026-09-13', 'barang dipake elektrik', 3, '2026-09-12 17:30:15'),
(10, 2, 'keluar', 12, '2026-09-14', '', 4, '2026-09-15 17:06:54'),
(11, 7, 'keluar', 4, '2026-09-16', '', 4, '2026-09-15 17:15:35'),
(12, 1, 'masuk', 12, '2026-09-17', '', 4, '2026-09-16 19:53:01'),
(13, 9, 'masuk', 12, '2026-09-17', '', 4, '2026-09-16 19:54:17'),
(14, 1, 'keluar', 5, '2026-09-17', '', 4, '2026-09-16 19:56:17'),
(15, 9, 'masuk', 2, '2026-09-15', 'log tanggal 15', 4, '2026-09-17 07:55:46');

-- --------------------------------------------------------

--
-- Table structure for table `unit_status_logs`
--

CREATE TABLE `unit_status_logs` (
  `id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `old_status` varchar(20) NOT NULL,
  `new_status` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `unit_status_logs`
--

INSERT INTO `unit_status_logs` (`id`, `unit_id`, `barang_id`, `old_status`, `new_status`, `user_id`, `keterangan`, `created_at`) VALUES
(1, 230, 9, 'tersedia', 'terpakai', 3, 'Perubahan status dari detail QR', '2026-09-16 16:56:37'),
(2, 231, 9, 'tersedia', 'terpakai', 3, 'Perubahan status dari detail QR', '2026-09-16 18:42:54'),
(3, 68, 5, 'tersedia', 'terpakai', 3, 'Perubahan status dari detail QR', '2026-09-16 20:53:06'),
(4, 232, 9, 'tersedia', 'rusak', 2, 'Perubahan status dari detail QR', '2026-09-17 07:50:32');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telepon` varchar(30) DEFAULT NULL,
  `bio` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `username`, `email`, `telepon`, `bio`, `password`, `role`, `aktif`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'admin', NULL, NULL, NULL, '$2y$10$mNeE./ZZHsENByOsw1sna.oJEKu2Gde9yyxuHLTb4j3x/ihvpNZtK', 'admin', 1, '2026-09-10 12:12:08', '2026-09-15 16:34:25'),
(2, 'Ilham', 'Ilham', NULL, NULL, NULL, '$2y$10$Fa2MeN2YUyoXJzRaTcijzef17BnhyG6niul7/Ygo4IzRSkj93mMka', 'user', 1, '2026-09-12 08:22:52', '2026-09-17 01:38:28'),
(3, 'dfa', 'daffa', 'fa@gmail.com', '0837393738', NULL, '$2y$10$KQQqDNSFWFDYpyCPNcuxS.k1AXP4mu4Y98Z0XlsIsI47K67qGGK2K', 'user', 1, '2026-09-12 08:52:06', '2026-09-17 07:00:16'),
(4, 'admin2', 'admin2', NULL, NULL, NULL, '$2y$10$84UFqYE/HK4qwqfPenqd7ul4zP8moCaTwekHmB5/bnu49LEwaZu3e', 'admin', 1, '2026-09-12 18:22:58', '2026-09-15 16:32:50'),
(5, 'affad', 'affad', 'affad@gmail.com', '08748447287', NULL, '$2y$10$X4N1DHyVuH.xjDUwl43g.OCRazkD7Lbd5W1hxFL/QWicTTYs/QryO', 'user', 1, '2026-09-17 06:58:31', '2026-09-17 06:58:31'),
(6, 'fikri', '12345', 'riyanfikri607@gmail.com', '082420674972', 'test', '$2y$10$S1BUKW7ZvX.cmusXJ0jbSeeHZiy270u0PDFaXimdNnZ2Y0xe6T/Gm', 'user', 1, '2026-09-17 08:00:48', '2026-09-17 08:00:48');

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `user_id` int(11) NOT NULL,
  `permission` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_permissions`
--

INSERT INTO `user_permissions` (`user_id`, `permission`) VALUES
(2, 'barang_manage'),
(2, 'barang_view'),
(2, 'dashboard_view'),
(2, 'scan_view'),
(2, 'units_view'),
(3, 'barang_manage'),
(3, 'barang_view'),
(3, 'dashboard_view'),
(3, 'scan_view'),
(3, 'units_view'),
(5, 'barang_view'),
(5, 'dashboard_view'),
(5, 'scan_view'),
(5, 'units_view'),
(6, 'barang_view'),
(6, 'dashboard_view'),
(6, 'scan_view'),
(6, 'units_view');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`),
  ADD KEY `fk_barang_kategori` (`kategori_id`),
  ADD KEY `fk_barang_lokasi` (`lokasi_id`);

--
-- Indexes for table `barang_unit`
--
ALTER TABLE `barang_unit`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_unit` (`kode_unit`),
  ADD KEY `fk_unit_barang` (`barang_id`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama` (`nama`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login_attempt_identity` (`username`,`ip_address`),
  ADD KEY `login_attempt_lock` (`locked_until`);

--
-- Indexes for table `lokasi`
--
ALTER TABLE `lokasi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room_visits`
--
ALTER TABLE `room_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_visits_created` (`created_at`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_trans_barang` (`barang_id`),
  ADD KEY `fk_trans_user` (`user_id`);

--
-- Indexes for table `unit_status_logs`
--
ALTER TABLE `unit_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unit_status_logs_created` (`created_at`),
  ADD KEY `fk_unit_status_log_unit` (`unit_id`),
  ADD KEY `fk_unit_status_log_barang` (`barang_id`),
  ADD KEY `fk_unit_status_log_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`user_id`,`permission`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `barang_unit`
--
ALTER TABLE `barang_unit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=286;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `lokasi`
--
ALTER TABLE `lokasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `room_visits`
--
ALTER TABLE `room_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `unit_status_logs`
--
ALTER TABLE `unit_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang`
--
ALTER TABLE `barang`
  ADD CONSTRAINT `fk_barang_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_barang_lokasi` FOREIGN KEY (`lokasi_id`) REFERENCES `lokasi` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `barang_unit`
--
ALTER TABLE `barang_unit`
  ADD CONSTRAINT `fk_unit_barang` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `fk_trans_barang` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`),
  ADD CONSTRAINT `fk_trans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `unit_status_logs`
--
ALTER TABLE `unit_status_logs`
  ADD CONSTRAINT `fk_unit_status_log_barang` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_unit_status_log_unit` FOREIGN KEY (`unit_id`) REFERENCES `barang_unit` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_unit_status_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `fk_permission_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
