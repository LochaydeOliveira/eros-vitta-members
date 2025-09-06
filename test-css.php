<?php
// Teste para verificar se o CSS está sendo carregado
header('Content-Type: text/css');
echo "/* Teste CSS - " . date('Y-m-d H:i:s') . " */\n";
echo "body { background: #c67b54 !important; color: white !important; }\n";
echo "h1 { font-size: 3rem !important; text-align: center !important; }\n";
?>
