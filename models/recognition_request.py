import json

from fastapi import Form, HTTPException
from pydantic import BaseModel, conlist


class FaceCompareRequest(BaseModel):
    encoding: conlist(float, min_length=128, max_length=128)
    threshold: float = 0.6

    @classmethod
    def as_form(
        cls,
        encoding: str = Form(..., description="JSON array of 128 floats"),
        threshold: float = Form(0.6, description="Match threshold (default 0.6)"),
    ) -> "FaceCompareRequest":
        try:
            parsed = json.loads(encoding)
        except json.JSONDecodeError as exc:
            raise HTTPException(status_code=400, detail="encoding must be a JSON array") from exc

        if not isinstance(parsed, list):
            raise HTTPException(status_code=400, detail="encoding must be a JSON array")

        return cls(encoding=parsed, threshold=threshold)
