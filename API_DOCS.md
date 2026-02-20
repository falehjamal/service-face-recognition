# Face Recognition API Documentation

Dokumentasi ini dirancang khusus agar mudah dibaca oleh AI (LLM) untuk keperluan integrasi otomatis (Code Generation) pada aplikasi klien (Frontend/Backend lain).

## ⚙️ Konfigurasi Dasar
- **Base URL**: `http://localhost:8000` (Sesuaikan dengan environment deployment)
- **Authentication**: Tidak ada (Public API, dilindungi via arsitektur internal/CORS)
- **Default Content-Type**: `multipart/form-data` (Kecuali endpoint GET/DELETE)
- **Format Response**: `application/json`

---

## 1️⃣ Basic Operations (Tanpa Database)
Operasi dasar pengolahan citra wajah tanpa menyimpan data ke database.

### 1.1. Encode Wajah
Mengubah gambar wajah menjadi vektor 512-dimensi (ArcFace).
- **Endpoint**: `POST /encode`
- **Content-Type**: `multipart/form-data`
- **Body Parameters**:
  - `file` (File, Required): Gambar wajah (JPEG/PNG).
- **Response (200 OK)**:
  ```json
  {
    "encoding": [0.0123, -0.0456, ...] // Array of 512 floats
  }
  ```

### 1.2. Compare Wajah (1:1 Manual)
Membandingkan gambar wajah dengan vektor encoding yang sudah ada.
- **Endpoint**: `POST /compare`
- **Content-Type**: `multipart/form-data`
- **Body Parameters**:
  - `file` (File, Required): Gambar wajah (JPEG/PNG).
  - `encoding` (String, Required): JSON string array berisi 512 floats (contoh: `"[0.012, -0.045, ...]"`).
  - `threshold` (Float, Optional): Batas toleransi kemiripan (Default: `0.35`).
- **Response (200 OK)**:
  ```json
  {
    "match": true,
    "distance": 0.25,
    "threshold": 0.35
  }
  ```

### 1.3. Deteksi Wajah
Mendeteksi posisi wajah dalam gambar (Bounding Box).
- **Endpoint**: `POST /detect`
- **Content-Type**: `multipart/form-data`
- **Body Parameters**:
  - `file` (File, Required): Gambar wajah (JPEG/PNG).
- **Response (200 OK)**:
  ```json
  {
    "faces": [
      [120.5, 80.2, 250.0, 300.5] // [x1, y1, x2, y2]
    ]
  }
  ```

---

## 2️⃣ Multi-Tenant Operations (Dengan Database & Redis)
Operasi utama untuk sistem absensi/keamanan yang terisolasi per tenant (sekolah/perusahaan).

### 2.1. Enroll Wajah (Daftar Wajah Baru)
Mendaftarkan wajah user ke database tenant.
- **Endpoint**: `POST /enroll`
- **Content-Type**: `multipart/form-data`
- **Body Parameters**:
  - `tenant_id` (Integer, Required): ID Tenant/Sekolah.
  - `user_id` (Integer, Required): ID User/Siswa di database tenant.
  - `name` (String, Required): Nama user.
  - `file` (File, Required): Gambar wajah (JPEG/PNG).
- **Response (200 OK)**:
  ```json
  {
    "stored": true,
    "count": 150,
    "tenant_id": 1
  }
  ```

### 2.2. Identify Wajah (Pencarian 1:N)
Mencari identitas wajah dari seluruh data yang terdaftar di tenant.
- **Endpoint**: `POST /identify`
- **Content-Type**: `multipart/form-data`
- **Body Parameters**:
  - `tenant_id` (Integer, Required): ID Tenant/Sekolah.
  - `file` (File, Required): Gambar wajah (JPEG/PNG).
  - `threshold` (Float, Optional): Batas toleransi kemiripan (Default: `0.35`).
- **Response (200 OK)**:
  ```json
  {
    "match": true,
    "name": "Budi Santoso",
    "user_id": 123,
    "distance": 0.21,
    "box": [120.5, 80.2, 250.0, 300.5]
  }
  ```

### 2.3. Verify Wajah (Pencocokan 1:1 untuk Absensi)
Memastikan wajah yang dikirim adalah benar milik `user_id` tertentu. Sangat cepat karena langsung membandingkan 1 data.
- **Endpoint**: `POST /verify`
- **Content-Type**: `multipart/form-data`
- **Body Parameters**:
  - `tenant_id` (Integer, Required): ID Tenant/Sekolah.
  - `user_id` (Integer, Required): ID User/Siswa yang akan diverifikasi.
  - `file` (File, Required): Gambar wajah (JPEG/PNG).
  - `threshold` (Float, Optional): Batas toleransi kemiripan (Default: `0.35`).
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "verified": true,
    "message": "Verifikasi berhasil, wajah cocok",
    "user_id": 123,
    "distance": 0.25
  }
  ```

### 2.4. Get Daftar Enrollment
Melihat semua data wajah yang terdaftar di suatu tenant.
- **Endpoint**: `GET /enrollments/{tenant_id}`
- **Path Parameters**:
  - `tenant_id` (Integer, Required): ID Tenant/Sekolah.
- **Response (200 OK)**:
  ```json
  {
    "tenant_id": 1,
    "enrollments": [
      {
        "id": 10,
        "user_id": 123,
        "name": "Budi Santoso",
        "encoding": [...]
      }
    ]
  }
  ```

### 2.5. Hapus Enrollment by ID
- **Endpoint**: `DELETE /enrollments/{tenant_id}/{enrollment_id}`
- **Path Parameters**:
  - `tenant_id` (Integer, Required)
  - `enrollment_id` (Integer, Required)

### 2.6. Hapus Enrollment by Name
- **Endpoint**: `DELETE /enrollments/{tenant_id}/name/{name}`
- **Path Parameters**:
  - `tenant_id` (Integer, Required)
  - `name` (String, Required)

---

## 3️⃣ Cache Management (Redis)
Digunakan untuk sinkronisasi data antara Database MySQL dan Redis Cache.

### 3.1. Invalidate Cache
Menghapus semua cache untuk tenant tertentu.
- **Endpoint**: `POST /cache/{tenant_id}/invalidate`

### 3.2. Refresh Enrollments Cache
Memaksa sistem untuk memuat ulang data wajah dari MySQL ke Redis. Gunakan ini jika ada perubahan data langsung di database MySQL tanpa melalui API `/enroll`.
- **Endpoint**: `POST /cache/{tenant_id}/refresh-enrollments`
- **Response (200 OK)**:
  ```json
  {
    "cache_refreshed": true,
    "tenant_id": 1,
    "enrollment_count": 150
  }
  ```

### 3.3. Cek Status Cache
- **Endpoint**: `GET /cache/{tenant_id}/status`

---

## 4️⃣ System
### 4.1. Health Check
- **Endpoint**: `GET /health`
- **Response (200 OK)**:
  ```json
  {
    "status": "ok",
    "version": "2.0.0"
  }
  ```

---

## ⚠️ Standar Error Response
Jika terjadi kesalahan (Wajah tidak terdeteksi, Tenant tidak ditemukan, dll), API akan mengembalikan HTTP Status Code `400`, `404`, atau `500` dengan format JSON berikut:
```json
{
  "detail": "Pesan error yang spesifik (contoh: No face detected in image)"
}
```
