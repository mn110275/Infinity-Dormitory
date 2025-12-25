<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAjaxRequest(): bool
{
    return (
        (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        ||
        (isset($_SERVER['HTTP_ACCEPT']) &&
         str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
    );
}

/**
 * Require role dùng chung cho:
 * - Trang HTML
 * - API / AJAX (fetch)
 */
function requireRole(string $requiredRole): void
{
    $isAjax = isAjaxRequest();

    if (!isset($_SESSION['role'])) {

        if ($isAjax) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Unauthenticated'
            ]);
        } else {
            header("Location: ../index.php");
        }
        exit;
    }

    if ($_SESSION['role'] !== $requiredRole) {

        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Permission denied'
            ]);
        } else {
            http_response_code(403);
            include __DIR__ . '/../errors/403.php';
        }
        exit;
    }
}
