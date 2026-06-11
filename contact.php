<?php
require_once __DIR__ . '/functions.php';
createDatabaseSchema();

$errors = [];
$infoMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $message = trim($_POST['message'] ?? '');

    if ($name === '') {
        $errors[] = 'Please enter your name.';
    }
    if (!$email) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($message === '') {
        $errors[] = 'Please enter your message.';
    }

    if (empty($errors)) {
        $subject = "Trusted Bank support request from {$name}";
        $body = "Name: {$name}\nEmail: {$email}\n\nMessage:\n{$message}";
        $sent = sendEmail(ADMIN_EMAIL, $subject, $body);

        if ($sent) {
            $infoMessage = 'Your message has been submitted. Our support team will reply as soon as possible.';
        } else {
            $infoMessage = 'Support email could not be sent from this local environment. Please contact support@trustedbank.local directly.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Support | Trusted Bank</title>
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
        <a href="index.php">Home</a>
        <a href="login.php">Sign In</a>
      </div>
    </nav>
  </div>

  <section class="section">
    <div class="container">
      <div class="hero-copy">
        <span class="eyebrow">Support</span>
        <h1>Contact Trusted Bank support</h1>
        <p>Need help with your account, verification, or transfers? Send us a message and we’ll respond quickly.</p>
      </div>

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
          Name
          <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </label>
        <label>
          Email Address
          <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </label>
        <label>
          Message
          <textarea name="message" rows="6" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        </label>
        <button class="btn btn-primary" type="submit">Send message</button>
      </form>

      <div class="contact-card" style="margin-top: 2rem;">
        <h3>Alternative support</h3>
        <p>If email is unavailable, send your request to <strong>support@trustedbank.local</strong> or visit this page later for account help.</p>
      </div>
    </div>
  </section>
  <script src="assets/js/script.js"></script>
</body>
</html>
