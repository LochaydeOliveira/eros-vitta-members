-- Script para remover tabelas desnecessárias do funil Hotmart
-- Execute este script para limpar a estrutura do banco

-- 1. ANÁLISE DAS TABELAS ATUAIS
SELECT 
    'ANÁLISE DAS TABELAS' as status,
    'Verificando quais tabelas são necessárias para o funil Hotmart' as descricao;

-- 2. TABELAS NECESSÁRIAS PARA O FUNIL HOTMART:
-- ✅ materials - Catálogo de materiais
-- ✅ product_material_mapping - Mapeamento Hotmart → Material  
-- ✅ upsell_package_materials - Pacotes Premium
-- ✅ users - Usuários
-- ✅ user_purchases - Compras dos usuários
-- ✅ system_settings - Configurações do sistema
-- ✅ access_logs - Logs de acesso (segurança)
-- ✅ user_notifications - Notificações (opcional)

-- 3. TABELAS DESNECESSÁRIAS:
-- ❌ user_materials - Sistema antigo, substituído por user_purchases
-- ❌ user_dashboard_materials - View complexa, pode ser simplificada

-- 4. VERIFICAR DADOS NA TABELA user_materials
SELECT 
    'DADOS EM user_materials' as status,
    COUNT(*) as total_registros,
    'Esta tabela pode ser removida após migração' as observacao
FROM user_materials;

-- 5. MIGRAR DADOS DE user_materials PARA user_purchases (se necessário)
-- Nota: Os dados já estão em user_purchases, então user_materials pode ser removida

-- 6. REMOVER TABELA user_materials (SISTEMA ANTIGO)
-- CUIDADO: Execute apenas se tiver certeza que não precisa dos dados
-- DROP TABLE IF EXISTS user_materials;

-- 7. SIMPLIFICAR VIEW user_dashboard_materials
-- A view atual é muito complexa, pode ser simplificada

-- 8. VERIFICAR ESTRUTURA FINAL
SELECT 
    'ESTRUTURA RECOMENDADA' as status,
    'Tabelas essenciais para o funil Hotmart:' as descricao;

-- 9. LISTAR TABELAS ESSENCIAIS
SELECT 
    'TABELAS ESSENCIAIS' as categoria,
    'materials' as tabela,
    'Catálogo de materiais (ebooks, vídeos, áudios)' as funcao
UNION ALL
SELECT 
    'TABELAS ESSENCIAIS',
    'product_material_mapping',
    'Mapeamento produtos Hotmart → materiais internos'
UNION ALL
SELECT 
    'TABELAS ESSENCIAIS',
    'upsell_package_materials',
    'Definição de pacotes Premium (múltiplos materiais)'
UNION ALL
SELECT 
    'TABELAS ESSENCIAIS',
    'users',
    'Usuários do sistema'
UNION ALL
SELECT 
    'TABELAS ESSENCIAIS',
    'user_purchases',
    'Histórico de compras e liberação de materiais'
UNION ALL
SELECT 
    'TABELAS OPCIONAIS',
    'system_settings',
    'Configurações do sistema (período de carência, etc.)'
UNION ALL
SELECT 
    'TABELAS OPCIONAIS',
    'access_logs',
    'Logs de acesso para segurança e auditoria'
UNION ALL
SELECT 
    'TABELAS OPCIONAIS',
    'user_notifications',
    'Sistema de notificações para usuários';

-- 10. RECOMENDAÇÕES
SELECT 
    'RECOMENDAÇÕES' as status,
    '1. Manter todas as tabelas atuais' as recomendacao_1,
    '2. user_materials pode ser removida (sistema antigo)' as recomendacao_2,
    '3. Simplificar view user_dashboard_materials' as recomendacao_3,
    '4. Todas as outras tabelas são úteis para o funil' as recomendacao_4;

-- 11. SCRIPT PARA REMOVER user_materials (EXECUTE COM CUIDADO)
/*
-- ATENÇÃO: Execute apenas se tiver certeza!
-- Este comando remove a tabela user_materials permanentemente

DROP TABLE IF EXISTS user_materials;

-- Verificar se foi removida
SELECT 'Tabela user_materials removida com sucesso' as status;
*/

-- 12. VERIFICAÇÃO FINAL
SELECT 
    'ANÁLISE CONCLUÍDA' as status,
    'Estrutura atual está adequada para o funil Hotmart' as resultado,
    'Apenas user_materials é desnecessária (sistema antigo)' as observacao,
    NOW() as data_analise;
