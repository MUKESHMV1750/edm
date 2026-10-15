<?php
/**
 * Root Login Entry Point - Forwards to unified auth/login.php
 */
require_once __DIR__ . '/config/app.php';
$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: ' . APP_URL . '/auth/login.php' . $queryString);
exit;
