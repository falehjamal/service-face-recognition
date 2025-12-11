<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Face Recognition Client</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap"
    rel="stylesheet"
  />
  <link rel="stylesheet" href="assets/style.css" />
  <script src="assets/jquery.js"></script>
</head>
<body>
  <div class="container">
    <header>
      <div class="eyebrow">Prototype Console</div>
      <h1>Realtime Face Recognition</h1>
      <p>Enroll foto sekali, lalu nyalakan deteksi live via kamera.</p>
      <div class="api-row">
        <label for="apiBase" class="field-label">API base URL</label>
        <input type="text" id="apiBase" value="http://localhost:8000" />
      </div>
      <div class="status-bar">
        <span class="pill muted" id="cameraStatus">Camera: initializing…</span>
        <span class="pill muted" id="liveStatus">Live detect: standby</span>
      </div>
    </header>

    <section class="card">
      <div class="card-head">
        <div>
          <p class="badge">Info</p>
          <h2>Identifikasi realtime</h2>
          <p class="sub">Gunakan kamera untuk mengenali wajah berdasarkan data enrollment yang sudah ada.</p>
        </div>
      </div>
      <div class="taglist" id="enrollmentList">
        <span class="tag">Memuat enrollments…</span>
      </div>
      <div class="micro" style="margin-top:8px;">
        Pengelolaan enrollment ada di <a href="enroll.php" style="color:#fbbf24; font-weight:700;">halaman Enrollment</a>.
      </div>
    </section>

    <section class="card">
      <div class="card-head">
        <div>
          <p class="badge">Live Recognize</p>
          <h2>Pratinjau kamera + identifikasi</h2>
          <p class="sub">Kamera menyala di panel kiri; jalankan deteksi untuk menampilkan nama & skor.</p>
        </div>
      </div>

      <div class="camera-row">
        <div class="video-wrap">
          <video id="camera" autoplay playsinline muted></video>
          <canvas id="overlayCanvas" class="overlay"></canvas>
        </div>
        <canvas id="captureCanvas" width="640" height="480" hidden></canvas>
        <div class="stat-box">
          <div class="stat-tile">Pastikan browser mengizinkan kamera (secure origin: http/https localhost).</div>
          <div class="stat-tile">Threshold bawaan 0.35 (semakin kecil semakin ketat).</div>
          <div class="stat-tile">Setiap 1.5s frame dikirim ke /identify selama live mode aktif.</div>
        </div>
      </div>

      <div class="button-row tight">
        <button id="startDetectBtn" class="secondary">Mulai deteksi live</button>
        <button id="stopDetectBtn" class="ghost">Stop</button>
      </div>
      <div class="field-grid">
        <div>
          <label class="field-label" for="thresholdInput">Threshold identify</label>
          <input type="number" step="0.01" min="0.1" max="1" id="thresholdInput" value="0.35" />
          <p class="micro">Gunakan 0.30-0.40 untuk ArcFace; lebih kecil = lebih ketat.</p>
        </div>
      </div>
      <p class="hint">Jika kamera belum tampil, pastikan izin sudah diberikan lalu refresh halaman.</p>
    </section>

    <section class="card">
      <div class="card-head">
        <div>
          <p class="badge">Log</p>
          <h2>Respons</h2>
          <p class="sub">Semua respons dari server akan tampil di sini.</p>
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
          enrollments: `${base}/enrollments`,
        };
      };

      const overlayCanvas = document.getElementById("overlayCanvas");
      const overlayCtx = overlayCanvas.getContext("2d");
      const cameraChip = document.getElementById("cameraStatus");
      const liveChip = document.getElementById("liveStatus");

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
        try {
          const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
          const video = document.getElementById("camera");
          video.srcObject = stream;
          video.onloadedmetadata = () => {
            video.play().catch(() => {
              /* some browsers need explicit play */
            });
            video.style.visibility = "visible";
            video.style.opacity = "1";
            syncOverlaySize();
            clearOverlay();
            updateChip(cameraChip, "Camera: aktif", "success");
          };
        } catch (err) {
          console.warn("Camera not available", err);
          updateChip(cameraChip, "Camera: gagal", "danger");
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

      // Real-time loop
      let detectTimer = null;
      const startLoop = () => {
        if (detectTimer) return;
        const loop = async () => {
          const blob = await captureFrame();
          if (!blob) {
            detectTimer = null;
            updateChip(liveChip, "Live detect: berhenti", "danger");
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
          setResult({ status: "live-detect-stopped" });
        }
        updateChip(liveChip, "Live detect: standby", "muted");
        clearOverlay();
      };

      $("#startDetectBtn").on("click", startLoop);
      $("#stopDetectBtn").on("click", stopLoop);

      const loadEnrollments = () => {
        const { enrollments } = makeEndpoints();
        $.get(enrollments)
          .done((data) => {
            const names = (data.enrollments || []).map((e) => e.name);
            const html = names.length
              ? names.map((n) => `<span class="tag">${n}</span>`).join(" ")
              : '<span class="tag">Belum ada enrollment</span>';
            $("#enrollmentList").html(html);
          })
          .fail(() => $("#enrollmentList").html('<span class="tag">Gagal memuat enrollments</span>'));
      };

      $(window).on("resize", syncOverlaySize);
      getCameraStream();
      loadEnrollments();
    });
  </script>
</body>
</html>
