# Face Recognition Microservice (InsightFace)

FastAPI microservice untuk encode, enroll, dan identifikasi wajah berbasis InsightFace (`buffalo_l` = RetinaFace + ArcFace 512-dim, cosine distance).

## Kebutuhan
- Python 3.10+
- CPU: `onnxruntime` (default). Jika punya GPU dengan CUDA 11, InsightFace akan mencoba GPU otomatis.

## Instalasi
1. Masuk ke folder proyek.
2. (Opsional) buat virtualenv: `python -m venv .venv && .venv\Scripts\activate` (Windows) atau `source .venv/bin/activate` (Unix).
3. Instal dependensi:
   ```bash
   pip install -r requirements.txt
   ```

## Menjalankan Service
```bash
uvicorn main:app --reload
```
Docs tersedia di `http://localhost:8000/docs`.

## Endpoint
- `POST /encode`
  - Form-data: `file` (image)
  - Output: `{ "encoding": [512 float] }`
- `POST /compare`
  - Form-data: `file` (image), `encoding` (JSON array string 512 float ArcFace), opsional `threshold` (default 0.35)
  - Output: `{ "match": bool, "distance": float, "threshold": float }`
- `POST /detect`
  - Form-data: `file` (image)
  - Output: `{ "faces": [{"bbox": {"left": int, "top": int, "right": int, "bottom": int}, "det_score": float, ...}] }`
- `POST /enroll`
  - Form-data: `name` (text), `file` (image). Menyimpan embedding ke folder `database/*.json`.
- `POST /identify`
  - Form-data: `file` (image), opsional `threshold` (default 0.35). Mengembalikan `{ match, name, distance, threshold, bbox }`.
- `GET /enrollments`
  - Daftar nama yang sudah dienroll.
- `GET /health`
  - Cek status service.

## Workflow Singkat
1. `POST /enroll` dengan foto wajah + `name`; embedding 512-d ArcFace disimpan ke `database/`.
2. `POST /identify` atau `POST /compare` untuk mencari kecocokan berdasarkan cosine distance (semakin kecil semakin mirip). Default threshold 0.35.

## Catatan
- Embedding lama (128-d) tidak kompatibel. Re-enroll dengan model baru bila sebelumnya memakai backend lain.
- Jika GPU tidak tersedia, service otomatis jatuh ke CPU (ctx_id=-1) sehingga lebih lambat tapi tetap berfungsi.
