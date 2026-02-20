# Face Recognition Service (Multi-Tenant)

Microservice pengenalan wajah (Face Recognition) berbasis **FastAPI** dan **InsightFace**. Sistem ini dirancang untuk mendukung arsitektur **Multi-Tenant** (banyak sekolah/klien) dan menggunakan **Redis** untuk caching data wajah agar proses pengenalan lebih cepat (high performance).

Sangat cocok digunakan untuk sistem absensi cerdas, verifikasi identitas, dan keamanan.

## ‚ú® Fitur Utama

- **Multi-Tenant Architecture**: Data wajah dan database diisolasi per tenant (misal: per sekolah/perusahaan).
- **High Accuracy Face Recognition**: Menggunakan model AI dari `insightface` dan `onnxruntime`.
- **Redis Caching**: Mempercepat proses identifikasi wajah (1:N) dan verifikasi (1:1) tanpa harus selalu query ke database.
- **Asynchronous API**: Dibangun dengan FastAPI dan `aiomysql` untuk performa tinggi dan non-blocking I/O.
- **Client-Side Pre-processing**: Dilengkapi dengan contoh implementasi frontend (PHP & MediaPipe) untuk deteksi wajah di sisi klien sebelum dikirim ke server.

## Ì≥Ç Struktur Project

```text
.
‚îú‚îÄ‚îÄ database/          # Script SQL untuk migrasi dan pembuatan tabel
‚îú‚îÄ‚îÄ models/            # Pydantic models untuk validasi request/response API
‚îú‚îÄ‚îÄ services/          # Core logic aplikasi (Database, InsightFace, Redis, Enrollment)
‚îú‚îÄ‚îÄ tests/web/         # Contoh implementasi frontend menggunakan PHP & MediaPipe
‚îú‚îÄ‚îÄ main.py            # Entry point aplikasi FastAPI (Routing & Endpoints)
‚îî‚îÄ‚îÄ requirements.txt   # Daftar dependensi Python
```

## Ìª†Ô∏è Prasyarat (Prerequisites)

Sebelum menjalankan project ini, pastikan sistem Anda memiliki:
- **Python 3.10+**
- **MySQL / MariaDB** (Untuk penyimpanan data utama)
- **Redis Server** (Untuk caching vector wajah)
- **C++ Build Tools** (Dibutuhkan oleh `insightface` saat instalasi di Windows)

## Ì∫Ä Cara Instalasi & Menjalankan

1. **Clone / Buka Project**
   Buka terminal dan arahkan ke direktori project.

2. **Buat Virtual Environment & Install Dependensi**
   ```bash
   python -m venv .venv
   source .venv/Scripts/activate  # Untuk Windows
   # source .venv/bin/activate    # Untuk Linux/Mac
   
   pip install -r requirements.txt
   ```

3. **Setup Database & Environment**
   - Buat database MySQL sesuai kebutuhan.
   - Jalankan script SQL yang ada di `database/migrations/create_tables.sql`.
   - Buat file `.env` (jika belum ada) dan sesuaikan konfigurasi koneksi Database & Redis.

4. **Jalankan Server FastAPI**
   ```bash
   # Mode Development (Auto-reload)
   uvicorn main:app --reload --host 0.0.0.0 --port 8000
   ```
   Server akan berjalan di `http://localhost:8000`.
   Anda bisa mengakses dokumentasi interaktif API (Swagger UI) di `http://localhost:8000/docs`.

## Ì≥° Daftar Endpoint API Utama

### Basic Operations (Tanpa Database)
- `POST /encode` : Mengubah gambar wajah menjadi vektor (512-dimensi).
- `POST /compare`: Membandingkan dua wajah (1:1) berdasarkan gambar dan vektor.
- `POST /detect` : Mendeteksi posisi wajah (bounding box) dalam sebuah gambar.

### Multi-Tenant Operations (Dengan Database & Redis)
- `POST /enroll`  : Mendaftarkan wajah baru untuk user tertentu di tenant tertentu.
- `POST /identify`: Mencari dan mengenali wajah dari seluruh data wajah yang ada di suatu tenant (1:N).
- `POST /verify`  : Memverifikasi apakah wajah yang dikirim cocok dengan user ID tertentu di suatu tenant (1:1).

## Ì≤ª Testing Web (Frontend)

Project ini menyertakan contoh implementasi frontend menggunakan PHP di dalam folder `tests/web/`. 
Fitur ini menggunakan **MediaPipe** untuk mendeteksi wajah secara realtime melalui webcam sebelum mengirimkan gambar ke backend FastAPI.

Untuk mencobanya, Anda bisa menggunakan Laragon/XAMPP atau menjalankan server PHP bawaan:
```bash
cd tests/web
php -S localhost:8080
```
Buka browser dan akses `http://localhost:8080/absensi_realtime.php`.
