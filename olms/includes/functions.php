<?php

declare(strict_types=1);

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    global $BASE_URL;
    return ($BASE_URL ?? '') . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = (string)($_POST['csrf_token'] ?? '');
    $known = (string)($_SESSION['csrf_token'] ?? '');
    if ($submitted === '' || $known === '' || !hash_equals($known, $submitted)) {
        http_response_code(419);
        exit('Your form session expired. Please go back, refresh the page, and try again.');
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    global $pdo;
    $user = current_user();
    if (!$user) {
        set_flash('warning', 'Please sign in to continue.');
        redirect('login.php');
    }
    // Refresh the session identity and enforce account deactivation immediately.
    $stmt = $pdo->prepare('SELECT id, employee_code, full_name, email, department, designation, role, status FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$user['id']]);
    $freshUser = $stmt->fetch();
    if (!$freshUser || $freshUser['status'] !== 'active') {
        unset($_SESSION['user']);
        set_flash('warning', 'Your account is inactive or no longer available. Contact an administrator.');
        redirect('login.php');
    }
    $_SESSION['user'] = $freshUser;
    return $freshUser;
}

function require_role(string $role): array
{
    $user = require_login();
    if (($user['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('You do not have permission to access this page.');
    }
    return $user;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function format_date(?string $date): string
{
    if (!$date) return '—';
    try { return (new DateTimeImmutable($date))->format('d M Y'); }
    catch (Throwable) { return h($date); }
}

function format_days(float|int|string|null $days): string
{
    $number = (float)$days;
    return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.') . ' ' . ($number === 1.0 ? 'day' : 'days');
}

function status_class(string $status): string
{
    return match ($status) {
        'approved' => 'success',
        'rejected' => 'danger',
        'cancelled' => 'muted',
        default => 'warning',
    };
}

function notify_user(PDO $pdo, int $userId, string $title, string $message, string $type = 'info', ?int $leaveRequestId = null): void
{
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, leave_request_id, title, message, type) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $leaveRequestId, $title, $message, $type]);
}

function ensure_user_balances(PDO $pdo, int $userId, ?int $year = null): void
{
    $year ??= (int)date('Y');
    $sql = 'INSERT IGNORE INTO user_leave_balances (user_id, leave_type_id, balance_year, allocated_days, used_days)
            SELECT ?, id, ?, annual_quota, 0 FROM leave_types WHERE is_active = 1';
    $pdo->prepare($sql)->execute([$userId, $year]);
}

function get_leave_balances(PDO $pdo, int $userId, ?int $year = null): array
{
    $year ??= (int)date('Y');
    ensure_user_balances($pdo, $userId, $year);
    $stmt = $pdo->prepare('SELECT lt.id AS leave_type_id, lt.name, lt.code, COALESCE(b.allocated_days, lt.annual_quota) AS allocated_days,
                                  COALESCE(b.used_days, 0) AS used_days,
                                  GREATEST(COALESCE(b.allocated_days, lt.annual_quota) - COALESCE(b.used_days, 0), 0) AS remaining_days
                           FROM leave_types lt
                           LEFT JOIN user_leave_balances b ON b.leave_type_id = lt.id AND b.user_id = ? AND b.balance_year = ?
                           WHERE lt.is_active = 1 ORDER BY lt.name');
    $stmt->execute([$userId, $year]);
    return $stmt->fetchAll();
}

function working_days(PDO $pdo, string $start, string $end): float
{
    $startDate = new DateTimeImmutable($start);
    $endDate = new DateTimeImmutable($end);
    if ($endDate < $startDate) return 0;

    $holidayStmt = $pdo->prepare('SELECT holiday_date FROM holidays WHERE holiday_date BETWEEN ? AND ?');
    $holidayStmt->execute([$start, $end]);
    $holidays = array_fill_keys(array_column($holidayStmt->fetchAll(), 'holiday_date'), true);

    $count = 0;
    for ($date = $startDate; $date <= $endDate; $date = $date->modify('+1 day')) {
        $dayOfWeek = (int)$date->format('N');
        $key = $date->format('Y-m-d');
        if ($dayOfWeek <= 5 && !isset($holidays[$key])) $count++;
    }
    return (float)$count;
}

function overlapping_requests(PDO $pdo, string $start, string $end, ?int $excludeRequestId = null, ?int $excludeUserId = null): array
{
    $sql = "SELECT lr.id, lr.request_code, lr.start_date, lr.end_date, lr.status,
                   u.id AS user_id, u.full_name, u.employee_code, u.department
            FROM leave_requests lr JOIN users u ON u.id = lr.user_id
            WHERE lr.status IN ('pending','approved') AND lr.start_date <= ? AND lr.end_date >= ?";
    $params = [$end, $start];
    if ($excludeRequestId !== null) { $sql .= ' AND lr.id <> ?'; $params[] = $excludeRequestId; }
    if ($excludeUserId !== null) { $sql .= ' AND lr.user_id <> ?'; $params[] = $excludeUserId; }
    $sql .= ' ORDER BY u.department, u.full_name';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function backup_suggestions(PDO $pdo, int $departmentUserId, string $start, string $end): array
{
    $departmentStmt = $pdo->prepare('SELECT department FROM users WHERE id = ?');
    $departmentStmt->execute([$departmentUserId]);
    $department = $departmentStmt->fetchColumn();
    if (!$department) return [];

    $sql = "SELECT u.id, u.full_name, u.employee_code, u.designation, u.department
            FROM users u
            WHERE u.role = 'employee' AND u.status = 'active' AND u.department = ? AND u.id <> ?
              AND NOT EXISTS (
                SELECT 1 FROM leave_requests lr
                WHERE lr.user_id = u.id AND lr.status = 'approved'
                  AND lr.start_date <= ? AND lr.end_date >= ?
              )
            ORDER BY u.full_name LIMIT 8";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$department, $departmentUserId, $end, $start]);
    return $stmt->fetchAll();
}

function unread_notification_count(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function generate_employee_code(PDO $pdo): string
{
    do {
        $code = 'EMP-' . date('ym') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE employee_code = ?');
        $stmt->execute([$code]);
    } while ((int)$stmt->fetchColumn() > 0);
    return $code;
}

function log_activity(PDO $pdo, int $actorId, string $action, string $entityType, ?int $entityId = null, ?string $details = null): void
{
    $stmt = $pdo->prepare('INSERT INTO activity_logs (actor_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$actorId, $action, $entityType, $entityId, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
}
