<?php
session_start();

// ─── Database ────────────────────────────────────────────────────────────────
$dbHost = '127.0.0.1';
$dbName = 'ticketly';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (Exception $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// ─── eSewa Sandbox Config ─────────────────────────────────────────────────────
// Sandbox credentials from developer.esewa.com.np
define('ESEWA_PRODUCT_CODE', 'EPAYTEST');
define('ESEWA_SECRET_KEY',   '8gBm/:&EnhH.1/q');
define('ESEWA_PAYMENT_URL',  'https://rc-epay.esewa.com.np/api/epay/main/v2/form');
define('ESEWA_VERIFY_URL',   'https://uat.esewa.com.np/api/epay/transaction/status/');

// ─── BASE_URL ─────────────────────────────────────────────────────────────────
// Dynamically built so it works on any port (80, 8080, etc.)
define('BASE_URL', (function () {
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $dir     = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    $sub     = str_replace($docRoot, '', $dir);
    return $scheme . '://' . $host . $sub;
})());

// ─── Auth helpers ─────────────────────────────────────────────────────────────
function isLoggedIn(): bool   { return isset($_SESSION['customer_id']); }
function currentUserId(): ?int { return isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null; }
function isAdminLoggedIn(): bool { return isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; }
function currentAdminId(): ?int  { return isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null; }

// ─── eSewa signature (HMAC-SHA256, base64) ────────────────────────────────────
// Message format per eSewa docs:
//   total_amount=<X>,transaction_uuid=<Y>,product_code=<Z>
function generateEsewaSignature(string $totalAmount, string $transactionUuid): string {
    $msg = "total_amount={$totalAmount},transaction_uuid={$transactionUuid},product_code=" . ESEWA_PRODUCT_CODE;
    return base64_encode(hash_hmac('sha256', $msg, ESEWA_SECRET_KEY, true));
}

// ─── Verify eSewa success response ───────────────────────────────────────────
// eSewa sends ?data=<base64-JSON> on success redirect
function verifyEsewaResponse(string $encodedData): array|false {
    $json = base64_decode($encodedData, true);
    if (!$json) return false;

    $data = json_decode($json, true);
    if (!is_array($data)) return false;

    // Build message from signed_field_names (exact order eSewa specifies)
    $fields = array_map('trim', explode(',', $data['signed_field_names'] ?? ''));
    $parts  = [];
    foreach ($fields as $f) {
        if (!array_key_exists($f, $data)) return false;
        $parts[] = "{$f}={$data[$f]}";
    }

    $expected = base64_encode(hash_hmac('sha256', implode(',', $parts), ESEWA_SECRET_KEY, true));
    if (!hash_equals($expected, $data['signature'] ?? '')) return false;

    return $data;
}
