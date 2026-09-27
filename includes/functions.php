<?php
/**
 * Global Helper Functions
 */

function get_root_url(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (strpos($script, '/medicines/') !== false || 
        strpos($script, '/purchases/') !== false || 
        strpos($script, '/inventory/') !== false || 
        strpos($script, '/billing/') !== false || 
        strpos($script, '/customers/') !== false || 
        strpos($script, '/suppliers/') !== false || 
        strpos($script, '/reports/') !== false || 
        strpos($script, '/auth/') !== false || 
        strpos($script, '/api/') !== false ||
        strpos($script, '/install/') !== false) {
        return '../';
    }
    return '';
}

function e(?string $string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function format_currency($amount): string {
    $val = (float)($amount ?? 0);
    return '₹' . number_format($val, 2);
}

function format_date(?string $date, string $format = 'd M Y'): string {
    if (empty($date)) return '-';
    $time = strtotime($date);
    return $time ? date($format, $time) : '-';
}

function format_datetime(?string $date): string {
    return format_date($date, 'd M Y, h:i A');
}

function get_setting(string $key, $default = null): ?string {
    static $settings = null;
    if ($settings === null) {
        try {
            $db = get_db();
            $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        } catch (Exception $e) {
            $settings = [];
        }
    }
    return $settings[$key] ?? $default;
}

function generate_invoice_number(): string {
    $db = get_db();
    $prefix = get_setting('invoice_prefix', 'INV') . '-' . date('Y') . '-';
    $stmt = $db->query("SELECT MAX(id) FROM sales");
    $max_id = (int)$stmt->fetchColumn() + 1;
    return $prefix . str_pad($max_id, 4, '0', STR_PAD_LEFT);
}

function generate_purchase_number(): string {
    $db = get_db();
    $prefix = get_setting('purchase_prefix', 'PUR') . '-' . date('Y') . '-';
    $stmt = $db->query("SELECT MAX(id) FROM purchases");
    $max_id = (int)$stmt->fetchColumn() + 1;
    return $prefix . str_pad($max_id, 4, '0', STR_PAD_LEFT);
}

function record_stock_movement(PDO $db, string $type, int $medicine_id, int $batch_id, int $quantity, ?int $ref_id = null, ?string $ref_no = null, ?string $notes = null): void {
    $user_id = $_SESSION['user_id'] ?? null;
    $stmt = $db->prepare("INSERT INTO stock_movements (movement_type, medicine_id, batch_id, quantity, reference_id, reference_number, notes, created_by) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$type, $medicine_id, $batch_id, $quantity, $ref_id, $ref_no, $notes, $user_id]);
}

function record_audit_log(PDO $db, string $action, string $module, ?int $record_id = null, $old_vals = null, $new_vals = null): void {
    $user = current_user();
    $user_id = $user['id'] ?? null;
    $user_name = $user['name'] ?? 'System';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

    $old_json = $old_vals !== null ? json_encode($old_vals) : null;
    $new_json = $new_vals !== null ? json_encode($new_vals) : null;

    $stmt = $db->prepare("INSERT INTO audit_logs (user_id, user_name, action, module, record_id, old_values, new_values, ip_address, user_agent) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $user_name, $action, $module, $record_id, $old_json, $new_json, $ip, $agent]);
}
