<?php
require_once __DIR__ . '/functions.php';
createDatabaseSchema();

$errors = [];
$successMessage = null;
$email = $_GET['email'] ?? $_POST['email'] ?? '';
$code = $_POST['code'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $code = trim($_POST['code'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$email) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($code === '') {
        $errors[] = 'Please enter your reset code.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $user = findUserByEmail($email);
        if (!$user || !verifyPasswordResetCode($user['id'], $code)) {
            $errors[] = 'The reset code or email address is incorrect.';
        }
    }

    if (empty($errors)) {
        resetUserPassword($user['id'], password_hash($password, PASSWORD_DEFAULT));
        $successMessage = 'Your password has been reset successfully. You can now log in with your new password.';
        $email = '';
        $code = '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | Trusted Bank</title>
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
        <a href="login.php">Sign In</a>
        <a href="index.php">Home</a>
      </div>
    </nav>
  </div>
  <section class="section">
    <div class="container">
      <h2>Reset your password</h2>
      <p>Enter the code you received and choose a new password for your account.</p>

      <?php if ($errors): ?>
        <div class="form-alert form-alert-error">
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($successMessage): ?>
        <div class="form-alert" style="border-color: rgba(52, 211, 153, 0.25); background: rgba(52, 211, 153, 0.08); color: #c0f4d4;">
          <?= htmlspecialchars($successMessage) ?>
        </div>
      <?php endif; ?>

      <?php if (!$successMessage): ?>
        <form method="post" class="auth-form">
          <label>
            Email Address
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
          </label>
          <label>
            Reset Code
            <input type="text" name="code" value="<?= htmlspecialchars($code) ?>" required>
          </label>
          <label>
            New Password
            <input type="password" name="password" required>
          </label>
          <label>
            Confirm New Password
            <input type="password" name="confirm_password" required>
          </label>
          <button class="btn btn-primary" type="submit">Reset password</button>
        </form>
      <?php endif; ?>

      <p class="small-note">Back to <a href="login.php">sign in</a>.</p>
    </div>
  </section>
  <script src="assets/js/script.js"></script>
</body>
</html>
