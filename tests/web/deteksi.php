<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Deteksi Realtime</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap"
    rel="stylesheet"
  />
  <link rel="stylesheet" href="assets/style.css" />
  <style>
    .video-wrap {
      position: relative;
      width: 640px;
      height: 480px;
      background: #000;
      border: 1px solid #ccc;
    }

    #camera {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 1;
      object-fit: cover;
    }

    #overlayCanvas {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 2;
      pointer-events: none;
    }
  </style>
  <script src="assets/jquery.js"></script>
</head>
<body>
  <div class="container">
    <header>
      <div class="eyebrow">Prototype Console</div>
      <h1>Deteksi Kamera Realtime</h1>
      <p>Mulai dan hentikan kamera untuk mengirim frame ke endpoint identify.</p>
      <div class="api-row">
        <label for="apiBase" class="field-label">API base URL</label>
        <input type="text" id="apiBase" value="http://localhost:8000" />
      </div>
      <div class="status-bar">
        <span class="pill muted" id="cameraStatus">Camera: standby</span>
        <span class="pill muted" id="liveStatus">Live detect: standby</span>
      </div>
    </header>

    <section class="card">
      <div class="card-head">
        <div>
          <p class="badge">Realtime</p>
          <h2>Kamera + identifikasi</h2>
          <p class="sub">Hanya start dan stop kamera untuk deteksi realtime.</p>
        </div>
      </div>

      <div class="camera-row">
        <div class="video-wrap">
          <video id="camera" autoplay playsinline muted></video>
          <canvas id="overlayCanvas"></canvas>
        </div>
        <canvas id="captureCanvas" width="640" height="480" hidden></canvas>
        <div class="stat-box">
          <div class="stat-tile">Pastikan izin kamera sudah diberikan.</div>
          <div class="stat-tile">Threshold default 0.35 (lebih kecil lebih ketat).</div>
          <div class="stat-tile">Frame dikirim tiap 1.5s saat live mode aktif.</div>
        </div>
      </div>

      <div class="button-row tight">
        <button id="startDetectBtn" class="secondary">Start kamera + deteksi</button>
        <button id="stopDetectBtn" class="ghost">Stop</button>
      </div>
      <div class="field-grid">
        <div>
          <label class="field-label" for="thresholdInput">Threshold identify</label>
          <input type="number" step="0.01" min="0.1" max="1" id="thresholdInput" value="0.35" />
          <p class="micro">Gunakan 0.30-0.40 untuk ArcFace; lebih kecil = lebih ketat.</p>
        </div>
      </div>
    </section>

    <section class="card">
      <div class="card-head">
        <div>
          <p class="badge">Log</p>
          <h2>Respons</h2>
          <p class="sub">Semua respons dari server tampil di sini.</p>
        </div>
      </div>
      <pre id="result">{ "result": "menunggu request" }</pre>
    </section>
  </div>

  <script>
    $(function () {
      const makeEndpoints = () => {
        const base = document.getElementById("apiBase").value.replace(/\/$/, "");
        return {
          identify: `${base}/identify`,
        };
      };

      const overlayCanvas = document.getElementById("overlayCanvas");
      const overlayCtx = overlayCanvas.getContext("2d");
      const cameraChip = document.getElementById("cameraStatus");
      const liveChip = document.getElementById("liveStatus");
      const videoEl = document.getElementById("camera");
      let mediaStream = null;

      const setResult = (payload) => {
        const formatted = typeof payload === "string" ? payload : JSON.stringify(payload, null, 2);
        $("#result").text(formatted);
      };

      const updateChip = (el, text, tone) => {
        el.textContent = text;
        el.className = `pill ${tone}`;
      };

      const showError = (jqXHR) => {
        let body = jqXHR.responseText;
        try {
          body = JSON.parse(body);
        } catch (_) {
          /* keep raw */
        }
        setResult({ status: jqXHR.status, error: body });
      };

      const syncOverlaySize = () => {
        if (!videoEl.videoWidth) return { scaleX: 1, scaleY: 1 };
        const displayWidth = videoEl.clientWidth || videoEl.videoWidth;
        const displayHeight = videoEl.clientHeight || videoEl.videoHeight;
        overlayCanvas.width = displayWidth;
        overlayCanvas.height = displayHeight;
        return {
          scaleX: displayWidth / videoEl.videoWidth,
          scaleY: displayHeight / videoEl.videoHeight,
        };
      };

      const clearOverlay = () => {
        overlayCtx.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
      };

      const drawOverlayFromResponse = (data) => {
        const bbox = data && data.bbox;
        if (!bbox || typeof bbox.left === "undefined") {
          clearOverlay();
          return;
        }
        if (!videoEl.videoWidth) return;

        const { scaleX, scaleY } = syncOverlaySize();
        const left = bbox.left * scaleX;
        const top = bbox.top * scaleY;
        const width = (bbox.right - bbox.left) * scaleX;
        const height = (bbox.bottom - bbox.top) * scaleY;

        clearOverlay();
        overlayCtx.strokeStyle = data.match ? "#22c55e" : "#f97316";
        overlayCtx.lineWidth = 3;
        overlayCtx.strokeRect(left, top, width, height);

        const label = data.match && data.name ? data.name : "Not Found";
        const scoreText = typeof bbox.det_score === "number" ? ` · ${bbox.det_score.toFixed(2)}` : "";
        const text = `${label}${scoreText}`;
        const padding = 6;
        const textY = Math.max(0, top - 22);

        overlayCtx.font = "16px 'Space Grotesk', 'Segoe UI', sans-serif";
        overlayCtx.textBaseline = "top";
        const textWidth = overlayCtx.measureText(text).width;
        overlayCtx.fillStyle = "rgba(15,23,42,0.85)";
        overlayCtx.fillRect(left, textY, textWidth + padding * 2, 22);
        overlayCtx.fillStyle = "#fff";
        overlayCtx.fillText(text, left + padding, textY + 3);
      };

      const ajaxSend = (url, formData, action, onSuccess) => {
        setResult({ status: "loading", action });
        $.ajax({
          url,
          method: "POST",
          data: formData,
          processData: false,
          contentType: false,
          success: (data) => {
            setResult(data);
            if (onSuccess) onSuccess(data);
          },
          error: showError,
        });
      };

      const getCameraStream = async () => {
        if (mediaStream) return mediaStream;
        try {
          mediaStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
          videoEl.srcObject = mediaStream;
          videoEl.onloadedmetadata = async () => {
            try {
              await videoEl.play();
            } catch (e) {
              console.error("Video play error:", e);
            }
            videoEl.style.visibility = "visible";
            videoEl.style.opacity = "1";
            syncOverlaySize();
            clearOverlay();
            updateChip(cameraChip, "Camera: aktif", "success");
          };
          return mediaStream;
        } catch (err) {
          console.warn("Camera not available", err);
          updateChip(cameraChip, "Camera: gagal", "danger");
          setResult({ status: "camera-error", error: err.message });
          return null;
        }
      };

      const stopCamera = () => {
        if (!mediaStream) return;
        mediaStream.getTracks().forEach((t) => t.stop());
        mediaStream = null;
        videoEl.srcObject = null;
        updateChip(cameraChip, "Camera: standby", "muted");
      };

      const captureFrame = () => {
        if (!videoEl.videoWidth) {
          alert("Kamera belum siap.");
          return null;
        }
        const canvas = document.getElementById("captureCanvas");
        canvas.width = videoEl.videoWidth;
        canvas.height = videoEl.videoHeight;
        const ctx = canvas.getContext("2d");
        ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);
        return new Promise((resolve) => {
          canvas.toBlob((blob) => resolve(blob), "image/jpeg", 0.9);
        });
      };

      let detectTimer = null;
      const startLoop = async () => {
        if (detectTimer) return;
        const stream = await getCameraStream();
        if (!stream) return;
        const loop = async () => {
          const blob = await captureFrame();
          if (!blob) {
            stopLoop();
            return;
          }
          const fd = new FormData();
          fd.append("file", new File([blob], "capture.jpg", { type: "image/jpeg" }));
          const threshold = parseFloat(document.getElementById("thresholdInput").value) || 0.35;
          fd.append("threshold", threshold);
          const { identify } = makeEndpoints();
          ajaxSend(identify, fd, "identify-live", (data) => {
            drawOverlayFromResponse(data);
            if (!data.match) {
              setResult({ status: "notfound", distance: data.distance, threshold: data.threshold });
            }
          });
        };
        detectTimer = setInterval(loop, 1500);
        setResult({ status: "live-detect-started" });
        updateChip(liveChip, "Live detect: berjalan", "accent");
      };

      const stopLoop = () => {
        if (detectTimer) {
          clearInterval(detectTimer);
          detectTimer = null;
        }
        stopCamera();
        clearOverlay();
        setResult({ status: "live-detect-stopped" });
        updateChip(liveChip, "Live detect: standby", "muted");
      };

      $("#startDetectBtn").on("click", startLoop);
      $("#stopDetectBtn").on("click", stopLoop);
      $(window).on("resize", syncOverlaySize);
    });
  </script>
</body>
</html>
