<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireRole(string $requiredRole): void
{
    if (!isset($_SESSION['role'])) {
        header("Location: ../index.php");
        exit;
    }

    if ($_SESSION['role'] !== $requiredRole) {
        header("HTTP/1.1 403 Forbidden");
        include __DIR__ . '/../errors/403.php';
        exit;
    }
}