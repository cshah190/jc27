<?php
// subscribers.php — JAINA 2027 notify subscriber admin page
// Change this password before the website goes live.
$ADMIN_PASSWORD = 'JAINA2027-Dallas';

$cookieName = 'jaina2027_subscribers_admin';
$cookieHours = 12;
$csvFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'notify_subscribers.csv';
$error = '';

function admin_cookie_value(string $password): string
{
    return hash_hmac('sha256', 'jaina-2027-subscribers-admin', $password);
}

function is_admin_logged_in(string $cookieName, string $adminPassword): bool
{
    if (!empty($_SESSION['subscribers_admin'])) {
        return true;
    }

    $expected = admin_cookie_value($adminPassword);
    $actual = (string)($_COOKIE[$cookieName] ?? '');
    return $actual !== '' && hash_equals($expected, $actual);
}

// Start session when available, but do not depend only on it.
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        @session_destroy();
    }
    setcookie($cookieName, '', time() - 3600, '/', '', !empty($_SERVER['HTTPS']), true);
    header('Location: subscribers.php');
    exit;
}

$isLoggedIn = is_admin_logged_in($cookieName, $ADMIN_PASSWORD);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim((string)($_POST['password'] ?? ''));

    if (hash_equals($ADMIN_PASSWORD, $password)) {
        $_SESSION['subscribers_admin'] = true;
        $cookieValue = admin_cookie_value($ADMIN_PASSWORD);
        setcookie($cookieName, $cookieValue, [
            'expires' => time() + ($cookieHours * 3600),
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        // Do not redirect after login. Some shared hosts have session/cookie timing issues,
        // so we show the list immediately on the successful POST request.
        $isLoggedIn = true;
    } else {
        $error = 'Incorrect password.';
        $isLoggedIn = false;
    }
}

function readSubscribers(string $csvFile): array
{
    if (!file_exists($csvFile)) {
        return [];
    }

    $rows = [];
    $handle = fopen($csvFile, 'r');
    if ($handle === false) {
        return [];
    }

    $header = fgetcsv($handle);
    while (($row = fgetcsv($handle)) !== false) {
        $rows[] = [
            'created_at' => $row[0] ?? '',
            'email' => $row[1] ?? '',
        ];
    }
    fclose($handle);

    return array_reverse($rows);
}

if ($isLoggedIn && isset($_GET['download'])) {
    if (!file_exists($csvFile)) {
        http_response_code(404);
        echo 'CSV file not found.';
        exit;
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="jaina_2027_notify_subscribers.csv"');
    header('Content-Length: ' . filesize($csvFile));
    readfile($csvFile);
    exit;
}

$subscribers = $isLoggedIn ? readSubscribers($csvFile) : [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Notify Subscribers — JAINA 2027</title>
  <link rel="icon" href="assets/images/favicon-32.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="admin-page">
  <main class="container">
    <div class="admin-card mx-auto" style="max-width: 980px;">
      <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-between mb-4">
        <div>
          <a href="index.html" class="d-inline-flex align-items-center gap-2 mb-3"><img src="assets/images/jaina_logo.png" alt="JAINA 2027" style="width:70px;height:auto;"> <span>Back to website</span></a>
          <div class="section-eyebrow mb-2">JAINA 2027</div>
          <h1 class="mb-0">Notify Subscribers</h1>
        </div>
        <?php if ($isLoggedIn): ?>
          <div class="d-flex flex-wrap gap-2">
            <a class="btn-jaina" href="subscribers.php?download=1">Download CSV</a>
            <a class="btn-jaina-outline" href="subscribers.php?logout=1">Log out</a>
          </div>
        <?php endif; ?>
      </div>

      <?php if (!$isLoggedIn): ?>
        <p class="text-muted-strong">Enter the admin password to view and download the notification email list.</p>
        <?php if ($error !== ''): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <form method="post" class="row g-3" autocomplete="off" action="subscribers.php">
          <div class="col-md-8">
            <label class="form-label" for="password">Password</label>
            <input class="form-control form-control-lg" type="password" name="password" id="password" required autofocus>
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <button class="btn-jaina w-100" type="submit">View List</button>
          </div>
        </form>
      <?php else: ?>
        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
          <p class="mb-0 text-muted-strong"><strong><?php echo count($subscribers); ?></strong> email<?php echo count($subscribers) === 1 ? '' : 's'; ?> collected.</p>
          <p class="mb-0 text-muted-strong small">CSV location remains protected in <code>/data/notify_subscribers.csv</code>.</p>
        </div>

        <?php if (count($subscribers) === 0): ?>
          <div class="alert alert-info mb-0">No notification sign-ups yet.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table subscriber-table align-middle">
              <thead>
                <tr>
                  <th style="width:70px;">#</th>
                  <th>Email</th>
                  <th style="width:260px;">Submitted</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($subscribers as $index => $subscriber): ?>
                  <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($subscriber['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($subscriber['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
