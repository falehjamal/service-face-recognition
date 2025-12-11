import io
from typing import Dict, List

import numpy as np
import torch
from facenet_pytorch import InceptionResnetV1, MTCNN
from PIL import Image

_device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
_mtcnn: MTCNN | None = None
_resnet: InceptionResnetV1 | None = None


def _ensure_models():
    global _mtcnn, _resnet
    if _mtcnn is None:
        _mtcnn = MTCNN(image_size=160, margin=20, keep_all=True, device=_device)
    if _resnet is None:
        _resnet = InceptionResnetV1(pretrained="vggface2").eval().to(_device)


def _bytes_to_image(data: bytes) -> Image.Image:
    img = Image.open(io.BytesIO(data)).convert("RGB")
    return img


def detect_bytes(data: bytes) -> List[Dict[str, int]]:
    _ensure_models()
    img = _bytes_to_image(data)
    boxes, _ = _mtcnn.detect(img)
    result: List[Dict[str, int]] = []
    if boxes is None:
        return result
    for box in boxes:
        left, top, right, bottom = box.astype(int).tolist()
        result.append({"top": top, "right": right, "bottom": bottom, "left": left})
    return result


def encode_bytes(data: bytes) -> tuple[List[float], Dict[str, int | float]]:
    _ensure_models()
    img = _bytes_to_image(data)

    boxes, probs_det = _mtcnn.detect(img)
    if boxes is None or len(boxes) == 0:
        raise ValueError("No face detected")
    probs_arr = probs_det if hasattr(probs_det, "argmax") else np.array(probs_det)
    idx = int(np.asarray(probs_arr).argmax())
    box = boxes[idx].astype(int).tolist()

    aligned, probs = _mtcnn(img, return_prob=True)
    if aligned is None or aligned.size(0) == 0:
        raise ValueError("No face detected")
    face_tensor = aligned[idx:idx+1].to(_device)
    with torch.no_grad():
        emb = _resnet(face_tensor).cpu().numpy()[0]
    emb = emb / np.linalg.norm(emb)
    bbox = {"top": box[1], "right": box[2], "bottom": box[3], "left": box[0], "det_score": float(probs_arr[idx])}
    return emb.astype(float).tolist(), bbox
