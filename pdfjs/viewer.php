<?php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';

// Segurança básica: exigir login
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(403);
    echo 'Acesso negado';
    exit;
}

// Parâmetro 'file' é o caminho relativo dentro de storage/pdfs/
$fileParam = $_GET['file'] ?? '';
// Suporta também id do material (ex.: ?id=7)
$materialId = isset($_GET['id']) ? (int) $_GET['id'] : null;

// Resolve o caminho do PDF
if ($materialId) {
    $db = Database::getInstance();
    $user = $auth->getCurrentUser();
    $row = $db->fetch("SELECT m.* FROM materials m LEFT JOIN user_purchases up ON up.material_id = m.id WHERE m.id = ? AND (up.user_id = ? OR ? = ?) LIMIT 1", [$materialId, $user['id'], $user['id'], $user['id']]);
    if (!$row) {
        http_response_code(404);
        echo 'Material não encontrado';
        exit;
    }
    $relative = $row['caminho'];
} else {
    $relative = ltrim($fileParam, '/');
}

// Permitir apenas PDFs e apenas dentro de storage
$filePath = realpath(__DIR__ . '/../storage/' . $relative);
$storageRoot = realpath(__DIR__ . '/../storage');
if (!$filePath || strpos($filePath, $storageRoot) !== 0 || pathinfo($filePath, PATHINFO_EXTENSION) !== 'pdf') {
    http_response_code(400);
    echo 'Arquivo inválido';
    exit;
}

// URL absoluta do arquivo para o PDF.js (usamos um endpoint simples de serve)
$fileUrl = BASE_URL . '/serve-file.php?path=' . urlencode(str_replace($storageRoot . '/', '', $filePath));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Viewer PDF</title>
    <link rel="stylesheet" href="../public/assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        body { background: #f5f5f5; padding: 1rem; }
        .viewer-wrap { max-width: 1100px; margin: 0 auto; }
        .page-indicator { margin-left: .5rem; color: #666; font-family: var(--font-sans); }
    </style>
</head>
<body>
<div class="viewer-wrap">
    <div class="pdf-toolbar">
        <button id="prev" class="btn btn-secondary">Página Anterior</button>
        <button id="next" class="btn btn-secondary">Próxima Página</button>
        <span class="page-indicator"><span id="page_num">1</span> / <span id="page_count">1</span></span>
        <div style="flex:1"></div>
        <button id="zoomOut" class="btn">Zoom -</button>
        <button id="zoomIn" class="btn">Zoom +</button>
    </div>
    <div class="pdf-container">
        <canvas id="the-canvas" class="pdf-canvas"></canvas>
    </div>
    <div id="fallback" class="error-message" style="display:none;">
        <i class="fas fa-exclamation-triangle"></i>
        <p>Não foi possível carregar o PDF.</p>
    </div>
</div>

<!-- PDF.js (usar CDN estável para agilizar; pode ser baixado e servido localmente conforme requisito) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js" integrity="sha512-QdK6rIozcZ4t9Pj7O1mQ9eMWDgR0qTng2fQv7BFzQG7cYzjR1m4o5j6oJ8y8Gg6+3GSK+K2m8e5QwY7rQO8aWw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
  // URL do PDF (vinda do PHP)
  const url = <?php echo json_encode($fileUrl); ?>;

  const loadingTask = pdfjsLib.getDocument(url);
  const canvas = document.getElementById('the-canvas');
  const ctx = canvas.getContext('2d');
  const fallback = document.getElementById('fallback');

  let pdfDoc = null;
  let pageNum = 1;
  let scale = 1.25;

  function renderPage(num) {
    pdfDoc.getPage(num).then(function(page) {
      const viewport = page.getViewport({ scale });
      canvas.height = viewport.height;
      canvas.width = viewport.width;

      const renderContext = {
        canvasContext: ctx,
        viewport: viewport
      };
      page.render(renderContext);

      document.getElementById('page_num').textContent = num;
      document.getElementById('page_count').textContent = pdfDoc.numPages;
    });
  }

  function queueRenderPage(num) { renderPage(num); }

  function onPrevPage() { if (pageNum <= 1) return; pageNum--; queueRenderPage(pageNum); }
  function onNextPage() { if (pageNum >= pdfDoc.numPages) return; pageNum++; queueRenderPage(pageNum); }
  function onZoomIn() { scale = Math.min(scale + 0.1, 3); queueRenderPage(pageNum); }
  function onZoomOut() { scale = Math.max(scale - 0.1, 0.5); queueRenderPage(pageNum); }

  document.getElementById('prev').addEventListener('click', onPrevPage);
  document.getElementById('next').addEventListener('click', onNextPage);
  document.getElementById('zoomIn').addEventListener('click', onZoomIn);
  document.getElementById('zoomOut').addEventListener('click', onZoomOut);

  loadingTask.promise.then(function(pdf) {
    pdfDoc = pdf;
    renderPage(pageNum);
  }).catch(function(err){
    console.error(err);
    fallback.style.display = 'block';
  });
</script>
</body>
</html>


