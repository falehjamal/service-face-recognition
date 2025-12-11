# Face Recognition Microservice

Microservice FastAPI untuk encode, compare, dan deteksi wajah menggunakan `face_recognition` (dlib) dari repositori https://github.com/ageitgey/face_recognition dengan endpoint async.

## Kebutuhan
- Python 3.10+
- Compiler toolchain (hanya jika membangun dlib dari source)

## Instalasi
1. Clone atau salin repo ini, lalu masuk ke folder proyek ini (root repo).
2. (Opsional tapi disarankan) Buat virtualenv: `python -m venv .venv && source .venv/bin/activate` (Windows: `source .venv/Scripts/activate`).
3. Instal dependensi Python:
   ```bash
   pip install -r requirements.txt
   ```
4. Instal dlib:
   - **Linux**: pastikan `cmake`, `build-essential`, `libopenblas-dev`, dan `liblapack-dev` terpasang. Lalu:
     ```bash
     pip install dlib
     ```
   - **Windows (praktis)**: gunakan wheel prebuilt `dlib-bin` lalu pasang `face_recognition` tanpa menarik dlib sumber:
     ```bash
     pip install dlib-bin==19.24.6 --no-deps
     pip install face_recognition --no-deps
     ```
     Jika ingin membangun sendiri: install Build Tools for Visual Studio + CMake (tambahkan ke PATH), lalu `pip install dlib`.

## Menjalankan Service
Dari root folder proyek:
```bash
uvicorn main:app --reload
```
Service berjalan di `http://localhost:8000` (docs di `/docs`).

## Endpoint
- `POST /encode`
  - Form-data: `file` (image)
  - Output: `{ "encoding": [128 float] }`
- `POST /compare`
  - Form-data: `file` (image), `encoding` (JSON array string berisi 128 float), opsional `threshold` (default 0.6)
  - Output: `{ "match": bool, "distance": float }`
- `POST /detect`
  - Form-data: `file` (image)
  - Output: `{ "faces": [{"top": int, "right": int, "bottom": int, "left": int}, ...] }`
- `GET /health`
  - Output: `{ "status": "ok" }`

## Contoh cURL
- Encode:
  ```bash
  curl -X POST http://localhost:8000/encode \
    -F "file=@/path/to/image.jpg"
  ```
- Compare (encoding dikirim sebagai JSON string pada field form `encoding`):
  ```bash
  curl -X POST http://localhost:8000/compare \
    -F "file=@/path/to/image.jpg" \
    -F 'encoding=[0.1,0.2,0.3,...128 items...]' \
    -F "threshold=0.6"
  ```
- Detect:
  ```bash
  curl -X POST http://localhost:8000/detect \
    -F "file=@/path/to/image.jpg"
  ```
- Health:
  ```bash
  curl http://localhost:8000/health
  ```

## Workflow Singkat Encode-Compare
1. `POST /encode`: kirim gambar wajah tunggal, service mengembalikan vektor embedding 128 dimensi.
2. Simpan embedding tersebut di aplikasi Anda.
3. `POST /compare`: kirim gambar baru dan embedding target, service menghitung jarak Euclidean; hasil `match` true jika jarak <= threshold (default 0.6).
