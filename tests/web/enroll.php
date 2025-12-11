<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Enrollment | Face Recognition</title>
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
      <div class="eyebrow">Enrollment Console</div>
      <h1>Kelola Enrollment Wajah</h1>
      <p>Tambah wajah baru dan lihat daftar enrollment yang tersimpan di database.</p>
      <div class="api-row">
        <label for="apiBase" class="field-label">API base URL</label>
        <input type="text" id="apiBase" value="http://localhost:8000" />
      </div>
      <div class="status-bar">
        <span class="pill muted" id="uploadStatus">Siap upload</span>
        <span class="pill muted" id="listStatus">Memuat daftar…</span>
        <a href="index.php" class="pill ghost" style="text-decoration:none;">Ke Live Identify</a>
      </div>
    </header>

    <section class="card">
      <div class="card-head">
        <div>
          <p class="badge">Tambah Enrollment</p>
          <h2>Upload foto + nama</h2>
          <p class="sub">Gunakan foto dengan wajah jelas. Model: ArcFace 512-d.</p>
        </div>
      </div>

      <div class="field-grid">
        <div>
          <label class="field-label" for="imageFile">Gambar</label>
          <input type="file" id="imageFile" accept="image/*" />
          <p class="micro">JPEG/PNG, wajah jelas, satu orang per foto.</p>
        </div>
        <div>
          <label class="field-label" for="enrollName">Nama</label>
          <input type="text" id="enrollName" placeholder="misal: Alice" />
          <p class="micro">Nama ini muncul ketika terdeteksi.</p>
        </div>
      </div>

      <div class="button-row">
        <button id="enrollBtn">Simpan Enrollment</button>
        <button id="refreshBtn" class="ghost">Refresh daftar</button>
      </div>
    </section>

    <section class="card">
      <div class="card-head">
        <div>
          <p class="badge">Daftar Enrollment</p>
          <h2>Data tersimpan</h2>
          <p class="sub">Diambil dari database backend.</p>
        </div>
      </div>
      <div class="taglist" id="enrollmentList">
        <span class="tag">Memuat…</span>
      </div>
    </section>

    <section class="card">
      <div class="card-head">
        <div>
          <p class="badge">Log</p>
          <h2>Respons</h2>
          <p class="sub">Hasil upload / daftar akan tampil di sini.</p>
        </div>
      </div>
      <pre id="result">{ "result": "menunggu request" }</pre>
    </section>
  </div>

  <script>
    $(function () {
      const uploadChip = document.getElementById('uploadStatus');
      const listChip = document.getElementById('listStatus');

      const setResult = (payload) => {
        const formatted = typeof payload === 'string' ? payload : JSON.stringify(payload, null, 2);
        $('#result').text(formatted);
      };

      const updateChip = (el, text, tone) => {
        el.textContent = text;
        el.className = `pill ${tone}`;
      };

      const makeEndpoints = () => {
        const base = document.getElementById('apiBase').value.replace(/\/$/, '');
        return {
          enroll: `${base}/enroll`,
          enrollments: `${base}/enrollments`,
        };
      };

      const requireFile = () => {
        const file = document.getElementById('imageFile').files[0];
        if (!file) {
          alert('Pilih file gambar terlebih dahulu.');
          return null;
        }
        return file;
      };

      const loadEnrollments = () => {
        const { enrollments } = makeEndpoints();
        updateChip(listChip, 'Memuat daftar…', 'muted');
        $.get(enrollments)
          .done((data) => {
            const names = (data.enrollments || []).map((e) => e.name);
            const html = names.length
              ? names.map((n) => `<span class="tag">${n}</span>`).join(' ')
              : '<span class="tag">Belum ada enrollment</span>';
            $('#enrollmentList').html(html);
            updateChip(listChip, `Enrollments: ${names.length}`, 'success');
          })
          .fail(() => {
            $('#enrollmentList').html('<span class="tag">Gagal memuat</span>');
            updateChip(listChip, 'Gagal memuat', 'danger');
          });
      };

      const ajaxSend = (url, formData, action, onSuccess) => {
        setResult({ status: 'loading', action });
        $.ajax({
          url,
          method: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          success: (data) => {
            setResult(data);
            if (onSuccess) onSuccess(data);
          },
          error: (jqXHR) => {
            let body = jqXHR.responseText;
            try { body = JSON.parse(body); } catch (_) {}
            setResult({ status: jqXHR.status, error: body });
          },
        });
      };

      $('#enrollBtn').on('click', () => {
        const file = requireFile();
        if (!file) return;
        const name = $('#enrollName').val().trim();
        if (!name) {
          alert('Isi nama untuk enrollment.');
          return;
        }
        const fd = new FormData();
        fd.append('name', name);
        fd.append('file', file);
        const { enroll } = makeEndpoints();
        updateChip(uploadChip, 'Uploading…', 'accent');
        ajaxSend(enroll, fd, 'enroll', () => {
          updateChip(uploadChip, 'Upload berhasil', 'success');
          loadEnrollments();
        });
      });

      $('#refreshBtn').on('click', loadEnrollments);
      loadEnrollments();
    });
  </script>
</body>
</html>
