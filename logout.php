<?php
require_once __DIR__ . '/includes/app.php';

if (is_logged_in()) {
    $db = get_db();
    record_audit_log($db, 'LOGOUT', 'auth', $_SESSION['user_id'] ?? null);
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header("Location: index.php");
exit;
