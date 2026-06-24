<?php
/*
|--------------------------------------------------------------------------
| Ticketly Main Configuration File
|--------------------------------------------------------------------------
| File path: includes/config.php
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Session Start
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Timezone
|--------------------------------------------------------------------------
| Very important for showtime logic.
| Example:
| If showtime is 10 AM, user cannot book after 10 AM Nepal time.
*/

date_default_timezone_set('Asia/Kathmandu');

/*
|--------------------------------------------------------------------------
| Database Configuration
|--------------------------------------------------------------------------
*/

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

    /*
    |--------------------------------------------------------------------------
    | MySQL Timezone
    |--------------------------------------------------------------------------
    | Nepal timezone = +05:45
    | This makes CURDATE(), CURTIME(), NOW() work correctly in MySQL.
    */

    try {
        $pdo->exec("SET time_zone = '+05:45'");
    } catch (Exception $e) {
        // If MySQL does not allow timezone setting, PHP timezone still works.
    }

} catch (Exception $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

/*
|--------------------------------------------------------------------------
| eSewa Sandbox Configuration
|--------------------------------------------------------------------------
*/

if (!defined('ESEWA_PRODUCT_CODE')) {
    define('ESEWA_PRODUCT_CODE', 'EPAYTEST');
}

if (!defined('ESEWA_SECRET_KEY')) {
    define('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q');
}

if (!defined('ESEWA_PAYMENT_URL')) {
    define('ESEWA_PAYMENT_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form');
}

if (!defined('ESEWA_VERIFY_URL')) {
    define('ESEWA_VERIFY_URL', 'https://uat.esewa.com.np/api/epay/transaction/status/');
}

/*
|--------------------------------------------------------------------------
| eSewa Test Mode
|--------------------------------------------------------------------------
| true  = use Rs. 1 for sandbox testing
| false = use real ticket amount
*/

$ESEWA_TEST_MODE = true;

/*
|--------------------------------------------------------------------------
| Base URL
|--------------------------------------------------------------------------
| Example:
| http://localhost/Ticketly
|--------------------------------------------------------------------------
*/

if (!defined('BASE_URL')) {
    define('BASE_URL', (function () {
        $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
        $dir     = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');

        $sub = '';

        if ($docRoot && strpos($dir, $docRoot) === 0) {
            $sub = str_replace($docRoot, '', $dir);
        }

        return rtrim($scheme . '://' . $host . $sub, '/');
    })());
}

/*
|--------------------------------------------------------------------------
| Auth Helper Functions
|--------------------------------------------------------------------------
*/

if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool {
        return isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']);
    }
}

if (!function_exists('currentUserId')) {
    function currentUserId(): ?int {
        return isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;
    }
}

if (!function_exists('isAdminLoggedIn')) {
    function isAdminLoggedIn(): bool {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

if (!function_exists('currentAdminId')) {
    function currentAdminId(): ?int {
        return isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;
    }
}

/*
|--------------------------------------------------------------------------
| Showtime Helper Functions
|--------------------------------------------------------------------------
| Use these if needed in other files.
*/

if (!function_exists('currentNepalDate')) {
    function currentNepalDate(): string {
        return date('Y-m-d');
    }
}

if (!function_exists('currentNepalTime')) {
    function currentNepalTime(): string {
        return date('H:i:s');
    }
}

if (!function_exists('currentNepalDateTime')) {
    function currentNepalDateTime(): string {
        return date('Y-m-d H:i:s');
    }
}

/*
|--------------------------------------------------------------------------
| eSewa Signature Generator
|--------------------------------------------------------------------------
| Message format:
| total_amount=<X>,transaction_uuid=<Y>,product_code=<Z>
*/

if (!function_exists('generateEsewaSignature')) {
    function generateEsewaSignature(string $totalAmount, string $transactionUuid): string {
        $msg = "total_amount={$totalAmount},transaction_uuid={$transactionUuid},product_code=" . ESEWA_PRODUCT_CODE;
        return base64_encode(hash_hmac('sha256', $msg, ESEWA_SECRET_KEY, true));
    }
}

/*
|--------------------------------------------------------------------------
| Verify eSewa Success Response
|--------------------------------------------------------------------------
| eSewa sends ?data=<base64-json> on success redirect.
*/

if (!function_exists('verifyEsewaResponse')) {
    function verifyEsewaResponse(string $encodedData) {
        $json = base64_decode($encodedData, true);

        if (!$json) {
            return false;
        }

        $data = json_decode($json, true);

        if (!is_array($data)) {
            return false;
        }

        $fields = array_map('trim', explode(',', $data['signed_field_names'] ?? ''));

        if (empty($fields)) {
            return false;
        }

        $parts = [];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                return false;
            }

            $parts[] = "{$field}={$data[$field]}";
        }

        $expectedSignature = base64_encode(
            hash_hmac('sha256', implode(',', $parts), ESEWA_SECRET_KEY, true)
        );

        if (!hash_equals($expectedSignature, $data['signature'] ?? '')) {
            return false;
        }

        return $data;
    }
}