<?php
/* ============================================================
   Cybersecurity Skills Hub — db_test.php
   ------------------------------------------------------------
   A one-screen health check. Open this when demonstrating
   "database connection" so there is visible proof that PHP is
   talking to MySQL.
   ============================================================ */

require_once 'db_connect.php';

/* ------------------------------------------------------------
   ACCESS CONTROL
   This page reports the MySQL version, PHP version, database
   name and table names. That is a free reconnaissance report
   for anyone probing the site, so it is staff-only — and it
   should be deleted entirely before any real deployment.
   ------------------------------------------------------------ */
requireStaffLogin();

$tables = [];
$res = $conn->query('SHOW TABLES FROM ' . DB_NAME);
while ($row = $res->fetch_array(MYSQLI_NUM)) {
    $tables[] = $row[0];
}

$rowTotal = $conn->query('SELECT COUNT(*) AS c FROM registrations')->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cybersecurity Skills Hub | Connection Test</title>
  <link rel="stylesheet" href="style.css">
  <script src="script.js" defer></script>
</head>
<body>

  <header class="navbar">
    <div class="container">
      <div class="logo">CyberSkills<span>Hub</span></div>
      <nav>
        <ul class="nav-links">
          <li><a href="index.html">Home</a></li>
          <li><a href="register.html">Register</a></li>
          <li><a href="view_registrations.php">Registrations</a></li>
          <li><button id="theme-toggle" class="theme-btn" type="button">🌙 Dark mode</button></li>
        </ul>
      </nav>
    </div>
  </header>

  <section class="page-hero">
    <h1>Database Connection Test</h1>
    <p>Confirms that <code>db_connect.php</code> reached the MySQL server.</p>
  </section>

  <section class="section">
    <div class="container">
      <div class="result-card ok">
        <h2>✅ Connected</h2>
        <p>The MySQLi connection opened successfully.</p>

        <div class="table-scroll">
          <table>
            <tbody>
              <tr><th style="width:42%">Host</th>            <td><?= e($conn->host_info) ?></td></tr>
              <tr><th>MySQL version</th>                      <td><?= e($conn->server_info) ?></td></tr>
              <tr><th>Database</th>                           <td><?= e(DB_NAME) ?></td></tr>
              <tr><th>Character set</th>                      <td><?= e($conn->character_set_name()) ?></td></tr>
              <tr><th>Tables found</th>                       <td><?= e(implode(', ', $tables)) ?></td></tr>
              <tr><th>Rows in <code>registrations</code></th> <td><?= e($rowTotal) ?></td></tr>
              <tr><th>PHP version</th>                        <td><?= e(PHP_VERSION) ?></td></tr>
            </tbody>
          </table>
        </div>

        <p style="margin-top:1.5rem">
          <a class="btn" href="view_registrations.php">View the records</a>
          &nbsp;
          <a class="btn btn-outline" href="register.html">Add a record</a>
        </p>
      </div>
    </div>
  </section>

  <footer>
    <div class="container">
      <div class="footer-bottom">
        <p>&copy; 2026 Cybersecurity Skills Hub · Final Project · Web Application Development</p>
      </div>
    </div>
  </footer>

</body>
</html>
<?php
$conn->close();
