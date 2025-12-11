import json

from fastapi import Form, HTTPException
from pydantic import BaseModel, conlist


class FaceCompareRequest(BaseModel):
    encoding: conlist(float, min_length=512, max_length=512)
    threshold: float = 0.35

    @classmethod
    def as_form(
        cls,
        encoding: str = Form(..., description="JSON array of 512 floats (ArcFace)"),
        threshold: float = Form(0.35, description="Cosine-distance threshold (default 0.35)"),
    ) -> "FaceCompareRequest":
        try:
            parsed = json.loads(encoding)
        except json.JSONDecodeError as exc:
            raise HTTPException(status_code=400, detail="encoding must be a JSON array") from exc

        if not isinstance(parsed, list):
            raise HTTPException(status_code=400, detail="encoding must be a JSON array")

        return cls(encoding=parsed, threshold=threshold)
