<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ukuran unggahan terlalu besar</title>
    <style>
        *{box-sizing:border-box}body{margin:0;padding:24px;background:#edf8f0;color:#24392b;font:16px/1.6 "Plus Jakarta Sans",system-ui,sans-serif}.card{width:min(100%,620px);margin:8vh auto;padding:clamp(22px,5vw,36px);border:1px solid #dce8de;border-radius:16px;background:#fff;box-shadow:0 10px 35px #1b452b12}h1{margin:0 0 12px;color:#963d35;font-size:clamp(1.35rem,4vw,1.8rem)}p{margin:0 0 12px}.hint{color:#607166}.back{display:inline-flex;min-height:44px;align-items:center;justify-content:center;margin-top:10px;padding:10px 16px;border-radius:9px;background:#65bd89;color:#203b28;font-weight:700;text-decoration:none}
    </style>
</head>
<body><main class="card">
    <h1>Ukuran data melebihi batas server</h1>
    <p>Perubahan belum disimpan. Batas total unggahan server saat ini adalah {{ ini_get('post_max_size') }}. Kecilkan ukuran poster, lalu kirim formulir lagi.</p>
    <p class="hint">Isi pengumuman dan nomor contact person yang sebelumnya tersimpan tetap aman. Untuk server lokal, mulai ulang dengan perintah <code>bash tools/serve</code> agar poster hingga 10 MB dapat diunggah.</p>
    <a class="back" href="{{ route('admin.spmb.dashboard') }}" onclick="if(history.length>1){history.back();return false}">Kembali ke formulir SPMB</a>
</main></body>
</html>
