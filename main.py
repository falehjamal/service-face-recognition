from fastapi import Depends, FastAPI, File, Form, UploadFile
from fastapi.middleware.cors import CORSMiddleware

from models.recognition_request import FaceCompareRequest
from services import recognition
from services import enrollment

app = FastAPI(title="Face Recognition Service", version="1.0.0")

# Allow local client (adjust origins as needed)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # tighten in production
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.post("/encode")
async def encode(file: UploadFile = File(...)):
    encoding = await recognition.encode_image(file)
    return {"encoding": encoding}


@app.post("/compare")
async def compare(
    file: UploadFile = File(...),
    payload: FaceCompareRequest = Depends(FaceCompareRequest.as_form),
):
    return await recognition.compare_face(file, payload.encoding, payload.threshold)


@app.post("/detect")
async def detect(file: UploadFile = File(...)):
    faces = await recognition.detect_faces(file)
    return {"faces": faces}


@app.post("/enroll")
async def enroll(name: str = Form(...), file: UploadFile = File(...)):
    # name passed as form-data text field
    return await enrollment.enroll_face(name, file)


@app.post("/identify")
async def identify(file: UploadFile = File(...), threshold: float = Form(recognition.DEFAULT_THRESHOLD)):
    return await enrollment.identify_face(file, threshold)


@app.get("/enrollments")
async def enrollments():
    return {"enrollments": enrollment.list_enrollments()}


@app.get("/health")
async def health():
    return {"status": "ok"}
