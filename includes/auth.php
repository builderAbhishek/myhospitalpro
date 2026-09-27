<?php
/**
 * Authentication & Authorization Guards
 */

function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $db = get_db();
        $stmt = $db->prepare("SELECT u.*, r.name as role_name, r.display_name as role_display 
                              FROM users u 
                              JOIN roles r ON u.role_id = r.id 
                              WHERE u.id = ? AND u.status = 'active' LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function require_login(): void {
    if (!is_logged_in()) {
        $root = get_root_url();
        header("Location: {$root}index.php");
        exit;
    }
}

function has_permission(string $permission_name): bool {
    $user = current_user();
    if (!$user) return false;
    if ($user['role_name'] === 'admin') return true;

    static $permissions = [];
    if (!isset($permissions[$user['id']])) {
        $db = get_db();
        $stmt = $db->prepare("SELECT p.name FROM permissions p 
                              JOIN role_permissions rp ON p.id = rp.permission_id 
                              WHERE rp.role_id = ?");
        $stmt->execute([$user['role_id']]);
        $permissions[$user['id']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    return in_array($permission_name, $permissions[$user['id']]);
}

function require_permission(string $permission_name): void {
    require_login();
    if (!has_permission($permission_name)) {
        http_response_code(403);
        die("<div style='font-family:sans-serif;padding:30px;max-width:500px;margin:50px auto;border:1px solid #e2e8f0;border-radius:8px;'>"
            . "<h2>Access Denied</h2><p>You do not have permission to access this resource ({$permission_name}).</p>"
            . "<a href='" . get_root_url() . "dashboard.php'>&larr; Back to Dashboard</a></div>");
    }
}
