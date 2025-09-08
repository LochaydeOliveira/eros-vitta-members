-- Script para corrigir os caminhos dos arquivos no banco de dados
-- Execute este script para ajustar os caminhos corretos

-- 1. Verificar materiais atuais
SELECT 
    'MATERIAIS ATUAIS' as status,
    id,
    titulo,
    caminho,
    tipo
FROM materials
ORDER BY id;

-- 2. Corrigir caminho do "5 Toques Mágicos" (ID 6)
UPDATE materials 
SET caminho = 'ebooks/o-guia-dos-5-toques-magicos.html'
WHERE id = 6 AND titulo = '5 Toques Mágicos';

-- 3. Corrigir caminho do "Libido Renovada" (ID 7)
UPDATE materials 
SET caminho = 'ebooks/libido-renovada.html'
WHERE id = 7 AND titulo = 'Libido Renovada - O Plano de 21 Dias Para Casais';

-- 4. Corrigir caminho do "Sem Desejo Nunca Mais" (ID 8)
UPDATE materials 
SET caminho = 'ebooks/sem-desejo-nunca-mais.html'
WHERE id = 8 AND titulo = 'Sem Desejo Nunca Mais';

-- 5. Corrigir caminho do "O Segredo da Resistência" (ID 9)
UPDATE materials 
SET caminho = 'ebooks/o-segredo-da-resistencia.html'
WHERE id = 9 AND titulo = 'O Segredo da Resistência';

-- 6. Corrigir caminho da "Versão em Áudio" (ID 10)
UPDATE materials 
SET caminho = 'audios/libido-renovada-o-plano-de-acao-de-21-dias-para-reacender-a-intimidade-e-a-libido.mp3'
WHERE id = 10 AND titulo = 'Versão em Áudio: Libido Renovada - O Plano de 21 Dias Para Casais';

-- 7. Verificar correções aplicadas
SELECT 
    'MATERIAIS CORRIGIDOS' as status,
    id,
    titulo,
    caminho,
    tipo
FROM materials
ORDER BY id;

-- 8. Resumo das correções
SELECT 
    'CORREÇÕES APLICADAS' as status,
    'Caminhos dos arquivos atualizados para corresponder aos arquivos reais na pasta storage' as resultado,
    NOW() as data_correcao;
