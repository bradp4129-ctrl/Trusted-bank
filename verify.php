<?php
require_once __DIR__ . '/functions.php';
ensureSessionStarted();
createDatabaseSchema();

$errors = [];
$infoMessage = $_SESSION['verify_info'] ?? null;
unset($_SESSION['verify_info']);
$pendingUserId = $_SESSION['pending_verification_user_id'] ?? null;
$verificationCodeHint = $_SESSION['pending_verification_code'] ?? null;
$user = $pendingUserId ? findUserById((int)$pendingUserId) : null;

if (!$user) {
    redirect('register.php');
}

if (!$verificationCodeHint) {
    $verificationCodeHint = getVerificationCode($user['id']);
}

if (isUserVerified($user)) {
    if (empty($user['pin_hash'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'is_admin' => $user['is_admin'] ?? 0,
        ];
        redirect('set_pin.php');
    }
    loginUser($user);
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'resend') {
        $newCode = generateVerificationCode();
        setVerificationCode($user['id'], $newCode);
        $_SESSION['pending_verification_code'] = $newCode;
        $verificationCodeHint = $newCode;
        $infoMessage = 'A new verification code has been generated and sent to your email.';
    } else {
        $code = trim($_POST['code'] ?? '');

        if ($code === '') {
            $errors[] = 'Please enter your verification code.';
        } elseif (!verifyUserCode($user['id'], $code)) {
            $errors[] = 'Incorrect verification code. Please try again.';
        } else {
            $user = findUserById($user['id']);
            unset($_SESSION['pending_verification_user_id'], $_SESSION['pending_verification_code']);
            if (empty($user['pin_hash'])) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'is_admin' => $user['is_admin'] ?? 0,
                ];
                redirect('set_pin.php');
            }
            loginUser($user);
            redirect('dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Account | Trusted Bank</title>
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
      <h2>Verify your account</h2>
      <p>Enter the verification code sent for your registration to access your dashboard.</p>

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
        <div class="form-alert" style="border-color: rgba(52, 211, 153, 0.25); background: rgba(52, 211, 153, 0.08); color: #c0f4d4; margin-bottom: 1rem;">
          <?= htmlspecialchars($infoMessage) ?>
        </div>
      <?php endif; ?>

      <form method="post" class="auth-form">
        <label>
          Verification code
          <input type="text" name="code" value="<?= htmlspecialchars($_POST['code'] ?? '') ?>" required>
        </label>
        <div class="buttons">
          <button class="btn btn-primary" type="submit">Verify account</button>
          <button class="btn btn-secondary" type="submit" name="action" value="resend">Resend code</button>
        </div>
      </form>

      <?php if ($verificationCodeHint): ?>
        <div class="contact-card" style="margin-top: 1.5rem;">
          <p><strong>Demo verification code:</strong> <?= htmlspecialchars($verificationCodeHint) ?></p>
          <p>This code is valid only for your current registration session.</p>
          <p style="margin-top:0.75rem; color:#f9fafb; opacity:0.8;">If email delivery is unavailable locally, use this demo code to complete verification.</p>
        </div>
      <?php endif; ?>

      <p class="small-note">Already verified? <a href="login.php">Sign in here</a>.</p>
    </div>
  </section>
  <script src="assets/js/script.js"></script>
</body>
</html>
