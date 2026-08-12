<?php
/* ============================================================
   Cybersecurity Skills Hub — db_connect.php
   ------------------------------------------------------------
   The single MySQLi connection used by every PHP page.
   Include it at the top of any page that needs the database:

       require_once 'db_connect.php';

   Rubric: Database connection (2 marks)
   ============================================================ */

/* XAMPP defaults — if your MySQL root user has a password,
   put it in DB_PASS. */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cyberskillshub');

/* Make MySQLi raise exceptions instead of failing silently,
   so a broken query can never pass unnoticed. */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');   // full Unicode support

} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
       . '<title>Database unavailable</title>'
       . '<link rel="stylesheet" href="style.css"></head><body>'
       . '<section class="section"><div class="container">'
       . '<div class="result-card bad"><h2>Database unavailable</h2>'
       . '<p>PHP could not reach the MySQL server.</p>'
       . '<p><strong>MySQL says:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>'
       . '<p>Start MySQL in the XAMPP control panel, then confirm the database '
       . '<code>' . DB_NAME . '</code> exists by running '
       . '<code>setup_database.sql</code> in phpMyAdmin.</p>'
       . '</div></div></section></body></html>';
    exit;
}

/* ------------------------------------------------------------
   HELPER — escape anything printed into the page.
   Every database value and every posted value passes through
   this before being echoed, so stored input can never run as
   HTML or JavaScript (stored XSS).
   ------------------------------------------------------------ */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/* ------------------------------------------------------------
   COURSE MAP
   The <select> in register.html submits short codes (its
   option values). This one map turns a code into its display
   name everywhere it is shown, and doubles as the whitelist
   the server validates against.
   ------------------------------------------------------------ */
const COURSE_NAMES = [
    'hygiene' => 'Cyber Hygiene Essentials',
    'network' => 'Network Security Fundamentals',
    'soc'     => 'SOC Analyst Bootcamp',
    'hacking' => 'Ethical Hacking Foundations',
];

function courseName($code)
{
    return COURSE_NAMES[$code] ?? $code;
}