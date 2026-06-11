<?php
require_once __DIR__ . '/functions.php';
ensureSessionStarted();
createDatabaseSchema();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {
        $user = authenticateUser($email, $password);
        if (!$user) {
            $errors[] = 'Email or password is incorrect.';
        } elseif (!isUserVerified($user)) {
            $verificationCode = $user['verification_code'] ?: generateVerificationCode();
            setVerificationCode($user['id'], $verificationCode);
            $_SESSION['pending_verification_user_id'] = $user['id'];
            $_SESSION['pending_verification_code'] = $verificationCode;
            $emailSent = sendVerificationEmail($user, $verificationCode);
            $_SESSION['verify_info'] = $emailSent
                ? 'Your account is not verified yet. A verification code has been sent to your email.'
                : 'Your account is not verified yet. A verification code has been generated and is shown on the next page.';
            redirect('verify.php');
        } elseif (empty($user['pin_hash'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'is_admin' => $user['is_admin'] ?? 0,
            ];
            redirect('set_pin.php');
        } else {
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
  <title>Login | Trusted Bank</title>
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
        <a href="register.php">Register</a>
        <a href="index.php">Home</a>
      </div>
    </nav>
  </div>
  <section class="section">
    <div class="container">
      <h2>Welcome back to Trusted Bank</h2>
      <p>Log in to access your dashboard and bank securely online.</p>

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
          Email Address
          <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </label>
        <label>
          Password
          <input type="password" name="password" required>
        </label>
        <button class="btn btn-primary" type="submit">Log In</button>
      </form>

      <p class="small-note"><a href="forgot_password.php">Forgot your password?</a></p>
      <p class="small-note">Need an account? <a href="register.php">Register now</a>.</p>
    </div>
  </section>
  <script src="assets/js/script.js"></script>
</body>
</html>
