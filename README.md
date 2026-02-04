# Service Face Recognition

Microservice pengenalan wajah berbasis **FastAPI** dengan dukungan **Multi-tenant** dan **Redis Caching**. Dirancang untuk performa tinggi dalam sistem absensi dan keamanan sekolah.

## Fitur Utama

- **Multi-tenant**: Database dan cache terisolasi per tenant (sekolah).
- **Verifikasi Wajah (1:1)**: Memastikan wajah cocok dengan user tertentu (untuk absensi).
- **Identifikasi Wajah (1:N)**: Mencari pemilik wajah dari database.
- **High Performance**: Menggunakan Redis untuk menyimpan encoding wajah, meminimalkan query database.
- **Utilitas**: Deteksi wajah dan encoding vektor langsung.

---

## Quick Start

### 1. Instalasi
Pastikan Python 3.10+ terinstall.

```bash
# Install dependencies
pip install -r requirements.txt

# Setup konfigurasi
cp .env.example .env
# Edit .env sesuaikan dengan database dan redis
```

### 2. Menjalankan Service

```bash
# Mode Development (Reload otomatis)
uvicorn main:app --reload --host 0.0.0.0 --port 8000

# Mode Production
uvicorn main:app --host 0.0.0.0 --port 8000 --workers 4
```

---

## Dokumentasi API

### A. Absensi & Verifikasi (Core)

Endpoint utama untuk fitur absensi. Membandingkan foto yang dikirim dengan data wajah user yang tersimpan.

#### 1. Verifikasi User
`POST /verify`

**Parameter (Form Data):**
- `tenant_id`: ID Sekolah/Tenant.
- `user_id`: ID Siswa/User yang akan absen.
- `file`: File foto wajah (JPEG/PNG).
- `threshold`: (Opsional) Batas kemiripan (Default: 0.35).

**Response Sukses:**
```json
{
  "success": true,
  "verified": true,
  "message": "Verifikasi berhasil, wajah cocok",
  "user_id": 123,
  "distance": 0.25
}
```

---

### B. Manajemen Wajah (Enrollment)

Mengelola data wajah siswa/guru dalam database.

#### 2. Daftarkan Wajah (Enroll)
`POST /enroll`

Menyimpan template wajah user baru.
**Parameter:** `tenant_id`, `user_id`, `name` (Nama User), `file` (Foto).

#### 3. Cari Identitas (Identify)
`POST /identify`

Mencari siapa pemilik wajah ini dari seluruh database tenant.
**Parameter:** `tenant_id`, `file`.

#### 4. Lihat Daftar Enrollment
`GET /enrollments/{tenant_id}`

#### 5. Hapus Enrollment
- By ID: `DELETE /enrollments/{tenant_id}/{enrollment_id}`
- By Name: `DELETE /enrollments/{tenant_id}/name/{nama_user}`

---

### C. Utilitas & Tools
Fungsi dasar pengolahan wajah tanpa database.

#### 6. Deteksi Wajah
`POST /detect`
Mengembalikan koordinat wajah (bounding box) dari foto yang diupload.

#### 7. Compare Manual
`POST /compare`
Membandingkan foto dengan vektor encoding tertentu secara langsung.

---

### D. System & Cache

#### 8. Refresh Cache
`POST /cache/{tenant_id}/refresh-enrollments`
Paksa reload data dari database ke Redis. Gunakan jika ada perubahan manual di database.

#### 9. Health Check
`GET /health`
Mengecek status service.

---

## Catatan Error
Format standar error jika terjadi masalah:

```json
{
  "detail": "Pesan error yang menjelaskan penyebabnya"
}
```
- **400 Bad Request**: Wajah tidak terdeteksi atau file rusak.
- **404 Not Found**: Tenant atau User tidak ditemukan.
