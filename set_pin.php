<?php
require_once __DIR__ . '/functions.php';
ensureSessionStarted();
createDatabaseSchema();

if (empty($_SESSION['user'])) {
    redirect('login.php');
}

$user = findUserById((int)$_SESSION['user']['id']);
if (!$user) {
    redirect('logout.php');
}

if (!empty($user['pin_hash'])) {
    loginUser($user);
    redirect('dashboard.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin'] ?? '');
    $confirmPin = trim($_POST['confirm_pin'] ?? '');

    if (!preg_match('/^\d{4}$/', $pin)) {
        $errors[] = 'PIN must be a 4-digit number.';
    }
    if ($pin !== $confirmPin) {
        $errors[] = 'PIN confirmation does not match.';
    }

    if (empty($errors)) {
        setUserPin($user['id'], $pin);
        $user = findUserById($user['id']);
        loginUser($user);
        redirect('dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Set Transfer PIN | Trusted Bank</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
    <nav class="topnav">
      <a class="brand-link" href="index.php">
        <span class="logo-mark" aria-hidden="true">
          <svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Trusted Bank logo">
            <defs>
              <linearGradient id="logoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#42a5f5" />
                <stop offset="100%" stop-color="#70d6ff" />
              </linearGradient>
            </defs>
            <rect x="8" y="8" width="64" height="64" rx="18" fill="rgba(255,255,255,0.08)" />
            <path d="M22 24 H58 V34 H46 V60 H34 V34 H22 Z" fill="url(#logoGradient)" />
          </svg>
        </span>
        <span class="brand-title">Trusted Bank</span>
      </a>
      <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
      </div>
    </nav>
  </div>
  <section class="section">
    <div class="container">
      <h2>Set your transfer PIN</h2>
      <p>Enter a secure 4-digit PIN to authorize transfers from your account.</p>

      <?php if ($errors): ?>
        <div class="form-alert form-alert-error">
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" class="auth-form">
        <label>
          PIN
          <input type="password" name="pin" maxlength="4" pattern="\d{4}" required placeholder="0000">
        </label>
        <label>
          Confirm PIN
          <input type="password" name="confirm_pin" maxlength="4" pattern="\d{4}" required placeholder="0000">
        </label>
        <button class="btn btn-primary" type="submit">Set PIN</button>
      </form>

      <p class="small-note">Need help? <a href="contact.php">Contact support</a>.</p>
    </div>
  </section>
  <script src="assets/js/script.js"></script>
</body>
</html>
