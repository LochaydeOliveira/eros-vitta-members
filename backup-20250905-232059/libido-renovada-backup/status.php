<?php
header('Content-Type: text/plain; charset=UTF-8');
echo "OK\n";
echo 'Time: ' . date('c') . "\n";
echo 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '') . "\n";
echo 'UA: ' . ($_SERVER['HTTP_USER_AGENT'] ?? '') . "\n";
echo 'URI: ' . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
exit;

