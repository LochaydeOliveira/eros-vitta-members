-- Script para verificar se o usuário foi criado corretamente
-- Execute este script para diagnosticar o problema

-- 1. Verificar se o usuário existe
SELECT 
    id,
    email,
    nome,
    senha,
    criado_em
FROM users 
WHERE email = 'lochaydeguerreiro@hotmail.com';

-- 2. Verificar se há materiais liberados
SELECT 
    u.email,
    m.titulo,
    m.tipo,
    um.liberado_em
FROM users u
JOIN user_materials um ON u.id = um.user_id
JOIN materials m ON um.material_id = m.id
WHERE u.email = 'lochaydeguerreiro@hotmail.com';

-- 3. Verificar total de usuários no sistema
SELECT COUNT(*) as total_usuarios FROM users;

-- 4. Verificar se a senha está correta (hash)
-- A senha '12345' deve gerar este hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
SELECT 
    email,
    senha,
    CASE 
        WHEN senha = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
        THEN 'SENHA CORRETA' 
        ELSE 'SENHA INCORRETA' 
    END as status_senha
FROM users 
WHERE email = 'lochaydeguerreiro@hotmail.com';
