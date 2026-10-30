<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body { margin: 24px; background: #f4f7f2; color: #0b3d3e; font-family: Arial, sans-serif; }
        h1 { font-size: 20px; }
        canvas { display: block; max-width: 100%; height: auto; margin-top: 20px; background: white; }
        a { display: inline-block; background: #315f31; color: white; padding: 12px 18px; border-radius: 6px; text-decoration: none; }
        a[hidden] { display: none; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p id="export-status" role="status">Menyiapkan gambar JPG…</p>
    <a id="download" hidden>Download JPG</a>
    <canvas id="preview" aria-label="Pratinjau denah siteplan"></canvas>
    <script>
        (async function () {
            const status = document.getElementById('export-status');
            let svgUrl;
            try {
                const svg = {!! \Illuminate\Support\Js::from($svg) !!};
                const width = {{ $width }};
                const height = {{ $height }};
                const scale = Math.min(4000 / Math.max(width, height), Math.sqrt(16000000 / (width * height)));
                const canvas = document.getElementById('preview');
                canvas.width = Math.max(1, Math.round(width * scale));
                canvas.height = Math.max(1, Math.round(height * scale));
                const image = new Image();
                svgUrl = URL.createObjectURL(new Blob([svg], { type: 'image/svg+xml;charset=utf-8' }));
                await new Promise((resolve, reject) => {
                    image.onload = resolve;
                    image.onerror = () => reject(new Error('Denah tidak dapat dibaca.'));
                    image.src = svgUrl;
                });
                const context = canvas.getContext('2d');
                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, canvas.width, canvas.height);
                context.drawImage(image, 0, 0, canvas.width, canvas.height);
                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.95));
                if (!blob) throw new Error('Gambar JPG gagal dibuat.');
                const download = document.getElementById('download');
                const jpgUrl = URL.createObjectURL(blob);
                download.href = jpgUrl;
                download.download = {!! \Illuminate\Support\Js::from($filename . '.jpg') !!};
                download.hidden = false;
                download.click();
                status.textContent = 'JPG siap. Jika unduhan belum dimulai, klik Download JPG.';
                window.addEventListener('pagehide', () => URL.revokeObjectURL(jpgUrl), { once: true });
            } catch (error) {
                status.textContent = 'Gagal membuat JPG. Silakan muat ulang halaman. ' + error.message;
            } finally {
                if (svgUrl) URL.revokeObjectURL(svgUrl);
            }
        })();
    </script>
</body>
</html>
