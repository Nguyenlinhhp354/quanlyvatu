-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: sql313.ezyro.com
-- Thời gian đã tạo: Th4 17, 2026 lúc 04:34 AM
-- Phiên bản máy phục vụ: 11.4.10-MariaDB
-- Phiên bản PHP: 7.2.22

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `ezyro_41401130_quan_ly_vat_tu_thinhtien`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bao_cao_su_co`
--

DROP TABLE IF EXISTS `bao_cao_su_co`;
CREATE TABLE IF NOT EXISTS `bao_cao_su_co` (
  `id_bao_cao` int(11) NOT NULL AUTO_INCREMENT,
  `id_vat_tu` int(11) DEFAULT NULL,
  `id_kho` int(11) DEFAULT NULL,
  `so_luong_hao_hut` int(11) NOT NULL,
  `nguyen_nhan` text DEFAULT NULL,
  `ngay_lap` datetime DEFAULT current_timestamp(),
  `hinh_anh` varchar(255) DEFAULT NULL,
  `trang_thai_duyet` varchar(50) DEFAULT 'Chờ duyệt',
  `id_nguoi_lap` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_bao_cao`),
  KEY `idx_bc_vattu` (`id_vat_tu`),
  KEY `idx_bc_kho` (`id_kho`),
  KEY `idx_bc_nguoi_lap` (`id_nguoi_lap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `bao_cao_su_co`
--

INSERT INTO `bao_cao_su_co` (`id_bao_cao`, `id_vat_tu`, `id_kho`, `so_luong_hao_hut`, `nguyen_nhan`, `ngay_lap`, `hinh_anh`, `trang_thai_duyet`, `id_nguoi_lap`) VALUES
(1, 2, 2, 3, 'không biết', '2026-04-16 23:55:20', '1.png', 'Chờ duyệt', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_dieu_chuyen`
--

DROP TABLE IF EXISTS `chi_tiet_dieu_chuyen`;
CREATE TABLE IF NOT EXISTS `chi_tiet_dieu_chuyen` (
  `id_phieu_chuyen` int(11) NOT NULL,
  `id_vat_tu` int(11) NOT NULL,
  `so_luong_chuyen` int(11) NOT NULL,
  PRIMARY KEY (`id_phieu_chuyen`,`id_vat_tu`),
  KEY `idx_ctdc_vattu` (`id_vat_tu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_dieu_chuyen`
--

INSERT INTO `chi_tiet_dieu_chuyen` (`id_phieu_chuyen`, `id_vat_tu`, `so_luong_chuyen`) VALUES
(1, 1, 100);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_kiem_ke`
--

DROP TABLE IF EXISTS `chi_tiet_kiem_ke`;
CREATE TABLE IF NOT EXISTS `chi_tiet_kiem_ke` (
  `id_phieu_kk` int(11) NOT NULL,
  `id_vat_tu` int(11) NOT NULL,
  `ton_he_thong` int(11) NOT NULL,
  PRIMARY KEY (`id_phieu_kk`,`id_vat_tu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_kiem_ke`
--

INSERT INTO `chi_tiet_kiem_ke` (`id_phieu_kk`, `id_vat_tu`, `ton_he_thong`) VALUES
(1, 1, 300),
(1, 2, 0),
(1, 3, 200);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_nhap_kho`
--

DROP TABLE IF EXISTS `chi_tiet_nhap_kho`;
CREATE TABLE IF NOT EXISTS `chi_tiet_nhap_kho` (
  `id_phieu_nhap` int(11) NOT NULL,
  `id_vat_tu` int(11) NOT NULL,
  `so_luong` int(11) NOT NULL,
  `don_gia` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`id_phieu_nhap`,`id_vat_tu`),
  KEY `idx_ctnk_vattu` (`id_vat_tu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_nhap_kho`
--

INSERT INTO `chi_tiet_nhap_kho` (`id_phieu_nhap`, `id_vat_tu`, `so_luong`, `don_gia`) VALUES
(4, 2, 2, '123123123.00'),
(4, 4, 1, '222222.00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_xuat_kho`
--

DROP TABLE IF EXISTS `chi_tiet_xuat_kho`;
CREATE TABLE IF NOT EXISTS `chi_tiet_xuat_kho` (
  `id_phieu_xuat` int(11) NOT NULL,
  `id_vat_tu` int(11) NOT NULL,
  `so_luong` int(11) NOT NULL,
  PRIMARY KEY (`id_phieu_xuat`,`id_vat_tu`),
  KEY `idx_ctxk_vattu` (`id_vat_tu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_xuat_kho`
--

INSERT INTO `chi_tiet_xuat_kho` (`id_phieu_xuat`, `id_vat_tu`, `so_luong`) VALUES
(1, 1, 200);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_yeu_cau`
--

DROP TABLE IF EXISTS `chi_tiet_yeu_cau`;
CREATE TABLE IF NOT EXISTS `chi_tiet_yeu_cau` (
  `id_phieu_yc` int(11) NOT NULL,
  `id_vat_tu` int(11) NOT NULL,
  `so_luong_yeu_cau` int(11) NOT NULL,
  PRIMARY KEY (`id_phieu_yc`,`id_vat_tu`),
  KEY `idx_ctyc_vattu` (`id_vat_tu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `don_vi_tinh`
--

DROP TABLE IF EXISTS `don_vi_tinh`;
CREATE TABLE IF NOT EXISTS `don_vi_tinh` (
  `id_dvt` int(11) NOT NULL AUTO_INCREMENT,
  `ten_don_vi_tinh` varchar(50) NOT NULL,
  PRIMARY KEY (`id_dvt`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `don_vi_tinh`
--

INSERT INTO `don_vi_tinh` (`id_dvt`, `ten_don_vi_tinh`) VALUES
(1, 'Cái'),
(2, 'Bộ'),
(3, 'Mét'),
(4, 'Kg'),
(5, 'Khối'),
(6, 'Thùng');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `du_an`
--

DROP TABLE IF EXISTS `du_an`;
CREATE TABLE IF NOT EXISTS `du_an` (
  `id_du_an` int(11) NOT NULL AUTO_INCREMENT,
  `ten_du_an` varchar(255) NOT NULL,
  `dia_diem` text DEFAULT NULL,
  `trang_thai` varchar(50) DEFAULT 'Đang thi công',
  PRIMARY KEY (`id_du_an`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `du_an`
--

INSERT INTO `du_an` (`id_du_an`, `ten_du_an`, `dia_diem`, `trang_thai`) VALUES
(1, 'Chung cư Blue Sky', 'Quận 7, TP.HCM', 'Đang thi công'),
(2, 'Cầu vượt Hòa Cầm', 'Đà Nẵng', 'Đang thi công'),
(3, 'BATA', 'An Dương, Hải Phòng', 'Đang thi công');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hang_san_xuat`
--

DROP TABLE IF EXISTS `hang_san_xuat`;
CREATE TABLE IF NOT EXISTS `hang_san_xuat` (
  `id_hsx` int(11) NOT NULL AUTO_INCREMENT,
  `ten_hang_san_xuat` varchar(100) NOT NULL,
  PRIMARY KEY (`id_hsx`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `hang_san_xuat`
--

INSERT INTO `hang_san_xuat` (`id_hsx`, `ten_hang_san_xuat`) VALUES
(1, 'Hòa Phát'),
(2, 'Duy Tân'),
(3, 'Panasonic'),
(4, 'Rang Đông'),
(5, 'Việt Nhật'),
(6, 'đasas');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `kho`
--

DROP TABLE IF EXISTS `kho`;
CREATE TABLE IF NOT EXISTS `kho` (
  `id_kho` int(11) NOT NULL AUTO_INCREMENT,
  `ten_kho` varchar(100) NOT NULL,
  `dia_chi` text DEFAULT NULL,
  PRIMARY KEY (`id_kho`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `kho`
--

INSERT INTO `kho` (`id_kho`, `ten_kho`, `dia_chi`) VALUES
(1, 'Kho Tổng - Thịnh Tiến', 'KCN Sóng Thần, Bình Dương'),
(2, 'Kho Công trình A', 'Quận 2, TP.HCM'),
(3, 'Kho Công trình B', 'Hòa Vang, Đà Nẵng');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loai_vat_tu`
--

DROP TABLE IF EXISTS `loai_vat_tu`;
CREATE TABLE IF NOT EXISTS `loai_vat_tu` (
  `id_loai_vat_tu` int(11) NOT NULL AUTO_INCREMENT,
  `ten_loai_vat_tu` varchar(100) NOT NULL,
  PRIMARY KEY (`id_loai_vat_tu`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `loai_vat_tu`
--

INSERT INTO `loai_vat_tu` (`id_loai_vat_tu`, `ten_loai_vat_tu`) VALUES
(1, 'Sắt thép'),
(2, 'Xi măng - Gạch - Cát'),
(3, 'Thiết bị điện'),
(4, 'Ống nước & Phụ kiện'),
(5, 'Sơn & Hóa chất xây dựng'),
(6, 'Vật tư phụ (Đinh, que hàn, dây buộc)'),
(7, 'ABC');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoi_dung`
--

DROP TABLE IF EXISTS `nguoi_dung`;
CREATE TABLE IF NOT EXISTS `nguoi_dung` (
  `id_nguoi_dung` int(11) NOT NULL AUTO_INCREMENT,
  `tai_khoan` varchar(50) NOT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `id_vai_tro` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_nguoi_dung`),
  UNIQUE KEY `tai_khoan` (`tai_khoan`),
  KEY `fk_nguoidung_vaitro` (`id_vai_tro`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `nguoi_dung`
--

INSERT INTO `nguoi_dung` (`id_nguoi_dung`, `tai_khoan`, `mat_khau`, `ho_ten`, `id_vai_tro`) VALUES
(1, 'admin', '123456', 'Nguyễn Văn Quản Trị', 1),
(2, 'thukho01', '1234567', 'Trần Thị Kho', 3),
(3, 'ketoan1', '123456', 'Lê Văn Tính', 4),
(4, 'chihuy01', '123456', 'Phạm Thế Thép', 5),
(5, 'nblinh', '123456', 'Nguyễn Bá Linh', 1),
(7, 'ltdat', '123456', 'Lê Tiến Đạt', 1),
(8, 'vuxuancuong', '123456', 'Vũ Xuân Cường', 4);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nha_cung_cap`
--

DROP TABLE IF EXISTS `nha_cung_cap`;
CREATE TABLE IF NOT EXISTS `nha_cung_cap` (
  `id_ncc` int(11) NOT NULL AUTO_INCREMENT,
  `ten_ncc` varchar(255) NOT NULL,
  `dia_chi` text DEFAULT NULL,
  `dien_thoai` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_ncc`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `nha_cung_cap`
--

INSERT INTO `nha_cung_cap` (`id_ncc`, `ten_ncc`, `dia_chi`, `dien_thoai`) VALUES
(1, 'Công ty Thép Việt', 'Số 1 Lý Thường Kiệt, HN', '0901234567'),
(2, 'Điện máy Xanh', '15 Trần Hưng Đạo, TP.HCM', '0988776655'),
(3, 'Daikin Hải Phòng', '15 Lê Hồng Phong', '0943556432');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieu_dieu_chuyen`
--

DROP TABLE IF EXISTS `phieu_dieu_chuyen`;
CREATE TABLE IF NOT EXISTS `phieu_dieu_chuyen` (
  `id_phieu_chuyen` int(11) NOT NULL AUTO_INCREMENT,
  `so_phieu` varchar(50) NOT NULL,
  `ngay_chuyen` datetime DEFAULT current_timestamp(),
  `id_kho_xuat` int(11) NOT NULL,
  `id_kho_nhap` int(11) NOT NULL,
  `id_nguoi_lap` int(11) DEFAULT NULL,
  `ly_do` text DEFAULT NULL,
  `trang_thai` varchar(50) DEFAULT 'Đang chuyển',
  PRIMARY KEY (`id_phieu_chuyen`),
  UNIQUE KEY `so_phieu` (`so_phieu`),
  KEY `fk_pdc_khoxuat` (`id_kho_xuat`),
  KEY `fk_pdc_khonhap` (`id_kho_nhap`),
  KEY `fk_pdc_nguoi` (`id_nguoi_lap`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phieu_dieu_chuyen`
--

INSERT INTO `phieu_dieu_chuyen` (`id_phieu_chuyen`, `so_phieu`, `ngay_chuyen`, `id_kho_xuat`, `id_kho_nhap`, `id_nguoi_lap`, `ly_do`, `trang_thai`) VALUES
(1, 'PDC-2024-001', '2026-04-06 20:57:55', 1, 2, 2, 'Chuyển thép sang công trình A', 'Đang chuyển');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieu_kiem_ke`
--

DROP TABLE IF EXISTS `phieu_kiem_ke`;
CREATE TABLE IF NOT EXISTS `phieu_kiem_ke` (
  `id_phieu_kk` int(11) NOT NULL AUTO_INCREMENT,
  `so_phieu` varchar(50) NOT NULL,
  `ngay_lap` datetime DEFAULT current_timestamp(),
  `ghi_chu` text DEFAULT NULL,
  PRIMARY KEY (`id_phieu_kk`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `phieu_kiem_ke`
--

INSERT INTO `phieu_kiem_ke` (`id_phieu_kk`, `so_phieu`, `ngay_lap`, `ghi_chu`) VALUES
(1, 'PKK-2026-4-16-1', '2026-04-15 20:33:39', 'Định kì tháng 4');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieu_nhap_kho`
--

DROP TABLE IF EXISTS `phieu_nhap_kho`;
CREATE TABLE IF NOT EXISTS `phieu_nhap_kho` (
  `id_phieu_nhap` int(11) NOT NULL AUTO_INCREMENT,
  `so_phieu` varchar(50) NOT NULL,
  `ngay_nhap` datetime DEFAULT current_timestamp(),
  `id_kho` int(11) DEFAULT NULL,
  `id_ncc` int(11) DEFAULT NULL,
  `id_nguoi_lap` int(11) DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL,
  PRIMARY KEY (`id_phieu_nhap`),
  UNIQUE KEY `so_phieu` (`so_phieu`),
  KEY `fk_pnhap_kho` (`id_kho`),
  KEY `fk_pnhap_ncc` (`id_ncc`),
  KEY `fk_pnhap_nguoi` (`id_nguoi_lap`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phieu_nhap_kho`
--

INSERT INTO `phieu_nhap_kho` (`id_phieu_nhap`, `so_phieu`, `ngay_nhap`, `id_kho`, `id_ncc`, `id_nguoi_lap`, `ghi_chu`) VALUES
(4, 'PNK-2026-04-17-1', '2026-04-17 04:21:00', 2, 1, 5, 'adđ');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieu_xuat_kho`
--

DROP TABLE IF EXISTS `phieu_xuat_kho`;
CREATE TABLE IF NOT EXISTS `phieu_xuat_kho` (
  `id_phieu_xuat` int(11) NOT NULL AUTO_INCREMENT,
  `so_phieu` varchar(50) NOT NULL,
  `ngay_xuat` datetime DEFAULT current_timestamp(),
  `id_kho` int(11) DEFAULT NULL,
  `id_du_an` int(11) DEFAULT NULL,
  `id_nguoi_lap` int(11) DEFAULT NULL,
  `nguoi_nhan` varchar(100) DEFAULT NULL,
  `ly_do_xuat` text DEFAULT NULL,
  PRIMARY KEY (`id_phieu_xuat`),
  UNIQUE KEY `so_phieu` (`so_phieu`),
  KEY `fk_pxuat_kho` (`id_kho`),
  KEY `fk_pxuat_duan` (`id_du_an`),
  KEY `fk_pxuat_nguoi` (`id_nguoi_lap`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phieu_xuat_kho`
--

INSERT INTO `phieu_xuat_kho` (`id_phieu_xuat`, `so_phieu`, `ngay_xuat`, `id_kho`, `id_du_an`, `id_nguoi_lap`, `nguoi_nhan`, `ly_do_xuat`) VALUES
(1, 'PXK-2024-001', '2026-04-06 20:57:55', 1, 1, 2, 'Nguyễn Văn Công', 'Xuất thép cho móng chung cư');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieu_yeu_cau`
--

DROP TABLE IF EXISTS `phieu_yeu_cau`;
CREATE TABLE IF NOT EXISTS `phieu_yeu_cau` (
  `id_phieu_yc` int(11) NOT NULL AUTO_INCREMENT,
  `so_phieu` varchar(50) NOT NULL,
  `ngay_lap` datetime DEFAULT current_timestamp(),
  `id_nguoi_lap` int(11) DEFAULT NULL,
  `id_du_an` int(11) DEFAULT NULL,
  `ly_do` text DEFAULT NULL,
  `trang_thai` varchar(50) DEFAULT 'Chờ duyệt',
  `nguoi_duyet` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_phieu_yc`),
  UNIQUE KEY `so_phieu` (`so_phieu`),
  KEY `fk_pyc_nguoilap` (`id_nguoi_lap`),
  KEY `fk_pyc_duan` (`id_du_an`),
  KEY `fk_pyc_nguoiduyet` (`nguoi_duyet`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ton_kho`
--

DROP TABLE IF EXISTS `ton_kho`;
CREATE TABLE IF NOT EXISTS `ton_kho` (
  `id_kho` int(11) NOT NULL,
  `id_vat_tu` int(11) NOT NULL,
  `so_luong_ton` int(11) DEFAULT 0
) ;

--
-- Đang đổ dữ liệu cho bảng `ton_kho`
--

INSERT INTO `ton_kho` (`id_kho`, `id_vat_tu`, `so_luong_ton`) VALUES
(1, 1, 500),
(1, 2, 50),
(2, 2, 2),
(2, 4, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vai_tro`
--

DROP TABLE IF EXISTS `vai_tro`;
CREATE TABLE IF NOT EXISTS `vai_tro` (
  `id_vai_tro` int(11) NOT NULL AUTO_INCREMENT,
  `ten_vai_tro` varchar(50) NOT NULL,
  PRIMARY KEY (`id_vai_tro`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `vai_tro`
--

INSERT INTO `vai_tro` (`id_vai_tro`, `ten_vai_tro`) VALUES
(1, 'Admin'),
(2, 'Giám đốc'),
(3, 'Thủ kho'),
(4, 'Kế toán'),
(5, 'Quản lý công trường');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vat_tu`
--

DROP TABLE IF EXISTS `vat_tu`;
CREATE TABLE IF NOT EXISTS `vat_tu` (
  `id_vat_tu` int(11) NOT NULL AUTO_INCREMENT,
  `ma_vat_tu` varchar(50) NOT NULL,
  `ten_vat_tu` varchar(255) NOT NULL,
  `id_dvt` int(11) NOT NULL,
  `id_loai_vat_tu` int(11) DEFAULT NULL,
  `mo_ta` text DEFAULT NULL,
  `anh_vat_tu` varchar(255) DEFAULT NULL,
  `id_hsx` int(11) DEFAULT NULL,
  `id_kho` int(11) NOT NULL,
  PRIMARY KEY (`id_vat_tu`),
  UNIQUE KEY `ma_vat_tu` (`ma_vat_tu`),
  KEY `fk_vattu_dvt` (`id_dvt`),
  KEY `fk_vattu_hsx` (`id_hsx`),
  KEY `fk_vat_tu_loai` (`id_loai_vat_tu`),
  KEY `id_kho` (`id_kho`),
  KEY `id_kho_2` (`id_kho`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `vat_tu`
--

INSERT INTO `vat_tu` (`id_vat_tu`, `ma_vat_tu`, `ten_vat_tu`, `id_dvt`, `id_loai_vat_tu`, `mo_ta`, `anh_vat_tu`, `id_hsx`, `id_kho`) VALUES
(1, 'THEP-D10', 'Thép cuộn D10', 4, 1, 'Thép xây dựng Hòa Phát', NULL, 1, 1),
(2, 'BONG-LED-20W', 'Bóng đèn Led 20W', 1, NULL, 'Đèn tiết kiệm điện Panasonic', NULL, 3, 1),
(3, 'DAY-DIEN-25', 'Dây điện 2.5mm', 3, 3, 'Dây đồng bọc nhựa', NULL, 4, 1),
(4, 'ấdasd', 'sadasd', 1, NULL, NULL, NULL, NULL, 2);

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `bao_cao_su_co`
--
ALTER TABLE `bao_cao_su_co`
  ADD CONSTRAINT `fk_bc_kho` FOREIGN KEY (`id_kho`) REFERENCES `kho` (`id_kho`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bc_nguoi` FOREIGN KEY (`id_nguoi_lap`) REFERENCES `nguoi_dung` (`id_nguoi_dung`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bc_vattu` FOREIGN KEY (`id_vat_tu`) REFERENCES `vat_tu` (`id_vat_tu`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `chi_tiet_dieu_chuyen`
--
ALTER TABLE `chi_tiet_dieu_chuyen`
  ADD CONSTRAINT `fk_ctdc_phieu` FOREIGN KEY (`id_phieu_chuyen`) REFERENCES `phieu_dieu_chuyen` (`id_phieu_chuyen`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctdc_vattu` FOREIGN KEY (`id_vat_tu`) REFERENCES `vat_tu` (`id_vat_tu`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `chi_tiet_kiem_ke`
--
ALTER TABLE `chi_tiet_kiem_ke`
  ADD CONSTRAINT `fk_ctkk_phieu` FOREIGN KEY (`id_phieu_kk`) REFERENCES `phieu_kiem_ke` (`id_phieu_kk`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctkk_vattu` FOREIGN KEY (`id_vat_tu`) REFERENCES `vat_tu` (`id_vat_tu`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `chi_tiet_nhap_kho`
--
ALTER TABLE `chi_tiet_nhap_kho`
  ADD CONSTRAINT `fk_ctnk_phieu` FOREIGN KEY (`id_phieu_nhap`) REFERENCES `phieu_nhap_kho` (`id_phieu_nhap`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctnk_vattu` FOREIGN KEY (`id_vat_tu`) REFERENCES `vat_tu` (`id_vat_tu`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `chi_tiet_xuat_kho`
--
ALTER TABLE `chi_tiet_xuat_kho`
  ADD CONSTRAINT `fk_ctxk_phieu` FOREIGN KEY (`id_phieu_xuat`) REFERENCES `phieu_xuat_kho` (`id_phieu_xuat`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctxk_vattu` FOREIGN KEY (`id_vat_tu`) REFERENCES `vat_tu` (`id_vat_tu`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `chi_tiet_yeu_cau`
--
ALTER TABLE `chi_tiet_yeu_cau`
  ADD CONSTRAINT `fk_ctyc_phieu` FOREIGN KEY (`id_phieu_yc`) REFERENCES `phieu_yeu_cau` (`id_phieu_yc`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctyc_vattu` FOREIGN KEY (`id_vat_tu`) REFERENCES `vat_tu` (`id_vat_tu`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  ADD CONSTRAINT `fk_nguoidung_vaitro` FOREIGN KEY (`id_vai_tro`) REFERENCES `vai_tro` (`id_vai_tro`);

--
-- Các ràng buộc cho bảng `phieu_dieu_chuyen`
--
ALTER TABLE `phieu_dieu_chuyen`
  ADD CONSTRAINT `fk_pdc_khonhap` FOREIGN KEY (`id_kho_nhap`) REFERENCES `kho` (`id_kho`),
  ADD CONSTRAINT `fk_pdc_khoxuat` FOREIGN KEY (`id_kho_xuat`) REFERENCES `kho` (`id_kho`),
  ADD CONSTRAINT `fk_pdc_nguoi` FOREIGN KEY (`id_nguoi_lap`) REFERENCES `nguoi_dung` (`id_nguoi_dung`);

--
-- Các ràng buộc cho bảng `phieu_nhap_kho`
--
ALTER TABLE `phieu_nhap_kho`
  ADD CONSTRAINT `fk_pnhap_kho` FOREIGN KEY (`id_kho`) REFERENCES `kho` (`id_kho`),
  ADD CONSTRAINT `fk_pnhap_ncc` FOREIGN KEY (`id_ncc`) REFERENCES `nha_cung_cap` (`id_ncc`),
  ADD CONSTRAINT `fk_pnhap_nguoi` FOREIGN KEY (`id_nguoi_lap`) REFERENCES `nguoi_dung` (`id_nguoi_dung`);

--
-- Các ràng buộc cho bảng `phieu_xuat_kho`
--
ALTER TABLE `phieu_xuat_kho`
  ADD CONSTRAINT `fk_pxuat_duan` FOREIGN KEY (`id_du_an`) REFERENCES `du_an` (`id_du_an`),
  ADD CONSTRAINT `fk_pxuat_kho` FOREIGN KEY (`id_kho`) REFERENCES `kho` (`id_kho`),
  ADD CONSTRAINT `fk_pxuat_nguoi` FOREIGN KEY (`id_nguoi_lap`) REFERENCES `nguoi_dung` (`id_nguoi_dung`);

--
-- Các ràng buộc cho bảng `phieu_yeu_cau`
--
ALTER TABLE `phieu_yeu_cau`
  ADD CONSTRAINT `fk_pyc_duan` FOREIGN KEY (`id_du_an`) REFERENCES `du_an` (`id_du_an`),
  ADD CONSTRAINT `fk_pyc_nguoiduyet` FOREIGN KEY (`nguoi_duyet`) REFERENCES `nguoi_dung` (`id_nguoi_dung`),
  ADD CONSTRAINT `fk_pyc_nguoilap` FOREIGN KEY (`id_nguoi_lap`) REFERENCES `nguoi_dung` (`id_nguoi_dung`);

--
-- Các ràng buộc cho bảng `vat_tu`
--
ALTER TABLE `vat_tu`
  ADD CONSTRAINT `fk_vat_tu_loai` FOREIGN KEY (`id_loai_vat_tu`) REFERENCES `loai_vat_tu` (`id_loai_vat_tu`),
  ADD CONSTRAINT `fk_vattu_dvt` FOREIGN KEY (`id_dvt`) REFERENCES `don_vi_tinh` (`id_dvt`),
  ADD CONSTRAINT `fk_vattu_hsx` FOREIGN KEY (`id_hsx`) REFERENCES `hang_san_xuat` (`id_hsx`),
  ADD CONSTRAINT `fk_vattu_kho` FOREIGN KEY (`id_kho`) REFERENCES `kho` (`id_kho`) ON UPDATE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
