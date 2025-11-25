<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!defined('CSRF_SESSION_KEY')) define('CSRF_SESSION_KEY', 'csrf_token');

function csrf_token(): string {
    if (empty($_SESSION[CSRF_SESSION_KEY])) {
        $_SESSION[CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_SESSION_KEY];
}

function verify_csrf(?string $token): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $sess = $_SESSION[CSRF_SESSION_KEY] ?? '';
    if (!is_string($token) || $token === '') return false;
    return hash_equals((string)$sess, (string)$token);
}
