<?php
declare(strict_types=1);

namespace App;

final class Config
{
    public static function dbDsn(): string
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_NAME') ?: 'paymen58_eros_vitta_members';
        $charset = 'utf8mb4';
        return "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    }

    public static function dbUser(): string
    {
        return getenv('DB_USER') ?: 'DB_USER_AQUI';
    }

    public static function dbPass(): string
    {
        return getenv('DB_PASS') ?: 'DB_PASS_AQUI';
    }

    public static function appKey(): string
    {
        // Chave HS256 para JWT; substitua em produção
        return getenv('APP_KEY') ?: '45c2fea14e8647080a1f1e1fa489e6c9df49a8b36a94cf108550d9848f628ae9';
    }

    public static function appUrl(): string
    {
        return getenv('APP_URL') ?: 'https://erosvitta.com.br';
    }

    // SMTP (Zoho)
    public static function smtpHost(): string { return getenv('SMTP_HOST') ?: 'smtp.zoho.com'; }
    public static function smtpPort(): int { return (int)(getenv('SMTP_PORT') ?: '465'); }
    public static function smtpUser(): string { return getenv('SMTP_USER') ?: 'contato@erosvitta.com.br'; }
    public static function smtpPass(): string { return getenv('SMTP_PASS') ?: 'VwYAkYcxK2PdQ.7'; }
    // ssl (465) ou tls (587)
    public static function smtpSecure(): string { return strtolower(getenv('SMTP_SECURE') ?: 'ssl'); }
    public static function smtpFrom(): string { return getenv('SMTP_FROM') ?: 'contato@erosvitta.com.br'; }
    public static function smtpFromName(): string { return getenv('SMTP_FROM_NAME') ?: 'Eros Vitta'; }

    // Webhooks / Integrações
    public static function hotmartSecret(): string
    {
        return getenv('HOTMART_SECRET') ?: '';
    }

    public static function hotmartHottok(): string
    {
        return getenv('HOTMART_HOTTOK') ?: '';
    }
}
