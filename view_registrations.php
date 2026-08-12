<?php
/* ============================================================
   Cybersecurity Skills Hub — view_registrations.php
   ------------------------------------------------------------
   Retrieves the rows saved by process_register.php and
   displays them, with a search box and a per-course summary.

   Rubric: Data retrieval (2 marks)
           Display of retrieved records (2 marks)
   ============================================================ */

require_once 'db_connect.php';        // provides $conn, e(), courseName()

/* ------------------------------------------------------------
   SEARCH FILTER
   GET is correct here because a search reads data rather than
   changing it. The value still goes through a prepared
   statement, so it cannot alter the query.
   ------------------------------------------------------------ */
$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $sql = "SELECT id, fullname, email, phone, level, course, goals, created_at
            FROM registrations
            WHERE fullname LIKE ? OR email LIKE ? OR course LIKE ?
            ORDER BY created_at DESC";

    $stmt     = $conn->prepare($sql);
    $wildcard = '%' . $search . '%';
    $stmt->bind_param('sss', $wildcard, $wildcard, $wildcard);

} else {
    $sql  = "SELECT id, fullname, email, phone, level, course, goals, created_at
             FROM registrations
             ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result   = $stmt->get_result();
$rowCount = $result->num_rows;

/* Second query: learners per course, via GROUP BY */
$summary = $conn->query(
    "SELECT course, COUNT(*) AS total
     FROM registrations
     GROUP BY course
     ORDER BY total DESC"
);

/* Third query: overall total, unaffected by the search filter */
$grandTotal = $conn->query("SELECT COUNT(*) AS c FROM registrations")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cybersecurity Skills Hub | Registrations</title>
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
    <h1>Enrolled Learners</h1>
    <p>Records retrieved live from the <code>registrations</code> table in the
      <code>cyberskillshub</code> MySQL database.</p>
  </section>

  <section class="section">
    <div class="container">

      <!-- Toolbar (Flexbox): search on one side, count on the other -->
      <div class="toolbar">
        <form class="search-form" method="get" action="view_registrations.php">
          <input type="text" name="search" value="<?= e($search) ?>"
                 placeholder="Search name, email or course code"
                 aria-label="Search registrations">
          <button type="submit" class="btn">Search</button>
          <?php if ($search !== ''): ?>
            <a class="btn btn-outline" href="view_registrations.php">Clear</a>
          <?php endif; ?>
        </form>

        <p style="margin:0">
          Showing <strong><?= $rowCount ?></strong>
          of <strong><?= e($grandTotal) ?></strong>
          record<?= (int) $grandTotal === 1 ? '' : 's' ?>
          <?= $search !== '' ? 'matching &ldquo;' . e($search) . '&rdquo;' : '' ?>
        </p>
      </div>

      <!-- Per-course totals, from the GROUP BY query -->
      <?php if ($summary && $summary->num_rows > 0): ?>
        <div class="stats">
          <?php while ($row = $summary->fetch_assoc()): ?>
            <div class="stat">
              <div class="num"><?= e($row['total']) ?></div>
              <div class="lbl"><?= e(courseName($row['course'])) ?></div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>

      <!-- The retrieved records -->
      <?php if ($rowCount > 0): ?>
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Level</th>
                <th>Course</th>
                <th>Goals</th>
                <th>Registered</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td><?= e($row['id']) ?></td>
                  <td><?= e($row['fullname']) ?></td>
                  <td><?= e($row['email']) ?></td>
                  <td><?= e($row['phone']) ?></td>
                  <td><span class="pill <?= e($row['level']) ?>"><?= e($row['level']) ?></span></td>
                  <td><?= e(courseName($row['course'])) ?></td>
                  <td>
                    <?php if ($row['goals'] !== null && $row['goals'] !== ''): ?>
                      <?= e(mb_strimwidth($row['goals'], 0, 60, '…')) ?>
                    <?php else: ?>
                      <em>&mdash;</em>
                    <?php endif; ?>
                  </td>
                  <td><?= e(date('d M Y, H:i', strtotime($row['created_at']))) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>

      <?php else: ?>
        <div class="empty-state">
          <h2>Nothing to show yet</h2>
          <p>
            <?= $search !== ''
                  ? 'No registration matches that search. Clear it to see every record.'
                  : 'No one has enrolled yet. Submit the form to create the first record.' ?>
          </p>
          <p><a class="btn" href="register.html">Open the registration form</a></p>
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
$stmt->close();
$conn->close();