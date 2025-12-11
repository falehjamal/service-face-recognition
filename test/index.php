<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Face Recognition Client</title>
  <link rel="stylesheet" href="assets/style.css" />
  <script src="assets/jquery.js"></script>
</head>
<body>
  <div class="container">
    <header>
      <h1>Face Recognition Client</h1>
      <p>Enroll foto lalu uji identifikasi via kamera.</p>
    </header>

    <section class="card">
      <label class="field-label" for="imageFile">Gambar</label>
      <input type="file" id="imageFile" accept="image/*" />

      <label class="field-label" for="enrollName">Nama untuk enrollment</label>
      <input type="text" id="enrollName" placeholder="misal: Alice" />

      <div class="button-row">
        <button id="enrollBtn">Enroll</button>
        <button id="identifyBtn">Identify (kamera)</button>
      </div>
    </section>

    <section class="card">
      <div class="result-header">Camera Preview</div>
      <div class="camera-row">
        <div class="video-wrap">
          <video id="camera" autoplay playsinline muted></video>
          <canvas id="overlayCanvas" class="overlay"></canvas>
        </div>
        <canvas id="captureCanvas" width="640" height="480" hidden></canvas>
      </div>
      <p class="hint">Jika kamera tidak menyala, cek izin browser.</p>
      <div class="button-row">
        <button id="startDetectBtn" class="secondary">Start Detect</button>
        <button id="stopDetectBtn" class="secondary">Stop Detect</button>
      </div>
    </section>

    <section class="card">
      <div class="result-header">Hasil</div>
      <pre id="result">{ "result": "menunggu request" }</pre>
    </section>
  </div>

  <script>
    $(function () {
      const endpoints = {
        enroll: "http://localhost:8000/enroll",
        identify: "http://localhost:8000/identify",
      };

      const overlayCanvas = document.getElementById("overlayCanvas");
      const overlayCtx = overlayCanvas.getContext("2d");

      const setResult = (payload) => {
        const formatted = typeof payload === "string" ? payload : JSON.stringify(payload, null, 2);
        $("#result").text(formatted);
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

      const requireFile = () => {
        const file = document.getElementById("imageFile").files[0];
        if (!file) {
          alert("Pilih file gambar terlebih dahulu.");
          return null;
        }
        return file;
      };

      const syncOverlaySize = () => {
        const video = document.getElementById("camera");
        if (!video.videoWidth) return { scaleX: 1, scaleY: 1 };
        const displayWidth = video.clientWidth || video.videoWidth;
        const displayHeight = video.clientHeight || video.videoHeight;
        overlayCanvas.width = displayWidth;
        overlayCanvas.height = displayHeight;
        return {
          scaleX: displayWidth / video.videoWidth,
          scaleY: displayHeight / video.videoHeight,
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
        const video = document.getElementById("camera");
        if (!video.videoWidth) return;

        const { scaleX, scaleY } = syncOverlaySize();
        const left = bbox.left * scaleX;
        const top = bbox.top * scaleY;
        const width = (bbox.right - bbox.left) * scaleX;
        const height = (bbox.bottom - bbox.top) * scaleY;

        clearOverlay();
        overlayCtx.strokeStyle = data.match ? "#22c55e" : "#f97316";
        overlayCtx.lineWidth = 3;
        overlayCtx.strokeRect(left, top, width, height);

        const label = data.match && data.name ? data.name : data.name || "Unknown";
        const scoreText = typeof bbox.det_score === "number" ? ` · ${bbox.det_score.toFixed(2)}` : "";
        const text = `${label}${scoreText}`;
        const padding = 6;
        const textY = Math.max(0, top - 22);

        overlayCtx.font = "16px Arial";
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

      $("#enrollBtn").on("click", () => {
        const file = requireFile();
        if (!file) return;
        const name = $("#enrollName").val().trim();
        if (!name) {
          alert("Isi nama untuk enrollment.");
          return;
        }
        const fd = new FormData();
        fd.append("name", name);
        fd.append("file", file);
        ajaxSend(endpoints.enroll, fd, "enroll");
      });

      const getCameraStream = async () => {
        try {
          const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
          const video = document.getElementById("camera");
          video.srcObject = stream;
          video.onloadedmetadata = () => {
            syncOverlaySize();
            clearOverlay();
          };
        } catch (err) {
          console.warn("Camera not available", err);
          setResult({ status: "camera-error", error: err.message });
        }
      };

      const captureFrame = () => {
        const video = document.getElementById("camera");
        const canvas = document.getElementById("captureCanvas");
        if (!video.videoWidth) {
          alert("Kamera belum siap.");
          return null;
        }
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext("2d");
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        return new Promise((resolve) => {
          canvas.toBlob((blob) => resolve(blob), "image/jpeg", 0.9);
        });
      };

      $("#identifyBtn").on("click", async () => {
        const blob = await captureFrame();
        if (!blob) return;
        const fd = new FormData();
        fd.append("file", new File([blob], "capture.jpg", { type: "image/jpeg" }));
        ajaxSend(endpoints.identify, fd, "identify", drawOverlayFromResponse);
      });

      // Real-time loop
      let detectTimer = null;
      const startLoop = () => {
        if (detectTimer) return;
        const loop = async () => {
          const blob = await captureFrame();
          if (!blob) {
            detectTimer = null;
            return;
          }
          const fd = new FormData();
          fd.append("file", new File([blob], "capture.jpg", { type: "image/jpeg" }));
          ajaxSend(endpoints.identify, fd, "identify-live", drawOverlayFromResponse);
        };
        detectTimer = setInterval(loop, 1500); // every 1.5s to avoid overload
        setResult({ status: "live-detect-started" });
      };

      const stopLoop = () => {
        if (detectTimer) {
          clearInterval(detectTimer);
          detectTimer = null;
          setResult({ status: "live-detect-stopped" });
        }
        clearOverlay();
      };

      $("#startDetectBtn").on("click", startLoop);
      $("#stopDetectBtn").on("click", stopLoop);

      $(window).on("resize", syncOverlaySize);
      getCameraStream();
    });
  </script>
</body>
</html>
