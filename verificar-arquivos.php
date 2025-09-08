<?php
// Script para verificar se os arquivos existem na pasta storage
require_once 'app/config.php';

echo "<h1>🔍 Verificação de Arquivos na Pasta Storage</h1>";

// Lista de arquivos esperados
$arquivos = [
    'ebooks/o-guia-dos-5-toques-magicos.html',
    'ebooks/libido-renovada.html', 
    'ebooks/sem-desejo-nunca-mais.html',
    'ebooks/o-segredo-da-resistencia.html',
    'audios/libido-renovada-o-plano-de-acao-de-21-dias-para-reacender-a-intimidade-e-a-libido.mp3',
    'pdfs/guia-5-toques-magicos.pdf',
    'pdfs/libido-renovada.pdf',
    'pdfs/o-segredo-da-resistencia-o-guia-pratico-para-urar-mais-tempo-na-cama.pdf',
    'pdfs/sem-desejo-nunca-mais.pdf'
];

echo "<h2>📁 Verificação de Arquivos:</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Arquivo</th><th>Status</th><th>Tamanho</th></tr>";

foreach ($arquivos as $arquivo) {
    $caminhoCompleto = STORAGE_PATH . '/' . $arquivo;
    $existe = file_exists($caminhoCompleto);
    $tamanho = $existe ? filesize($caminhoCompleto) : 0;
    
    echo "<tr>";
    echo "<td>" . $arquivo . "</td>";
    echo "<td>" . ($existe ? "✅ Existe" : "❌ Não existe") . "</td>";
    echo "<td>" . ($existe ? number_format($tamanho / 1024, 2) . " KB" : "-") . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>📋 Caminhos Corretos para o Banco:</h2>";
echo "<ul>";
foreach ($arquivos as $arquivo) {
    echo "<li><code>" . $arquivo . "</code></li>";
}
echo "</ul>";

echo "<h2>🔧 Script SQL para Corrigir:</h2>";
echo "<pre>";
echo "-- Corrigir caminhos dos materiais\n";
echo "UPDATE materials SET caminho = 'ebooks/o-guia-dos-5-toques-magicos.html' WHERE id = 6;\n";
echo "UPDATE materials SET caminho = 'ebooks/libido-renovada.html' WHERE id = 7;\n";
echo "UPDATE materials SET caminho = 'ebooks/sem-desejo-nunca-mais.html' WHERE id = 8;\n";
echo "UPDATE materials SET caminho = 'ebooks/o-segredo-da-resistencia.html' WHERE id = 9;\n";
echo "UPDATE materials SET caminho = 'audios/libido-renovada-o-plano-de-acao-de-21-dias-para-reacender-a-intimidade-e-a-libido.mp3' WHERE id = 10;\n";
echo "</pre>";

echo "<p><a href='" . BASE_URL . "/dashboard-simples'>← Voltar ao Dashboard</a></p>";
?>
