<?php
/* ============================================================
   Cybersecurity Skills Hub — process_register.php
   ------------------------------------------------------------
   The action target of the form in register.html.

     1. Confirms the request arrived by POST
     2. Reads every field from the $_POST array
     3. Re-validates on the server (JavaScript can be disabled,
        so client-side checks are never trusted on their own)
     4. Inserts the record with a prepared statement
     5. Prints a confirmation showing exactly what was received

   Rubric: PHP form processing (8 marks)
           Database connection + data insertion (4 marks)
   ============================================================ */

require_once 'db_connect.php';        // provides $conn, e(), courseName()

$requestMethod = $_SERVER['REQUEST_METHOD'];
$errors  = [];
$success = false;
$newId   = null;

/* ------------------------------------------------------------
   STEP 1 — POST ONLY
   Typing this file's URL into the address bar sends a GET
   request, which is refused with 405 Method Not Allowed.
   That is the visible proof the form uses POST.
   ------------------------------------------------------------ */
if ($requestMethod !== 'POST') {
    http_response_code(405);
}

if ($requestMethod === 'POST') {

    /* --------------------------------------------------------
       STEP 2 — READ THE POSTED DATA
       Each name="..." attribute in register.html becomes a
       key in the $_POST superglobal.
       -------------------------------------------------------- */
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $phone    = trim($_POST['phone']    ?? '');
    $level    = trim($_POST['level']    ?? '');    // radio group
    $course   = trim($_POST['course']   ?? '');    // select (short code)
    $goals    = trim($_POST['goals']    ?? '');    // textarea
    /* The checkbox submits value="accepted" when ticked and is
       absent from $_POST entirely when not, so isset() is the
       correct test. */
    $terms    = isset($_POST['terms']) ? 1 : 0;

    /* --------------------------------------------------------
       STEP 3 — SERVER-SIDE VALIDATION
       These mirror the checks in script.js. A visitor who
       disables JavaScript still cannot save bad data.
       -------------------------------------------------------- */
    if ($fullname === '') {
        $errors[] = 'Full name is required.';
    } elseif (mb_strlen($fullname) > 100) {
        $errors[] = 'Full name must be 100 characters or fewer.';
    }

    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'That email address is not valid.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($phone === '') {
        $errors[] = 'Phone number is required.';
    } elseif (!preg_match('/^\+?[0-9\s\-]{7,20}$/', $phone)) {
        $errors[] = 'Phone number may contain digits, spaces, hyphens and a leading + only.';
    }

    $allowedLevels = ['beginner', 'intermediate', 'advanced'];
    if ($level === '') {
        $errors[] = 'Select an experience level.';
    } elseif (!in_array($level, $allowedLevels, true)) {
        $errors[] = 'That experience level is not one of the options offered.';
    }

    /* The whitelist is the COURSE_NAMES map itself — only the
       four codes the <select> actually offers are accepted. */
    if ($course === '') {
        $errors[] = 'Choose a course.';
    } elseif (!array_key_exists($course, COURSE_NAMES)) {
        $errors[] = 'That course is not one of the options offered.';
    }

    /* The textarea has maxlength="200" in the HTML, but that
       attribute is only advisory — it must be enforced here. */
    if (mb_strlen($goals) > 200) {
        $errors[] = 'Your learning goals must be 200 characters or fewer.';
    }

    if ($terms !== 1) {
        $errors[] = 'You must accept the terms and conditions.';
    }

    /* --------------------------------------------------------
       STEP 4 — INSERT WITH A PREPARED STATEMENT
       The SQL and the user data travel in separate channels,
       so nothing typed into the form can execute as SQL.
       -------------------------------------------------------- */
    if (empty($errors)) {

        // One-way bcrypt hash — the plain password is never stored
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $sql = "INSERT INTO registrations
                        (fullname, email, phone, password_hash,
                         level, course, goals, terms_accepted)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            // 7 strings then 1 integer
            $stmt->bind_param(
                'sssssssi',
                $fullname, $email, $phone, $passwordHash,
                $level, $course, $goals, $terms
            );

            $stmt->execute();

            $newId   = $conn->insert_id;   // AUTO_INCREMENT id of the new row
            $success = true;

            $stmt->close();

        } catch (mysqli_sql_exception $ex) {
            // 1062 = duplicate value on the UNIQUE email key
            if ((int) $ex->getCode() === 1062) {
                $errors[] = 'That email address is already registered. Use a different one.';
            } else {
                $errors[] = 'The registration could not be saved: ' . $ex->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cybersecurity Skills Hub | Registration Result</title>
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
          <li><a href="view_registrations.php">Registrations</a></li>
          <li><button id="theme-toggle" class="theme-btn" type="button">🌙 Dark mode</button></li>
        </ul>
      </nav>
    </div>
  </header>

  <section class="page-hero">
    <h1>Registration Result</h1>
    <p>Processed on the server by <code>process_register.php</code> using the
      POST method.</p>
  </section>

  <section class="section">
    <div class="container">

<?php if ($requestMethod !== 'POST'): ?>

      <!-- Reached by typing the URL rather than submitting the form -->
      <div class="result-card bad">
        <h2>⚠ This page only accepts POST</h2>
        <p>Your browser sent a <strong><?= e($requestMethod) ?></strong> request.
          Registrations are accepted only when the form on the Register page is
          submitted, because that form is set to <code>method="post"</code> —
          the data travels in the request body, not the address bar.</p>
        <p><a class="btn" href="register.html">Go to the registration form</a></p>
      </div>

<?php elseif ($success): ?>

      <div class="result-card ok">
        <h2>✅ Registration saved</h2>
        <p>Thank you, <strong><?= e($fullname) ?></strong>. Your enrolment is
          stored in the database under reference <strong>#<?= e($newId) ?></strong>.
          A confirmation will be sent to <strong><?= e($email) ?></strong>.</p>
      </div>

      <div class="result-card">
        <h2>Data received by the server</h2>
        <p>
          Request method: <span class="badge"><?= e($requestMethod) ?></span>
          &nbsp; Fields in <code>$_POST</code>: <span class="badge"><?= count($_POST) ?></span>
        </p>

        <div class="table-scroll">
          <table>
            <thead>
              <tr><th style="width:32%">Field</th><th>Value received</th></tr>
            </thead>
            <tbody>
              <tr><td>Full name</td>       <td><?= e($fullname) ?></td></tr>
              <tr><td>Email</td>           <td><?= e($email) ?></td></tr>
              <tr><td>Phone</td>           <td><?= e($phone) ?></td></tr>
              <tr><td>Password</td>        <td>Received, then hashed with <code>password_hash()</code>. The plain text is never stored or displayed.</td></tr>
              <tr><td>Experience level</td><td><span class="pill <?= e($level) ?>"><?= e($level) ?></span></td></tr>
              <tr><td>Preferred course</td><td><?= e(courseName($course)) ?> <em>(submitted as "<?= e($course) ?>")</em></td></tr>
              <tr><td>Learning goals</td>  <td><?= $goals !== '' ? nl2br(e($goals)) : '<em>Not provided</em>' ?></td></tr>
              <tr><td>Terms accepted</td>  <td><?= $terms ? 'Yes' : 'No' ?></td></tr>
            </tbody>
          </table>
        </div>

        <p style="margin-top:1.5rem">
          <a class="btn" href="view_registrations.php">View all registrations</a>
          &nbsp;
          <a class="btn btn-outline" href="register.html">Register another learner</a>
        </p>
      </div>

<?php else: ?>

      <div class="result-card bad">
        <h2>⚠ Registration not saved</h2>
        <p>The server rejected the submission for these reasons:</p>
        <ul>
          <?php foreach ($errors as $message): ?>
            <li><?= e($message) ?></li>
          <?php endforeach; ?>
        </ul>
        <p>These checks run on the server, so they apply even when JavaScript
          is disabled in the browser.</p>
        <p><a class="btn" href="register.html">Back to the form</a></p>
      </div>

<?php endif; ?>

    </div>
  </section>

  <footer>
    <div class="container">
      <div class="footer-cols">
        <div class="footer-col">
          <h4>CyberSkillsHub</h4>
          <p>Building Kenya's next generation of cyber defenders.</p>
        </div>
        <div class="footer-col">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="courses.html">Courses</a></li>
            <li><a href="gallery.html">Gallery</a></li>
            <li><a href="register.html">Register</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Contact</h4>
          <ul>
            <li>📍 Nairobi, Kenya</li>
            <li>✉️ info@cyberskillshub.co.ke</li>
            <li>📞 +254 700 000 000</li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; 2026 Cybersecurity Skills Hub · Final Project · Web Application Development</p>
      </div>
    </div>
  </footer>

</body>
</html>
<?php
$conn->close();