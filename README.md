# Face Recognition API

Multi-tenant face recognition microservice dengan Redis caching.

## Quick Start

```bash
# Install
pip install -r requirements.txt

# Copy & edit config
cp .env.example .env

# Run
uvicorn main:app --reload --host 0.0.0.0 --port 8000
```

---

## API Documentation

### 1. Enroll Face
Simpan wajah baru ke database.

```
POST /enroll
Content-Type: multipart/form-data
```

**Request:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| tenant_id | int | ✓ | ID tenant |
| user_id | int | ✓ | ID user di tabel user_{tenant_id} |
| name | string | ✓ | Nama/label untuk enrollment |
| file | file | ✓ | Gambar wajah (JPEG/PNG) |

**Response:**
```json
{
  "stored": {
    "id": 1,
    "user_id": 1,
    "label": "John Doe",
    "tenant_id": 1
  },
  "count": 5,
  "tenant_id": 1
}
```

---

### 2. Identify Face
Identifikasi wajah dari database enrollment.

```
POST /identify
Content-Type: multipart/form-data
```

**Request:**
| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| tenant_id | int | ✓ | - | ID tenant |
| file | file | ✓ | - | Gambar wajah |
| threshold | float | - | 0.35 | Threshold (0.30-0.40, lebih kecil = lebih ketat) |

**Response (Match):**
```json
{
  "match": true,
  "name": "John Doe",
  "user_id": 1,
  "enrollment_id": 1,
  "distance": 0.28,
  "threshold": 0.35,
  "count": 5,
  "bbox": {
    "left": 120,
    "top": 80,
    "right": 320,
    "bottom": 350,
    "det_score": 0.99
  },
  "tenant_id": 1
}
```

**Response (No Match):**
```json
{
  "match": false,
  "name": "Closest Name",
  "user_id": 2,
  "enrollment_id": 3,
  "distance": 0.52,
  "threshold": 0.35,
  "count": 5,
  "bbox": {...},
  "tenant_id": 1
}
```

---

### 3. List Enrollments
Daftar semua enrollment untuk tenant.

```
GET /enrollments/{tenant_id}
```

**Response:**
```json
{
  "enrollments": [
    {"id": 1, "user_id": 1, "name": "John Doe", "created_at": "2024-01-15 10:30:00"},
    {"id": 2, "user_id": 2, "name": "Jane Doe", "created_at": "2024-01-15 11:00:00"}
  ],
  "tenant_id": 1
}
```

---

### 4. Delete Enrollment

**By ID:**
```
DELETE /enrollments/{tenant_id}/{enrollment_id}
```

**By Name:**
```
DELETE /enrollments/{tenant_id}/name/{name}
```

**Response:**
```json
{
  "deleted": 1,
  "count": 4,
  "tenant_id": 1
}
```

---

### 5. Refresh Cache
Invalidate cache dan reload data dari database.

```
POST /cache/{tenant_id}/refresh-enrollments
```

**Response:**
```json
{
  "cache_refreshed": true,
  "tenant_id": 1,
  "enrollment_count": 5
}
```

---

### 6. Cache Status
Cek status cache tenant.

```
GET /cache/{tenant_id}/status
```

**Response:**
```json
{
  "tenant_id": 1,
  "enrollment_cache_exists": true,
  "enrollment_cache_ttl": 45,
  "config_cache_exists": true,
  "config_cache_ttl": 280,
  "connection_pool_active": true
}
```

---

### 7. Health Check

```
GET /health
```

**Response:**
```json
{
  "status": "ok",
  "version": "2.0.0"
}
```

---

## Error Responses

```json
{
  "detail": "Error message here"
}
```

| Status | Description |
|--------|-------------|
| 400 | Bad request (no face detected, invalid file) |
| 404 | Tenant/enrollment not found |
| 500 | Server error |
