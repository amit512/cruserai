<?php
declare(strict_types=1);
define('UPLOADS_URL', '/homecraft-php/uploads/');
define('SITE_NAME', 'HandCraft');
define('SITE_DESCRIPTION', 'Handmade Marketplace for Artisans');
define('ITEMS_PER_PAGE', 12);

// --- Database connection ---
function db(): PDO {
    $pdo = new PDO("mysql:host=localhost;dbname=homecraft;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

// --- Start session safely ---
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// --- CSRF token functions ---
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(419);
            die('Invalid CSRF token.');
        }
    }
}

// --- Helper functions for dynamic features ---
function get_categories(): array {
    return [
        'jewelry' => 'Handmade Jewelry',
        'home-decor' => 'Home Decor',
        'clothing' => 'Clothing & Accessories',
        'art' => 'Art & Paintings',
        'pottery' => 'Pottery & Ceramics',
        'textiles' => 'Textiles & Fabrics',
        'woodwork' => 'Woodwork & Furniture',
        'metalwork' => 'Metalwork & Sculptures',
        'leather' => 'Leather Goods',
        'candles' => 'Candles & Soaps'
    ];
}

function format_price(float $price): string {
    return 'Rs ' . number_format($price, 2);
}

function truncate_text(string $text, int $length = 100): string {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

function get_user_role(): ?string {
    return $_SESSION['user']['role'] ?? null;
}

function is_logged_in(): bool {
    return isset($_SESSION['user']);
}

function is_seller(): bool {
    return get_user_role() === 'seller';
}

function is_buyer(): bool {
    return get_user_role() === 'buyer';
}

function is_admin(): bool {
    return get_user_role() === 'admin';
}
