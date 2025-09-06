
USE paymen58_eros_vitta;

-- Tabela de usuários
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) UNIQUE NOT NULL,
  senha VARCHAR(255) NOT NULL,
  nome VARCHAR(150),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email)
);

-- Tabela de materiais
CREATE TABLE materials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(150) NOT NULL,
  tipo ENUM('ebook','video','audio') NOT NULL,
  caminho VARCHAR(255) NOT NULL,
  descricao TEXT,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tipo (tipo)
);

-- Tabela de relacionamento usuário-materiais
CREATE TABLE user_materials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  material_id INT NOT NULL,
  liberado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_material (user_id, material_id),
  INDEX idx_user_id (user_id),
  INDEX idx_material_id (material_id)
);

-- Inserir alguns materiais de exemplo
INSERT INTO materials (titulo, tipo, caminho, descricao) VALUES
('Guia Completo de Marketing Digital', 'ebook', 'ebooks/guia-marketing-digital.html', 'Ebook completo sobre estratégias de marketing digital'),
('Curso de Vendas Online', 'video', 'videos/curso-vendas-online.mp4', 'Vídeo-aulas sobre técnicas de vendas online'),
('Meditação para Iniciantes', 'audio', 'audios/meditacao-iniciantes.mp3', 'Áudio guiado para meditação'),
('Ebook de Receitas Saudáveis', 'ebook', 'ebooks/receitas-saudaveis.html', 'Coleção de receitas nutritivas e deliciosas'),
('Tutorial de Fotografia', 'video', 'videos/tutorial-fotografia.mp4', 'Aprenda técnicas profissionais de fotografia');

-- Criar usuário de exemplo para testes
INSERT INTO users (email, senha, nome) VALUES
('teste@exemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Usuário Teste');

-- Liberar materiais para o usuário de teste
INSERT INTO user_materials (user_id, material_id) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5);
