<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\JsonResponse;
use App\Security\Jwt;
use App\Mail\Mailer;
use PDO;

final class AuthController
{
    /**
     * Registro opcional (principal via webhook). Se email existir, retorna conflito.
     */
    public static function register(array $body): void
    {
        $nome = trim((string)($body['nome'] ?? ''));
        $email = strtolower(trim((string)($body['email'] ?? '')));
        $senha = (string)($body['senha'] ?? '');
        if ($nome === '' || $email === '' || $senha === '') {
            JsonResponse::error('Campos obrigatórios: nome, email, senha', 422);
            return;
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            JsonResponse::error('Email já cadastrado', 409);
            return;
        }
        $hash = password_hash($senha, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, status, criado_em, atualizado_em) VALUES (?,?,?,?,NOW(),NOW())');
        $stmt->execute([$nome, $email, $hash, 'ativo']);
        $userId = (int)$pdo->lastInsertId();
        $token = Jwt::sign(['sub' => $userId, 'email' => $email]);
        JsonResponse::ok(['token' => $token]);
    }

    public static function login(array $body): void
    {
        $email = strtolower(trim((string)($body['email'] ?? '')));
        $senha = (string)($body['senha'] ?? '');
        if ($email === '' || $senha === '') {
            JsonResponse::error('Email e senha são obrigatórios', 422);
            return;
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, senha_hash, status FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['senha_hash']) || !password_verify($senha, (string)$row['senha_hash'])) {
            JsonResponse::error('Credenciais inválidas', 401);
            return;
        }
        if ($row['status'] !== 'ativo') {
            JsonResponse::error('Usuário bloqueado', 403);
            return;
        }
        $userId = (int)$row['id'];
        $pdo->prepare('UPDATE usuarios SET ultimo_login_em = NOW() WHERE id = ?')->execute([$userId]);
        $token = Jwt::sign(['sub' => $userId, 'email' => $email]);
        JsonResponse::ok(['token' => $token]);
    }

    public static function forgotPassword(array $body): void
    {
        $email = strtolower(trim((string)($body['email'] ?? '')));
        if ($email === '') {
            JsonResponse::error('Email é obrigatório', 422);
            return;
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, nome FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            JsonResponse::ok(['message' => 'Se existir, um e-mail será enviado']);
            return;
        }
        $userId = (int)$row['id'];
        $token = bin2hex(random_bytes(32));
        $pdo->prepare('INSERT INTO password_resets (usuario_id, token, expira_em, criado_em) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())')->execute([$userId, $token]);
        $resetUrl = '/reset-password?token=' . urlencode($token);
        $ok = Mailer::send($email, 'Recuperação de Senha', '<p>Olá ' . htmlspecialchars((string)$row['nome']) . ',</p><p>Para redefinir sua senha, acesse: <a href="' . $resetUrl . '">' . $resetUrl . '</a></p>');
        JsonResponse::ok(['message' => $ok ? 'Email de recuperação enviado' : 'Falha ao enviar e-mail, tente novamente']);
    }

    public static function resetPassword(array $body): void
    {
        $token = (string)($body['token'] ?? '');
        $nova = (string)($body['senha'] ?? '');
        if ($token === '' || $nova === '') {
            JsonResponse::error('Token e nova senha são obrigatórios', 422);
            return;
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT pr.id, pr.usuario_id FROM password_resets pr WHERE pr.token = ? AND pr.usado_em IS NULL AND pr.expira_em > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            JsonResponse::error('Token inválido ou expirado', 400);
            return;
        }
        $userId = (int)$row['usuario_id'];
        $hash = password_hash($nova, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE usuarios SET senha_hash = ?, atualizado_em = NOW() WHERE id = ?')->execute([$hash, $userId]);
        $pdo->prepare('UPDATE password_resets SET usado_em = NOW() WHERE id = ?')->execute([(int)$row['id']]);
        JsonResponse::ok(['message' => 'Senha atualizada']);
    }

    public static function me(array $_body, array $req): void
    {
        $userId = (int)($req['user_id'] ?? 0);
        if ($userId <= 0) {
            JsonResponse::error('Não autenticado', 401);
            return;
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, nome, email, status, criado_em FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$user) {
            JsonResponse::error('Usuário não encontrado', 404);
            return;
        }
        JsonResponse::ok([
            'user' => $user,
        ]);
    }
}
