<?php
/* ============================================================
   Cybersecurity Skills Hub — logout.php
   ------------------------------------------------------------
   Ends the staff session properly: clears the data, expires the
   cookie, then destroys the session on the server.
   ============================================================ */

require_once 'db_connect.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

session_destroy();

header('Location: login.php');
exit;
