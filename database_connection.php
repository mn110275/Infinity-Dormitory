<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "infidorm";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

$today = date('Y-m-d');

$checkNewSem = mysqli_query($conn, "SELECT SEM_ID FROM SEMESTER 
                                    WHERE SEM_STATUS = 'Upcoming' 
                                    AND '$today' >= STARTDATE 
                                    LIMIT 1");

if ($newSem = mysqli_fetch_assoc($checkNewSem)) {
    $nextSemId = $newSem['SEM_ID'];

    mysqli_begin_transaction($conn);
    try {
        $oldRes = mysqli_query($conn, "SELECT SEM_ID FROM SEMESTER WHERE SEM_STATUS = 'Active' LIMIT 1");
        $oldSem = mysqli_fetch_assoc($oldRes);
        $oldSemId = $oldSem ? $oldSem['SEM_ID'] : null;

        if ($oldSemId) {
            mysqli_query($conn, "UPDATE CONTRACT c 
                                 SET c.STATUS = 'Expired' 
                                 WHERE c.SEM_ID = '$oldSemId' 
                                 AND c.STATUS = 'Active'
                                 AND NOT EXISTS (
                                     SELECT 1 FROM (SELECT * FROM CONTRACT) c2 
                                     WHERE c2.STD_ID = c.STD_ID 
                                     AND c2.SEM_ID = '$nextSemId'
                                 )");

            mysqli_query($conn, "UPDATE STUDENT s 
                                 JOIN CONTRACT c ON s.STD_ID = c.STD_ID 
                                 SET s.IS_ACTIVE = 0 
                                 WHERE c.SEM_ID = '$oldSemId' AND c.STATUS = 'Expired'");

            mysqli_query($conn, "UPDATE SEMESTER SET SEM_STATUS = 'Archived' WHERE SEM_ID = '$oldSemId'");
        }

        $stmtSem = mysqli_prepare($conn, "UPDATE SEMESTER SET SEM_STATUS = 'Active' WHERE SEM_ID = ?");
        mysqli_stmt_bind_param($stmtSem, 's', $nextSemId);
        mysqli_stmt_execute($stmtSem);

        mysqli_query($conn, "UPDATE CONTRACT SET STATUS = 'Active' 
                             WHERE SEM_ID = '$nextSemId' AND STATUS = 'Upcoming'");

        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        exit("Lỗi hệ thống: " . $e->getMessage()); 
    }
}
?>