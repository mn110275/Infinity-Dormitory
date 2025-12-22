<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function redirectByRole(): void
{
    if (!isset($_SESSION['role'])) {
        header("Location: ../index.php");
        exit;
    }

    switch ($_SESSION['role']) {
        case 'student':
            header("Location: ../student/home.php");
            break;
        case 'manager':
            header("Location: ../manager/dashboard.php");
            break;
        case 'admin':
            header("Location: ../admin/dashboard.php");
            break;
        default:
            header("Location: ../index.php");
    }
    exit;
}

function requireRole(string $role): void
{
    if (!isset($_SESSION['role'])) {
        redirectByRole();
    }

    if ($_SESSION['role'] !== $role) {
        redirectByRole();
    }
}
