<?php
declare(strict_types=1);

namespace App\Security;

final class RateLimiter
{
    private string $key;
    private int $max;
    private int $windowSeconds;
    private string $storageDir;

    public function __construct(string $key, int $max, int $windowSeconds, string $storageDir = '')
    {
        $this->key = preg_replace('/[^a-zA-Z0-9:_\-\.]/', '_', $key);
        $this->max = $max;
        $this->windowSeconds = $windowSeconds;
        $this->storageDir = $storageDir !== '' ? $storageDir : sys_get_temp_dir() . '/evm_rate_limits';
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0777, true);
        }
    }

    public function allow(): bool
    {
        $now = time();
        $bucketStart = (int)floor($now / $this->windowSeconds) * $this->windowSeconds;
        $file = $this->storageDir . '/' . sha1($this->key . ':' . $bucketStart) . '.ctr';
        $count = 0;
        $fh = @fopen($file, 'c+');
        if ($fh === false) {
            return true; // fail-open
        }
        try {
            @flock($fh, LOCK_EX);
            $contents = stream_get_contents($fh);
            if ($contents !== false && $contents !== '') {
                $count = (int)$contents;
            }
            if ($count >= $this->max) {
                return false;
            }
            $count++;
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, (string)$count);
        } finally {
            @flock($fh, LOCK_UN);
            fclose($fh);
        }
        return true;
    }
}


