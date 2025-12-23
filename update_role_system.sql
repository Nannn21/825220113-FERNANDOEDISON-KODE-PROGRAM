-- ========================================
-- UPDATE ROLE SYSTEM: 2 Roles Only
-- Superadmin dan Karyawan
-- ========================================

-- Step 1: Backup data user yang ada (optional)
CREATE TABLE IF NOT EXISTS User_backup AS SELECT * FROM User;

-- Step 2: Hapus semua role yang ada
DELETE FROM Role;

-- Step 3: Reset AUTO_INCREMENT untuk Role
ALTER TABLE Role AUTO_INCREMENT = 1;

-- Step 4: Insert 2 role baru
INSERT INTO Role (nama_role) VALUES 
('Superadmin'),
('Karyawan');

-- Step 5: Update role_id untuk user yang ada
-- Administrator (role_id 1) -> Superadmin (role_id 1)
-- Manager (role_id 2) -> Karyawan (role_id 2)
-- Staff (role_id 3) -> Karyawan (role_id 2)

-- Update user dengan role_id 1 (Administrator) -> tetap role_id 1 (Superadmin)
UPDATE User SET role_id = 1 WHERE role_id = 1;

-- Update user dengan role_id 2 (Manager) -> role_id 2 (Karyawan)
UPDATE User SET role_id = 2 WHERE role_id = 2;

-- Update user dengan role_id 3 (Staff) -> role_id 2 (Karyawan)
UPDATE User SET role_id = 2 WHERE role_id = 3;

-- Step 6: Verifikasi hasil
SELECT 
    r.role_id, 
    r.nama_role, 
    COUNT(u.user_id) as jumlah_user,
    GROUP_CONCAT(u.nama_lengkap SEPARATOR ', ') as daftar_user
FROM Role r
LEFT JOIN User u ON r.role_id = u.role_id
GROUP BY r.role_id, r.nama_role;

-- Step 7: Tampilkan semua user dengan role barunya
SELECT 
    u.user_id,
    u.nama_lengkap,
    u.username,
    r.nama_role
FROM User u
INNER JOIN Role r ON u.role_id = r.role_id
ORDER BY r.role_id, u.nama_lengkap;

