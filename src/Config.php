<?php
declare(strict_types=1);

namespace App;

final class Config
{
	private static ?array $local = null;

	private static function local(string $key, ?string $default = null): ?string
	{
		if (self::$local === null) {
			$file = __DIR__ . '/../config.local.php';
			if (is_file($file)) {
				/** @var array<string,string> $cfg */
				$cfg = require $file;
				self::$local = is_array($cfg) ? $cfg : [];
			} else {
				self::$local = [];
			}
		}
		return isset(self::$local[$key]) ? (string)self::$local[$key] : $default;
	}

    public static function dbDsn(): string
    {
        $host = self::local('DB_HOST', getenv('DB_HOST') ?: 'localhost');
        $port = self::local('DB_PORT', getenv('DB_PORT') ?: '3306');
        $name = self::local('DB_NAME', getenv('DB_NAME') ?: 'paymen58_eros_vitta_members');
        $charset = 'utf8mb4';
        return "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    }

    public static function dbUser(): string
    {
        return self::local('DB_USER', getenv('DB_USER') ?: 'DB_USER_AQUI') ?: 'DB_USER_AQUI';
    }

    public static function dbPass(): string
    {
        return self::local('DB_PASS', getenv('DB_PASS') ?: 'DB_PASS_AQUI') ?: 'DB_PASS_AQUI';
    }

    public static function appKey(): string
    {
        // Chave HS256 para JWT; substitua em produção
        return self::local('APP_KEY', getenv('APP_KEY') ?: '45c2fea14e8647080a1f1e1fa489e6c9df49a8b36a94cf108550d9848f628ae9') ?: '45c2fea14e8647080a1f1e1fa489e6c9df49a8b36a94cf108550d9848f628ae9';
    }

    public static function appUrl(): string
    {
        return self::local('APP_URL', getenv('APP_URL') ?: 'https://erosvitta.com.br') ?: 'https://erosvitta.com.br';
    }

    // SMTP (Zoho)
    public static function smtpHost(): string { return self::local('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.zoho.com') ?: 'smtp.zoho.com'; }
    public static function smtpPort(): int { return (int)(self::local('SMTP_PORT', getenv('SMTP_PORT') ?: '465') ?: '465'); }
    public static function smtpUser(): string { return self::local('SMTP_USER', getenv('SMTP_USER') ?: 'contato@erosvitta.com.br') ?: 'contato@erosvitta.com.br'; }
    public static function smtpPass(): string { return self::local('SMTP_PASS', getenv('SMTP_PASS') ?: 'VwYAkYcxK2PdQ.7') ?: 'VwYAkYcxK2PdQ.7'; }
    // ssl (465) ou tls (587)
    public static function smtpSecure(): string { return strtolower(self::local('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'ssl') ?: 'ssl'); }
    public static function smtpFrom(): string { return self::local('SMTP_FROM', getenv('SMTP_FROM') ?: 'contato@erosvitta.com.br') ?: 'contato@erosvitta.com.br'; }
    public static function smtpFromName(): string { return self::local('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Eros Vitta') ?: 'Eros Vitta'; }

    // Webhooks / Integrações
    public static function hotmartSecret(): string
    {
        return self::local('HOTMART_SECRET', getenv('HOTMART_SECRET') ?: '') ?: '';
    }

    public static function hotmartHottok(): string
    {
        return self::local('HOTMART_HOTTOK', getenv('HOTMART_HOTTOK') ?: '') ?: '';
    }

    // Meta Conversions API
    public static function metaPixelId(): string
    {
        return self::local('META_PIXEL_ID', getenv('META_PIXEL_ID') ?: '') ?: '';
    }

    public static function metaAccessToken(): string
    {
        return self::local('META_ACCESS_TOKEN', getenv('META_ACCESS_TOKEN') ?: '') ?: '';
    }
}
