import asyncio
from typing import Dict, List, Sequence

import cv2
import numpy as np
from fastapi import HTTPException, UploadFile

from services import insight as insight_backend

# Cosine distance threshold for ArcFace embeddings (L2-normalized)
DEFAULT_THRESHOLD = 0.35


async def _run_in_thread(func, *args, **kwargs):
    loop = asyncio.get_running_loop()
    return await loop.run_in_executor(None, lambda: func(*args, **kwargs))


def _decode_image(data: bytes) -> np.ndarray:
    """Decode raw bytes into BGR image for InsightFace."""
    arr = np.frombuffer(data, dtype=np.uint8)
    image = cv2.imdecode(arr, cv2.IMREAD_COLOR)
    if image is None:
        raise HTTPException(status_code=400, detail="Failed to decode image")
    return image


def _bbox_from_meta(meta: Dict[str, object]) -> Dict[str, float]:
    bbox = meta.get("bbox") if isinstance(meta, dict) else None
    if not bbox or len(bbox) < 4:
        return {}
    left, top, right, bottom = bbox[:4]
    return {
        "left": float(left),
        "top": float(top),
        "right": float(right),
        "bottom": float(bottom),
        "det_score": float(meta.get("det_score", 0.0)) if isinstance(meta, dict) else 0.0,
    }


async def encode_image_with_box(file: UploadFile) -> tuple[List[float], Dict[str, float]]:
    data = await file.read()
    if not data:
        raise HTTPException(status_code=400, detail="Image file is empty")
    image = _decode_image(data)
    try:
        encoding, meta = await _run_in_thread(insight_backend.encode_face, image)
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc
    bbox = _bbox_from_meta(meta)
    return encoding, bbox


async def encode_image(file: UploadFile) -> List[float]:
    encoding, _ = await encode_image_with_box(file)
    return encoding


async def compare_face(
    file: UploadFile, target_encoding: Sequence[float], threshold: float = DEFAULT_THRESHOLD
) -> Dict[str, float | bool]:
    if len(target_encoding) != 512:
        raise HTTPException(status_code=400, detail="Target encoding must have length 512 (ArcFace)")

    data = await file.read()
    if not data:
        raise HTTPException(status_code=400, detail="Image file is empty")

    image = _decode_image(data)
    try:
        encoding, _ = await _run_in_thread(insight_backend.encode_face, image)
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc

    src = np.array(encoding, dtype=float)
    tgt = np.array(target_encoding, dtype=float)
    if len(tgt) != len(src):
        raise HTTPException(status_code=400, detail="Encoding length mismatch; re-enroll using current model (512-d ArcFace).")

    distance = float(1.0 - float(np.dot(src, tgt)))
    return {"match": distance <= threshold, "distance": distance, "threshold": threshold}


async def detect_faces(file: UploadFile) -> List[Dict[str, object]]:
    data = await file.read()
    if not data:
        raise HTTPException(status_code=400, detail="Image file is empty")
    image = _decode_image(data)
    faces = await _run_in_thread(insight_backend.detect_faces, image)
    return faces
