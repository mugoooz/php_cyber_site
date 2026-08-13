<?php
/* ============================================================
   Cybersecurity Skills Hub — db_connect.php
   ------------------------------------------------------------
   Shared MySQLi connection, output escaping, and the staff
   authentication helpers used to protect the records pages.

   Include it at the top of any page that needs the database:

       require_once 'db_connect.php';

   Rubric: Database connection (2 marks)
   ============================================================ */

/* ------------------------------------------------------------
   ERROR DISCLOSURE CONTROL
   Leave this false. When true, PHP prints file paths, line
   numbers and SQL details straight into the page — useful while
   building, but it hands an attacker a map of the application.
   Errors are always written to error.log either way.
   ------------------------------------------------------------ */
const DEBUG_ERRORS = false;

ini_set('display_errors', DEBUG_ERRORS ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/error.log');
error_reporting(E_ALL);

/* ------------------------------------------------------------
   SESSION
   Started before any output so the cookie flags apply. The
   cookie is HttpOnly so JavaScript cannot read it, and
   SameSite=Strict so it is not sent on cross-site requests.
   ------------------------------------------------------------ */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Strict',
        // 'secure' => true,   // switch on once the site runs over HTTPS
    ]);
    session_start();
}

/* ------------------------------------------------------------
   SECURITY HEADERS
   Defence in depth: stop MIME sniffing, block framing
   (clickjacking), and avoid leaking URLs in the Referer header.
   ------------------------------------------------------------ */
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

/* ------------------------------------------------------------
   CONNECTION CREDENTIALS
   XAMPP defaults. If your MySQL root user has a password, put
   it in DB_PASS.
   ------------------------------------------------------------ */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cyberskillshub');

/* ------------------------------------------------------------
   STAFF LOGIN
   Protects view_registrations.php and db_test.php, which would
   otherwise expose every learner's personal data to anyone who
   knows the URL.

   Default demo password: CyberHub#2026
   Replace this hash with your own using make_hash.php, then
   delete make_hash.php.
   ------------------------------------------------------------ */
const STAFF_USER = 'admin';
const STAFF_PASSWORD_HASH = '$6$TQL2HTe9lxut3PS3$h6j.4D7sGymiH9/4wsktG8PXe0wqP5.Xq5s7ZwmoIO0.qFmUJJi28cfOTK51l.I/49lnM3a76NbQybDGtioG//';

/* Is the current visitor a signed-in staff member? */
function isStaffLoggedIn()
{
    return !empty($_SESSION['staff_logged_in']);
}

/* Guard: place at the top of any page that must not be public.
   Redirects to the login form instead of rendering anything. */
function requireStaffLogin()
{
    if (!isStaffLoggedIn()) {
        header('Location: login.php?next=' . urlencode(basename($_SERVER['PHP_SELF'])));
        exit;
    }
}

/* ------------------------------------------------------------
   OPEN THE CONNECTION
   ------------------------------------------------------------ */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');

} catch (mysqli_sql_exception $ex) {

    /* The real reason goes to the log, never to the browser. A
       message like "Access denied for user 'root'@'localhost'"
       gives away the username, the host and the database
       software in a single line. */
    error_log('DB connection failed: ' . $ex->getMessage());

    http_response_code(503);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
       . '<title>Service unavailable</title>'
       . '<link rel="stylesheet" href="style.css"></head><body>'
       . '<section class="section"><div class="container">'
       . '<div class="result-card bad"><h2>Service temporarily unavailable</h2>'
       . '<p>We could not process your request right now. Please try again '
       . 'shortly.</p>';

    if (DEBUG_ERRORS) {
        echo '<p><strong>Developer detail:</strong> '
           . htmlspecialchars($ex->getMessage()) . '</p>';
    }

    echo '</div></div></section></body></html>';
    exit;
}

/* ------------------------------------------------------------
   HELPER — escape anything printed into a page.
   Every database value and every posted value passes through
   this before being echoed, so stored input cannot run as HTML
   or JavaScript (stored XSS).
   ------------------------------------------------------------ */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/* ------------------------------------------------------------
   COURSE MAP
   The <select> in register.html submits short codes. This map
   turns a code into its display name and doubles as the
   whitelist the server validates against.
   ------------------------------------------------------------ */
const COURSE_NAMES = [
    'hygiene' => 'Cyber Hygiene Essentials',
    'network' => 'Network Security Fundamentals',
    'soc'     => 'SOC Analyst Bootcamp',
    'hacking' => 'Ethical Hacking Foundations',
];

function courseName($code)
{
    return COURSE_NAMES[$code] ?? 'Unknown course';
}

/* ------------------------------------------------------------
   HELPER — reduce any Kenyan phone format to +254XXXXXXXXX so
   the stored data is consistent however it was typed.
   ------------------------------------------------------------ */
function normalizePhone($raw)
{
    $digits = preg_replace('/\D/', '', $raw);

    if (strpos($digits, '254') === 0) {
        $digits = substr($digits, 3);
    } elseif (strpos($digits, '0') === 0) {
        $digits = substr($digits, 1);
    }

    return strlen($digits) === 9 ? '+254' . $digits : '';
}
