<?php
/* ============================================================
   Cybersecurity Skills Hub — login.php
   ------------------------------------------------------------
   Staff sign-in that protects the learner records. Without it,
   anyone who guessed the URL view_registrations.php could read
   every registrant's name, email and phone number.
   ============================================================ */

require_once 'db_connect.php';

$error = '';
$next  = basename($_GET['next'] ?? 'view_registrations.php');

/* Already signed in? Go straight through. */
if (isStaffLoggedIn()) {
    header('Location: ' . $next);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $next = basename($_POST['next'] ?? 'view_registrations.php');

    /* Simple throttle: slow down repeated failures so the login
       cannot be brute-forced at speed. */
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0);
    if ($_SESSION['login_attempts'] >= 3) {
        sleep(2);
    }

    /* hash_equals compares in constant time, so an attacker
       cannot learn the username from response timing. */
    $userOk = hash_equals(STAFF_USER, $user);
    $passOk = password_verify($pass, STAFF_PASSWORD_HASH);

    if ($userOk && $passOk) {
        /* Regenerate the session id on privilege change to
           defeat session fixation. */
        session_regenerate_id(true);
        $_SESSION['staff_logged_in'] = true;
        $_SESSION['login_attempts']  = 0;

        header('Location: ' . $next);
        exit;
    }

    $_SESSION['login_attempts']++;
    error_log('Failed staff login attempt for username: ' . $user);

    /* One message for both wrong username and wrong password,
       so the form never confirms which part was correct. */
    $error = 'Incorrect username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cybersecurity Skills Hub | Staff Login</title>
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
          <li><a href="about.html">About</a></li>
          <li><a href="courses.html">Courses</a></li>
          <li><a href="gallery.html">Gallery</a></li>
          <li><a href="register.html">Register</a></li>
          <li><a href="view_registrations.php" class="active">Registrations</a></li>
          <li><button id="theme-toggle" class="theme-btn" type="button">🌙 Dark mode</button></li>
        </ul>
      </nav>
    </div>
  </header>

  <section class="page-hero">
    <h1>Staff Login</h1>
    <p>Learner records contain personal data, so this area is restricted to
      hub staff.</p>
  </section>

  <section class="section">
    <div class="container">

      <?php if ($error !== ''): ?>
        <div class="form-summary error" style="display:block">⚠ <?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" action="login.php">
        <input type="hidden" name="next" value="<?= e($next) ?>">

        <fieldset>
          <legend>Sign in</legend>

          <div class="field">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" autocomplete="username">
          </div>

          <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password">
          </div>

          <button type="submit" class="btn">Sign in</button>
        </fieldset>
      </form>
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
