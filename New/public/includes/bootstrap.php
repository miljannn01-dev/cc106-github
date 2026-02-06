<?php

$config = require __DIR__ . '/config.php';

if (!is_dir($config['uploads_path'])) {
    mkdir($config['uploads_path'], 0775, true);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function db(): PDO
{
    static $pdo;
    global $config;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['db']['host'],
        $config['db']['port'],
        $config['db']['database'],
        $config['db']['charset']
    );

    try {
        $pdo = new PDO(
            $dsn,
            $config['db']['username'],
            $config['db']['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $e) {
        http_response_code(500);
        die('Database connection failed. Please verify your phpMyAdmin credentials.');
    }

    return $pdo;
}

function app_name(): string
{
    global $config;
    return $config['app_name'];
}

function base_path(string $path = ''): string
{
    global $config;
    
    // Use config value if set, otherwise auto-detect
    static $detectedBase = null;
    if ($detectedBase === null) {
        if (!empty($config['base_path'])) {
            $detectedBase = $config['base_path'];
        } else {
            // Auto-detect: find the 'public' directory in the script path
            $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            $publicPos = strpos($scriptName, '/public');
            if ($publicPos !== false) {
                // Extract everything up to and including '/public'
                $detectedBase = substr($scriptName, 0, $publicPos + 7); // +7 for '/public'
            } else {
                // Fallback: use script directory
                $detectedBase = dirname($scriptName);
            }
        }
    }
    
    $base = rtrim($detectedBase, '/');
    $cleanPath = ltrim($path, '/');
    return $cleanPath ? "{$base}/{$cleanPath}" : $base;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect(string $path): void
{
    header("Location: {$path}");
    exit;
}

function sanitize(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function login_user(array $user): void
{
    $_SESSION['user'] = [
        'id' => $user['id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'user_type' => $user['user_type'],
        'company_name' => $user['company_name'] ?? null,
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

function require_login(?string $role = null): void
{
    $user = current_user();
    if (!$user) {
        redirect(base_path('auth/login.php'));
    }

    if ($role && $user['user_type'] !== $role) {
        redirect(base_path('index.php'));
    }
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message === null) {
        $value = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $value;
    }

    $_SESSION['flash'][$key] = $message;
    return null;
}

function find_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    return $stmt->fetch() ?: null;
}

function fetch_grants(string $status = 'published'): array
{
    $stmt = db()->prepare('SELECT * FROM grant_programs WHERE status = :status ORDER BY deadline ASC');
    $stmt->execute(['status' => $status]);
    return $stmt->fetchAll();
}

function fetch_grant_with_requirements(int $grantId): ?array
{
    $stmt = db()->prepare('SELECT * FROM grant_programs WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $grantId]);
    $grant = $stmt->fetch();

    if (!$grant) {
        return null;
    }

    $reqStmt = db()->prepare('SELECT * FROM grant_requirements WHERE grant_id = :id ORDER BY sort_order ASC');
    $reqStmt->execute(['id' => $grantId]);
    $grant['requirements'] = $reqStmt->fetchAll();

    return $grant;
}

function application_status_options(): array
{
    static $options;
    if ($options !== null) {
        return $options;
    }

    $stmt = db()->query('SELECT * FROM application_statuses ORDER BY sort_order ASC');
    $options = $stmt->fetchAll();

    return $options;
}

function application_status_id(string $statusKey): ?int
{
    static $map = null;

    if ($map === null) {
        $map = [];
        foreach (application_status_options() as $status) {
            $map[$status['status_key']] = (int) $status['id'];
        }
    }

    return $map[$statusKey] ?? null;
}

function generate_application_code(): string
{
    return 'APP-' . strtoupper(bin2hex(random_bytes(4)));
}


