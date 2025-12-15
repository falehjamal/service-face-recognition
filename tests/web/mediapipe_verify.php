<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Verification | MediaPipe</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg: #0a0a0f;
            --card: rgba(255, 255, 255, 0.03);
            --border: rgba(255, 255, 255, 0.08);
            --text: #f1f5f9;
            --muted: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --primary: #6366f1;
            --info: #3b82f6;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 24px;
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            color: var(--muted);
            font-size: 14px;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 20px;
        }

        @media (max-width: 900px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--muted);
        }

        .video-section {
            position: relative;
            aspect-ratio: 4/3;
            background: #000;
        }

        #video,
        #overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        #video {
            object-fit: cover;
            transform: scaleX(-1);
        }

        #overlay {
            pointer-events: none;
            transform: scaleX(-1);
        }

        .face-guide {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 280px;
            border: 3px dashed rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            pointer-events: none;
            transition: all 0.3s;
        }

        .face-guide.detected {
            border-color: var(--success);
            border-style: solid;
        }

        .face-guide.warning {
            border-color: var(--warning);
        }

        .face-guide.error {
            border-color: var(--danger);
        }

        .controls {
            padding: 16px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        button {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-ghost {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--muted);
        }

        .btn-primary:hover:not(:disabled) {
            background: #4f46e5;
        }

        .btn-success:hover:not(:disabled) {
            background: #059669;
        }

        /* Validation Panel */
        .validation-panel {
            padding: 20px;
        }

        .validation-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }

        .validation-item:last-child {
            border-bottom: none;
        }

        .validation-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .validation-icon.pending {
            background: rgba(100, 116, 139, 0.2);
        }

        .validation-icon.ok {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        .validation-icon.warning {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning);
        }

        .validation-icon.error {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }

        .validation-content {
            flex: 1;
        }

        .validation-label {
            font-weight: 500;
            font-size: 13px;
        }

        .validation-value {
            font-size: 11px;
            color: var(--muted);
            margin-top: 2px;
        }

        /* Result Panel */
        .result-panel {
            padding: 24px;
            text-align: center;
            display: none;
        }

        .result-panel.show {
            display: block;
        }

        .result-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 16px;
        }

        .result-icon.success {
            background: rgba(16, 185, 129, 0.2);
        }

        .result-icon.error {
            background: rgba(239, 68, 68, 0.2);
        }

        .result-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .result-message {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 20px;
        }

        /* Config */
        .config-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            padding: 16px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            margin: 16px;
        }

        .config-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .config-item label {
            font-size: 11px;
            color: var(--muted);
            text-transform: uppercase;
        }

        .config-item input {
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 13px;
            width: 100px;
        }

        /* Status Badge */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.ready {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        .status-badge.waiting {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning);
        }

        .status-badge.error {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }

        /* Loading */
        .loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 16px;
        }

        .loading-overlay.show {
            display: flex;
        }

        .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Progress Bar */
        .progress-bar {
            height: 4px;
            background: var(--border);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-fill {
            height: 100%;
            transition: width 0.3s, background 0.3s;
        }

        .progress-fill.ok {
            background: var(--success);
        }

        .progress-fill.warning {
            background: var(--warning);
        }

        .progress-fill.error {
            background: var(--danger);
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>🎯 Face Verification</h1>
            <p class="subtitle">Validasi wajah dengan MediaPipe sebelum verifikasi backend</p>
        </header>

        <div class="main-grid">
            <!-- Camera Section -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Kamera</span>
                    <span id="statusBadge" class="status-badge waiting">Menunggu</span>
                </div>

                <div class="video-section">
                    <video id="video" autoplay playsinline muted></video>
                    <canvas id="overlay"></canvas>
                    <div id="faceGuide" class="face-guide"></div>

                    <div id="loadingOverlay" class="loading-overlay">
                        <div class="spinner"></div>
                        <span>Memverifikasi...</span>
                    </div>
                </div>

                <div class="config-row">
                    <div class="config-item">
                        <label>API URL</label>
                        <input type="text" id="apiUrl" value="http://localhost:8000" style="width:160px">
                    </div>
                    <div class="config-item">
                        <label>Tenant ID</label>
                        <input type="number" id="tenantId" value="1" min="1">
                    </div>
                    <div class="config-item">
                        <label>User ID</label>
                        <input type="number" id="userId" value="1" min="1">
                    </div>
                    <div class="config-item">
                        <label>Threshold</label>
                        <input type="number" id="threshold" value="0.35" step="0.01">
                    </div>
                </div>

                <div class="controls">
                    <button id="btnStart" class="btn-primary">
                        <span>▶️</span> Mulai Kamera
                    </button>
                    <button id="btnVerify" class="btn-success" disabled>
                        <span>✓</span> Verifikasi
                    </button>
                    <button id="btnStop" class="btn-ghost" disabled>
                        <span>⏹️</span> Stop
                    </button>
                </div>
            </div>

            <!-- Validation Panel -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Validasi Real-time</span>
                </div>

                <div class="validation-panel" id="validationPanel">
                    <!-- Face Detection -->
                    <div class="validation-item">
                        <div class="validation-icon pending" id="iconFace">👤</div>
                        <div class="validation-content">
                            <div class="validation-label">Deteksi Wajah</div>
                            <div class="validation-value" id="valueFace">Menunggu kamera...</div>
                        </div>
                    </div>

                    <!-- Face Size / Distance -->
                    <div class="validation-item">
                        <div class="validation-icon pending" id="iconDistance">📏</div>
                        <div class="validation-content">
                            <div class="validation-label">Jarak Wajah</div>
                            <div class="validation-value" id="valueDistance">-</div>
                            <div class="progress-bar">
                                <div id="barDistance" class="progress-fill" style="width:0%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Face Position -->
                    <div class="validation-item">
                        <div class="validation-icon pending" id="iconPosition">🎯</div>
                        <div class="validation-content">
                            <div class="validation-label">Posisi Wajah</div>
                            <div class="validation-value" id="valuePosition">-</div>
                        </div>
                    </div>

                    <!-- Lighting -->
                    <div class="validation-item">
                        <div class="validation-icon pending" id="iconLight">💡</div>
                        <div class="validation-content">
                            <div class="validation-label">Pencahayaan</div>
                            <div class="validation-value" id="valueLight">-</div>
                            <div class="progress-bar">
                                <div id="barLight" class="progress-fill" style="width:0%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Face Angle -->
                    <div class="validation-item">
                        <div class="validation-icon pending" id="iconAngle">↔️</div>
                        <div class="validation-content">
                            <div class="validation-label">Sudut Wajah</div>
                            <div class="validation-value" id="valueAngle">-</div>
                        </div>
                    </div>

                    <!-- Overall Status -->
                    <div class="validation-item" style="margin-top:12px;padding-top:16px;border-top:2px solid var(--border)">
                        <div class="validation-icon pending" id="iconOverall">⏳</div>
                        <div class="validation-content">
                            <div class="validation-label" style="font-size:14px">Status Keseluruhan</div>
                            <div class="validation-value" id="valueOverall">Menunggu validasi...</div>
                        </div>
                    </div>
                </div>

                <!-- Result Panel (hidden by default) -->
                <div class="result-panel" id="resultPanel">
                    <div class="result-icon" id="resultIcon">✓</div>
                    <div class="result-title" id="resultTitle">Verifikasi Berhasil</div>
                    <div class="result-message" id="resultMessage">Halo, John Doe!</div>
                    <button id="btnRetry" class="btn-primary" style="margin:0 auto">
                        🔄 Coba Lagi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MediaPipe Face Detection -->
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_detection/face_detection.js" crossorigin="anonymous"></script>

    <script>
        // Elements
        const video = document.getElementById('video');
        const overlay = document.getElementById('overlay');
        const ctx = overlay.getContext('2d');
        const faceGuide = document.getElementById('faceGuide');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const validationPanel = document.getElementById('validationPanel');
        const resultPanel = document.getElementById('resultPanel');

        // Buttons
        const btnStart = document.getElementById('btnStart');
        const btnVerify = document.getElementById('btnVerify');
        const btnStop = document.getElementById('btnStop');
        const btnRetry = document.getElementById('btnRetry');

        // State
        let stream = null;
        let faceDetection = null;
        let animationId = null;
        let lastValidation = null;
        let isValidating = false;

        // Validation thresholds
        const THRESHOLDS = {
            minFaceRatio: 0.20, // Minimum face width as ratio of frame
            maxFaceRatio: 0.60, // Maximum face width as ratio of frame
            idealFaceRatio: 0.35, // Ideal face size
            maxOffsetX: 0.15, // Maximum horizontal offset from center
            maxOffsetY: 0.15, // Maximum vertical offset from center
            minBrightness: 80, // Minimum average brightness (0-255)
            maxBrightness: 200, // Maximum brightness (overexposed)
            idealBrightness: 140, // Ideal brightness
            maxYawAngle: 20, // Maximum face yaw angle
        };

        // =========================================
        // MediaPipe Setup
        // =========================================

        function initMediaPipe() {
            faceDetection = new FaceDetection({
                locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/face_detection/${file}`
            });

            faceDetection.setOptions({
                model: 'short',
                minDetectionConfidence: 0.5
            });

            faceDetection.onResults(onFaceDetectionResults);
        }

        // =========================================
        // Validation Logic
        // =========================================

        function validateFace(detection, frameWidth, frameHeight, brightness) {
            const result = {
                faceDetected: false,
                distance: {
                    ok: false,
                    value: 0,
                    message: ''
                },
                position: {
                    ok: false,
                    message: ''
                },
                lighting: {
                    ok: false,
                    value: 0,
                    message: ''
                },
                angle: {
                    ok: false,
                    message: ''
                },
                allPassed: false
            };

            if (!detection) {
                result.distance.message = 'Tidak ada wajah';
                result.position.message = 'Tidak ada wajah';
                result.lighting.message = 'Tidak ada wajah';
                result.angle.message = 'Tidak ada wajah';
                return result;
            }

            result.faceDetected = true;
            const bbox = detection.boundingBox;

            // 1. Face Distance (size)
            // MediaPipe bbox.width is already normalized (0-1), so use it directly
            const faceRatio = bbox.width; // This is already the ratio of face width to frame width
            result.distance.value = faceRatio;

            if (faceRatio < THRESHOLDS.minFaceRatio) {
                result.distance.ok = false;
                result.distance.message = `Terlalu jauh (${(faceRatio*100).toFixed(0)}%)`;
            } else if (faceRatio > THRESHOLDS.maxFaceRatio) {
                result.distance.ok = false;
                result.distance.message = `Terlalu dekat (${(faceRatio*100).toFixed(0)}%)`;
            } else {
                result.distance.ok = true;
                result.distance.message = `Ideal (${(faceRatio*100).toFixed(0)}%)`;
            }

            // 2. Face Position (centered)
            // xCenter and yCenter are normalized (0-1), 0.5 = center
            const faceCenterX = bbox.xCenter; // 0-1, 0.5 = center
            const faceCenterY = bbox.yCenter; // 0-1, 0.5 = center  
            const offsetX = Math.abs(faceCenterX - 0.5);
            const offsetY = Math.abs(faceCenterY - 0.5);

            if (offsetX > THRESHOLDS.maxOffsetX || offsetY > THRESHOLDS.maxOffsetY) {
                result.position.ok = false;
                // Note: video is mirrored, so left/right directions are swapped
                if (offsetX > offsetY) {
                    result.position.message = faceCenterX < 0.5 ? 'Geser ke kiri' : 'Geser ke kanan';
                } else {
                    result.position.message = faceCenterY < 0.5 ? 'Geser ke bawah' : 'Geser ke atas';
                }
            } else {
                result.position.ok = true;
                result.position.message = 'Posisi tepat di tengah';
            }

            // 3. Lighting
            result.lighting.value = brightness;

            if (brightness < THRESHOLDS.minBrightness) {
                result.lighting.ok = false;
                result.lighting.message = `Terlalu gelap (${brightness.toFixed(0)})`;
            } else if (brightness > THRESHOLDS.maxBrightness) {
                result.lighting.ok = false;
                result.lighting.message = `Terlalu terang (${brightness.toFixed(0)})`;
            } else {
                result.lighting.ok = true;
                result.lighting.message = `Pencahayaan baik (${brightness.toFixed(0)})`;
            }

            // 4. Face Angle (using keypoints if available)
            if (detection.landmarks && detection.landmarks.length >= 2) {
                const leftEye = detection.landmarks[0];
                const rightEye = detection.landmarks[1];
                const eyeAngle = Math.atan2(rightEye.y - leftEye.y, rightEye.x - leftEye.x) * (180 / Math.PI);

                if (Math.abs(eyeAngle) > THRESHOLDS.maxYawAngle) {
                    result.angle.ok = false;
                    result.angle.message = `Miringkan kepala (${eyeAngle.toFixed(0)}°)`;
                } else {
                    result.angle.ok = true;
                    result.angle.message = `Sudut baik (${eyeAngle.toFixed(0)}°)`;
                }
            } else {
                result.angle.ok = true;
                result.angle.message = 'Sudut baik';
            }

            // Overall
            result.allPassed = result.distance.ok && result.position.ok &&
                result.lighting.ok && result.angle.ok;

            return result;
        }

        function calculateBrightness(video, canvas) {
            const tempCanvas = document.createElement('canvas');
            const tempCtx = tempCanvas.getContext('2d');
            tempCanvas.width = 100; // Sample at low resolution for performance
            tempCanvas.height = 75;
            tempCtx.drawImage(video, 0, 0, 100, 75);

            const imageData = tempCtx.getImageData(0, 0, 100, 75);
            const data = imageData.data;
            let sum = 0;

            for (let i = 0; i < data.length; i += 4) {
                // Luminance formula
                sum += (data[i] * 0.299 + data[i + 1] * 0.587 + data[i + 2] * 0.114);
            }

            return sum / (data.length / 4);
        }

        // =========================================
        // UI Updates
        // =========================================

        function updateValidationUI(validation) {
            // Face Detection
            setValidationItem('Face',
                validation.faceDetected ? 'ok' : 'error',
                validation.faceDetected ? '✓' : '✗',
                validation.faceDetected ? 'Wajah terdeteksi' : 'Tidak ada wajah'
            );

            // Distance
            const distPct = Math.min(100, (validation.distance.value / THRESHOLDS.maxFaceRatio) * 100);
            setValidationItem('Distance',
                validation.distance.ok ? 'ok' : 'warning',
                validation.distance.ok ? '✓' : '⚠',
                validation.distance.message
            );
            document.getElementById('barDistance').style.width = `${distPct}%`;
            document.getElementById('barDistance').className =
                `progress-fill ${validation.distance.ok ? 'ok' : 'warning'}`;

            // Position
            setValidationItem('Position',
                validation.position.ok ? 'ok' : 'warning',
                validation.position.ok ? '✓' : '↔',
                validation.position.message
            );

            // Lighting
            const lightPct = Math.min(100, (validation.lighting.value / 255) * 100);
            setValidationItem('Light',
                validation.lighting.ok ? 'ok' : 'warning',
                validation.lighting.ok ? '✓' : '💡',
                validation.lighting.message
            );
            document.getElementById('barLight').style.width = `${lightPct}%`;
            document.getElementById('barLight').className =
                `progress-fill ${validation.lighting.ok ? 'ok' : 'warning'}`;

            // Angle
            setValidationItem('Angle',
                validation.angle.ok ? 'ok' : 'warning',
                validation.angle.ok ? '✓' : '↺',
                validation.angle.message
            );

            // Overall
            setValidationItem('Overall',
                validation.allPassed ? 'ok' : 'warning',
                validation.allPassed ? '✓' : '⏳',
                validation.allPassed ? 'Siap untuk verifikasi!' : 'Perbaiki kondisi di atas'
            );

            // Update face guide
            faceGuide.className = 'face-guide ' + (
                validation.faceDetected ?
                (validation.allPassed ? 'detected' : 'warning') :
                ''
            );

            // Update verify button
            btnVerify.disabled = !validation.allPassed;

            // Update status badge
            const badge = document.getElementById('statusBadge');
            if (validation.allPassed) {
                badge.textContent = 'Siap';
                badge.className = 'status-badge ready';
            } else if (validation.faceDetected) {
                badge.textContent = 'Perbaiki';
                badge.className = 'status-badge waiting';
            } else {
                badge.textContent = 'Mencari';
                badge.className = 'status-badge error';
            }
        }

        function setValidationItem(name, status, icon, value) {
            document.getElementById(`icon${name}`).className = `validation-icon ${status}`;
            document.getElementById(`icon${name}`).textContent = icon;
            document.getElementById(`value${name}`).textContent = value;
        }

        // =========================================
        // Face Detection Results
        // =========================================

        function onFaceDetectionResults(results) {
            if (!video.videoWidth) return;

            overlay.width = video.clientWidth;
            overlay.height = video.clientHeight;
            ctx.clearRect(0, 0, overlay.width, overlay.height);

            const brightness = calculateBrightness(video, overlay);
            const detection = results.detections[0] || null;

            // Draw face bbox
            if (detection) {
                const bbox = detection.boundingBox;
                const x = bbox.xCenter * overlay.width - (bbox.width * overlay.width / 2);
                const y = bbox.yCenter * overlay.height - (bbox.height * overlay.height / 2);
                const w = bbox.width * overlay.width;
                const h = bbox.height * overlay.height;

                ctx.strokeStyle = '#10b981';
                ctx.lineWidth = 3;
                ctx.strokeRect(x, y, w, h);

                // Draw landmarks
                if (detection.landmarks) {
                    ctx.fillStyle = '#6366f1';
                    detection.landmarks.forEach(point => {
                        ctx.beginPath();
                        ctx.arc(point.x * overlay.width, point.y * overlay.height, 4, 0, 2 * Math.PI);
                        ctx.fill();
                    });
                }
            }

            // Validate
            lastValidation = validateFace(detection, video.videoWidth, video.videoHeight, brightness);
            updateValidationUI(lastValidation);
        }

        // =========================================
        // Camera Functions
        // =========================================

        async function startCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: 640,
                        height: 480,
                        facingMode: 'user'
                    }
                });
                video.srcObject = stream;
                await video.play();

                initMediaPipe();

                // Start detection loop
                const camera = new Camera(video, {
                    onFrame: async () => {
                        await faceDetection.send({
                            image: video
                        });
                    },
                    width: 640,
                    height: 480
                });
                camera.start();

                btnStart.disabled = true;
                btnStop.disabled = false;

                // Show validation panel, hide result panel
                validationPanel.style.display = 'block';
                resultPanel.classList.remove('show');

            } catch (err) {
                alert('Error accessing camera: ' + err.message);
            }
        }

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(t => t.stop());
                stream = null;
            }
            if (animationId) {
                cancelAnimationFrame(animationId);
            }

            ctx.clearRect(0, 0, overlay.width, overlay.height);

            btnStart.disabled = false;
            btnVerify.disabled = true;
            btnStop.disabled = true;

            document.getElementById('statusBadge').textContent = 'Menunggu';
            document.getElementById('statusBadge').className = 'status-badge waiting';
        }

        // =========================================
        // Verification
        // =========================================

        async function verify() {
            if (!lastValidation || !lastValidation.allPassed) {
                alert('Validasi belum terpenuhi!');
                return;
            }

            // Show loading
            loadingOverlay.classList.add('show');
            btnVerify.disabled = true;

            // Capture frame
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            const blob = await new Promise(resolve => {
                canvas.toBlob(resolve, 'image/jpeg', 0.9);
            });

            // Get config
            const apiUrl = document.getElementById('apiUrl').value.replace(/\/$/, '');
            const tenantId = document.getElementById('tenantId').value;
            const userId = document.getElementById('userId').value;
            const threshold = document.getElementById('threshold').value;

            // Send to backend
            const formData = new FormData();
            formData.append('tenant_id', tenantId);
            formData.append('user_id', userId);
            formData.append('threshold', threshold);
            formData.append('file', blob, 'capture.jpg');

            try {
                const response = await fetch(`${apiUrl}/verify`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                // Hide loading
                loadingOverlay.classList.remove('show');

                // Show result
                showResult(data);

            } catch (err) {
                loadingOverlay.classList.remove('show');
                showResult({
                    success: false,
                    verified: false,
                    message: 'Error: ' + err.message
                });
            }
        }

        function showResult(data) {
            validationPanel.style.display = 'none';
            resultPanel.classList.add('show');

            const icon = document.getElementById('resultIcon');
            const title = document.getElementById('resultTitle');
            const message = document.getElementById('resultMessage');

            if (data.verified) {
                icon.textContent = '✓';
                icon.className = 'result-icon success';
                title.textContent = 'Verifikasi Berhasil';
                title.style.color = 'var(--success)';
                message.textContent = `Halo, ${data.user_name}! (distance: ${data.distance?.toFixed(4)})`;
            } else {
                icon.textContent = '✗';
                icon.className = 'result-icon error';
                title.textContent = 'Verifikasi Gagal';
                title.style.color = 'var(--danger)';
                message.textContent = data.message || 'Wajah tidak cocok';
            }
        }

        function retry() {
            resultPanel.classList.remove('show');
            validationPanel.style.display = 'block';
            btnVerify.disabled = !lastValidation?.allPassed;
        }

        // =========================================
        // Event Listeners
        // =========================================

        btnStart.onclick = startCamera;
        btnStop.onclick = stopCamera;
        btnVerify.onclick = verify;
        btnRetry.onclick = retry;
    </script>
</body>

</html>
