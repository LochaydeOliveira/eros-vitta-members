<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Http\JsonResponse;
use App\Mail\Mailer;

final class TestEmailController
{
    public static function send(array $body): void
    {
        $key = (string)($body['key'] ?? '');
        if ($key === '' || $key !== Config::appKey()) {
            JsonResponse::error('Não autorizado', 403);
            return;
        }
        $to = (string)($body['to'] ?? Config::smtpUser());
        if ($to === '') {
            JsonResponse::error('Destinatário inválido', 422);
            return;
        }
        $ok = Mailer::send($to, 'Teste SMTP Zoho - Eros Vitta', '<p>Este é um e-mail de teste do backend Eros Vitta Members.</p><p>Data: ' . date('c') . '</p>');
        if (!$ok) {
            JsonResponse::error('Falha ao enviar e-mail', 500);
            return;
        }
        JsonResponse::ok(['message' => 'Email enviado para ' . $to]);
    }
}
