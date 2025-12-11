import asyncio
from typing import Dict, List, Sequence

import numpy as np
from fastapi import HTTPException, UploadFile

from services import facenet_backend

DEFAULT_THRESHOLD = 0.8  # cosine distance threshold for facenet embeddings


async def _run_in_thread(func, *args, **kwargs):
    loop = asyncio.get_running_loop()
    return await loop.run_in_executor(None, lambda: func(*args, **kwargs))


async def encode_image_with_box(file: UploadFile) -> tuple[List[float], Dict[str, int | float]]:
    data = await file.read()
    if not data:
        raise HTTPException(status_code=400, detail="Image file is empty")
    try:
        encoding, bbox = await _run_in_thread(facenet_backend.encode_bytes, data)
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc
    return encoding, bbox


async def encode_image(file: UploadFile) -> List[float]:
    encoding, _ = await encode_image_with_box(file)
    return encoding


async def compare_face(
    file: UploadFile, target_encoding: Sequence[float], threshold: float = DEFAULT_THRESHOLD
) -> Dict[str, float | bool]:
    if len(target_encoding) not in (128, 512):
        raise HTTPException(status_code=400, detail="Target encoding must have length 512 (preferred) or 128")

    data = await file.read()
    if not data:
        raise HTTPException(status_code=400, detail="Image file is empty")
    try:
        encoding, _ = await _run_in_thread(facenet_backend.encode_bytes, data)
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc

    src = np.array(encoding, dtype=float)
    tgt = np.array(target_encoding, dtype=float)
    if len(tgt) != len(src):
        raise HTTPException(status_code=400, detail="Encoding length mismatch; re-enroll/encode with the same model (512-d preferred).")
    if len(tgt) == 512:
        distance = float(1.0 - float(np.dot(src, tgt)))
    else:
        distance = float(np.linalg.norm(src - tgt))
        threshold = 0.6

    return {"match": distance <= threshold, "distance": distance}


async def detect_faces(file: UploadFile) -> List[Dict[str, int]]:
    data = await file.read()
    if not data:
        raise HTTPException(status_code=400, detail="Image file is empty")
    faces = await _run_in_thread(facenet_backend.detect_bytes, data)
    return faces
