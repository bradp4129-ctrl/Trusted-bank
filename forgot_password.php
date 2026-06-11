<?php
require_once __DIR__ . '/functions.php';
createDatabaseSchema();

$errors = [];
$infoMessage = null;
$resetCode = null;
$email = $_POST['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $user = findUserByEmail($email);
        if ($user) {
            $resetCode = generateVerificationCode();
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);
            setPasswordResetCode($user['id'], $resetCode, $expiresAt);
            $emailSent = sendPasswordResetEmail($user, $resetCode);
            $infoMessage = $emailSent
                ? 'If an account exists for that email, a password reset code has been sent.'
                : 'If an account exists for that email, a reset code has been generated. Use the demo code shown below.';
        } else {
            $infoMessage = 'If an account exists for that email, a password reset code has been sent.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | Trusted Bank</title>
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
      <p>Enter the email address for your account to receive a password reset code.</p>

      <?php if ($errors): ?>
        <div class="form-alert form-alert-error">
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($infoMessage): ?>
        <div class="form-alert" style="border-color: rgba(52, 211, 153, 0.25); background: rgba(52, 211, 153, 0.08); color: #c0f4d4;">
          <?= htmlspecialchars($infoMessage) ?>
        </div>
      <?php endif; ?>

      <form method="post" class="auth-form">
        <label>
          Email Address
          <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
        </label>
        <button class="btn btn-primary" type="submit">Send reset code</button>
      </form>

      <?php if ($resetCode): ?>
        <div class="contact-card" style="margin-top: 1.5rem;">
          <p><strong>Demo reset code:</strong> <?= htmlspecialchars($resetCode) ?></p>
          <p>This code is valid for 60 minutes. Use it on the password reset page below.</p>
          <p style="margin-top:0.75rem; color:#f9fafb; opacity:0.8;">If SMTP is not configured, this demo code can be used instead of an email.</p>
          <p><a href="reset_password.php?email=<?= urlencode($email) ?>">Continue to password reset</a></p>
        </div>
      <?php endif; ?>

      <p class="small-note">Remembered your password? <a href="login.php">Sign in</a>.</p>
    </div>
  </section>
  <script src="assets/js/script.js"></script>
</body>
</html>
