import json
from pathlib import Path
from typing import Dict, List, Optional

import numpy as np
from fastapi import HTTPException, UploadFile

from services import recognition


DB_DIR = Path(__file__).resolve().parent.parent / "database"
DB_DIR.mkdir(parents=True, exist_ok=True)

# In-memory enrollment store; backed by files in database/
ENROLLMENTS: List[Dict[str, object]] = []


def _ensure_single_face_encoding(encoding: List[float]) -> List[float]:
    if len(encoding) != 512:
        raise HTTPException(status_code=500, detail="Encoding length mismatch; expected 512-d ArcFace")
    return encoding


def _slugify(name: str) -> str:
    slug = "".join(ch.lower() if ch.isalnum() or ch in ("-", "_") else "_" for ch in name).strip("_")
    return slug or "face"


def _next_filename(base: str) -> Path:
    candidate = DB_DIR / f"{base}.json"
    counter = 1
    while candidate.exists():
        candidate = DB_DIR / f"{base}-{counter}.json"
        counter += 1
    return candidate


def _save_record(record: Dict[str, object]) -> None:
    base = _slugify(str(record.get("name", "face")))
    target = _next_filename(base)
    with target.open("w", encoding="utf-8") as f:
        json.dump(record, f)


def _load_records() -> None:
    ENROLLMENTS.clear()
    for path in sorted(DB_DIR.glob("*.json")):
        try:
            with path.open("r", encoding="utf-8") as f:
                data = json.load(f)
            encoding = _ensure_single_face_encoding(data.get("encoding", []))
            name = data.get("name") or path.stem
            ENROLLMENTS.append({"name": name, "encoding": encoding})
        except Exception:
            # Skip malformed files silently to keep service running.
            continue


def _delete_record(name: str) -> bool:
    """Remove a stored enrollment file by matching its name field."""
    for path in DB_DIR.glob("*.json"):
        try:
            with path.open("r", encoding="utf-8") as f:
                data = json.load(f)
            if (data.get("name") or path.stem) == name:
                path.unlink(missing_ok=True)
                return True
        except Exception:
            continue
    return False


async def enroll_face(name: str, file: UploadFile) -> Dict[str, object]:
    if not name:
        raise HTTPException(status_code=400, detail="Name is required")
    encoding = await recognition.encode_image(file)
    record = {"name": name, "encoding": _ensure_single_face_encoding(encoding)}
    ENROLLMENTS.append(record)
    _save_record(record)
    return {"stored": {"name": record["name"]}, "count": len(ENROLLMENTS)}


async def identify_face(file: UploadFile, threshold: float = recognition.DEFAULT_THRESHOLD) -> Dict[str, object]:
    if not ENROLLMENTS:
        raise HTTPException(status_code=404, detail="No enrollments available")

    encoding, bbox = await recognition.encode_image_with_box(file)
    source = np.array(encoding, dtype=float)

    best_name: Optional[str] = None
    best_distance: float = float("inf")
    skipped = 0

    for rec in ENROLLMENTS:
        target = np.array(rec["encoding"], dtype=float)
        if len(target) != len(source):
            skipped += 1
            continue
        distance = float(1.0 - float(np.dot(source, target)))
        if distance < best_distance:
            best_distance = distance
            best_name = rec["name"]

    if best_distance == float("inf"):
        if skipped:
            raise HTTPException(
                status_code=400,
                detail="No compatible enrollments. Re-enroll faces using current model (512-d).",
            )
        raise HTTPException(status_code=404, detail="No enrollments available")

    return {
        "match": best_distance <= threshold,
        "name": best_name,
        "distance": best_distance,
        "threshold": threshold,
        "count": len(ENROLLMENTS),
        "bbox": bbox,
    }


def list_enrollments() -> List[Dict[str, object]]:
    return [{"name": rec["name"]} for rec in ENROLLMENTS]


# Load existing enrollments at import time.
_load_records()


def delete_enrollment(name: str) -> Dict[str, object]:
    """Delete enrollment both in memory and persisted file."""
    global ENROLLMENTS
    before = len(ENROLLMENTS)
    ENROLLMENTS = [rec for rec in ENROLLMENTS if rec.get("name") != name]
    removed_file = _delete_record(name)
    after = len(ENROLLMENTS)
    if before == after and not removed_file:
        raise HTTPException(status_code=404, detail="Enrollment not found")
    return {"deleted": name, "count": after}
