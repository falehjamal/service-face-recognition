import functools
import math
from typing import Dict, List, Tuple

import cv2
import insightface
import numpy as np

# Lazy globals
_model = None
_embedder = None


def _ensure_model():
    global _model, _embedder
    if _model is None:
        # Use default retinaface_r50_v1 and arcface_r100_v1
        _model = insightface.app.FaceAnalysis(name="buffalo_l")
        _model.prepare(ctx_id=0, det_size=(640, 640))
    if _embedder is None:
        _embedder = _model.models["recognition"]  # ArcFace


def detect_faces(image: np.ndarray) -> List[Dict[str, object]]:
    _ensure_model()
    faces = _model.get(image)
    results: List[Dict[str, object]] = []
    for f in faces:
        box = f.bbox.astype(int).tolist()
        results.append(
            {
                "bbox": {"left": box[0], "top": box[1], "right": box[2], "bottom": box[3]},
                "kps": f.kps.astype(float).tolist(),
                "det_score": float(f.det_score),
            }
        )
    return results


def encode_face(image: np.ndarray) -> Tuple[List[float], Dict[str, object]]:
    _ensure_model()
    faces = _model.get(image)
    if len(faces) == 0:
        raise ValueError("No face detected")
    # pick largest face
    faces = sorted(faces, key=lambda f: (f.bbox[2] - f.bbox[0]) * (f.bbox[3] - f.bbox[1]), reverse=True)
    face = faces[0]
    emb = face.normed_embedding  # already L2-normalized
    meta = {
        "bbox": face.bbox.astype(int).tolist(),
        "kps": face.kps.astype(float).tolist(),
        "det_score": float(face.det_score),
    }
    return emb.astype(float).tolist(), meta


def cosine_distance(a: np.ndarray, b: np.ndarray) -> float:
    # embeddings are normalized; cosine distance = 1 - cos_sim
    return float(1.0 - np.dot(a, b))


def best_match(source_emb: List[float], gallery: List[Dict[str, object]]) -> Dict[str, object]:
    src = np.array(source_emb, dtype=float)
    best = {"name": None, "distance": math.inf}
    for rec in gallery:
        dist = cosine_distance(src, np.array(rec["encoding"], dtype=float))
        if dist < best["distance"]:
            best = {"name": rec["name"], "distance": dist}
    return best
