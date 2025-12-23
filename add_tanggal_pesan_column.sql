-- SQL Migration: Menambahkan kolom tanggal_pesan untuk menghitung lead time yang akurat
-- Lead Time = selisih antara tanggal_pesan (waktu pesan) dan tanggal (waktu diterima)

-- Tambahkan kolom tanggal_pesan di tabel TransaksiMasuk
ALTER TABLE TransaksiMasuk 
ADD COLUMN tanggal_pesan DATETIME NULL AFTER tanggal;

-- Update data existing: set tanggal_pesan = tanggal (untuk data lama)
-- Ini akan membuat lead time = 0 untuk data lama
UPDATE TransaksiMasuk 
SET tanggal_pesan = tanggal 
WHERE tanggal_pesan IS NULL;

-- Buat index untuk optimasi query
CREATE INDEX IF NOT EXISTS idx_transaksimasuk_tanggal_pesan ON TransaksiMasuk(tanggal_pesan);

