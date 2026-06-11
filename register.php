<?php
require_once __DIR__ . '/functions.php';
ensureSessionStarted();
createDatabaseSchema();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '') {
        $errors[] = 'Please enter your full name.';
    }
    if (!$email) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if ($email && findUserByEmail($email)) {
        $errors[] = 'An account with that email already exists.';
    }

    if (empty($errors)) {
        $verificationCode = generateVerificationCode();

        if (empty($errors)) {
            $userId = createUser($name, $email, password_hash($password, PASSWORD_DEFAULT), false, 0.0, $verificationCode, null, null);
            $user = findUserById($userId);

            if (!$user) {
                $errors[] = 'Could not complete registration. Please try again.';
            } else {
                $emailSent = sendVerificationEmail($user, $verificationCode);
                $_SESSION['pending_verification_user_id'] = $userId;
                $_SESSION['pending_verification_code'] = $verificationCode;
                $_SESSION['verify_info'] = $emailSent
                    ? 'A verification code has been sent to your email address.'
                    : 'A verification code has been generated. Use the demo code shown on the next page.';
                redirect('verify.php');
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
  <title>Register | Trusted Bank</title>
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
      <h2>Create your Trusted Bank account</h2>
      <p>Register now to begin managing payments, savings, and secure banking online.</p>

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
          Full Name
          <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </label>
        <label>
          Email Address
          <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </label>
        <label>
          Password
          <input type="password" name="password" required>
        </label>
        <label>
          Confirm Password
          <input type="password" name="confirm_password" required>
        </label>
        <button class="btn btn-primary" type="submit">Create Account</button>
      </form>

      <p class="small-note">Already have an account? <a href="login.php">Log in here</a>.</p>
    </div>
  </section>
  <script src="assets/js/script.js"></script>
</body>
</html>
