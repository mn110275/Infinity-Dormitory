<?php
function loginWithRole(mysqli $conn, string $email, string $password, string $expectedRole): ?array
{
    $sql = "SELECT USER_ID, USER_ROLE, PASS 
            FROM USERS 
            WHERE EMAIL = ? 
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows !== 1) {
        return null;
    }

    $user = $result->fetch_assoc();

    if ($user['USER_ROLE'] !== $expectedRole || !password_verify($password, $user['PASS'])) {
        return null;
    }

    unset($user['PASS']);
    return $user;
}
