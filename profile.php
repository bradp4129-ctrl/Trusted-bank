<?php
require_once __DIR__ . '/functions.php';
createDatabaseSchema();
requireLogin();

$current = currentUser();
$user = findUserById((int)$current['id']);
if (!$user) {
    redirect('logout.php');
}

$errors = [];
$successMessage = null;
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'update_info') {
        $name = sanitize($_POST['name'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

        if ($name === '') {
            $errors[] = 'Please enter your full name.';
        }
        if (!$email) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (empty($errors)) {
            $existingUser = findUserByEmail($email);
            if ($existingUser && $existingUser['id'] !== $user['id']) {
                $errors[] = 'Another account already uses that email address.';
            }
        }

        if (empty($errors)) {
            updateUserInfo($user['id'], $name, $email);
            $successMessage = 'Profile updated successfully.';
            $user = findUserById($user['id']);
            loginUser($user);
        }
    } elseif ($action === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($currentPassword === '') {
            $errors[] = 'Please enter your current password.';
        }
        if (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        }
        if (!password_verify($currentPassword, $user['password_hash'])) {
            $errors[] = 'The current password is incorrect.';
        }

        if (empty($errors)) {
            updateUserPassword($user['id'], password_hash($newPassword, PASSWORD_DEFAULT));
            $successMessage = 'Password changed successfully.';
            $user = findUserById($user['id']);
        }
    } elseif ($action === 'update_pin') {
        $pin = trim($_POST['pin'] ?? '');
        $confirmPin = trim($_POST['confirm_pin'] ?? '');

        if (!preg_match('/^\d{4}$/', $pin)) {
            $errors[] = 'PIN must be a 4-digit number.';
        }
        if ($pin !== $confirmPin) {
            $errors[] = 'PIN entries do not match.';
        }

        if (empty($errors)) {
            setUserPin($user['id'], $pin);
            $successMessage = 'Transfer PIN updated successfully.';
            $user = findUserById($user['id']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile | Trusted Bank</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <section class="section">
    <div class="container">
      <nav class="topnav">
        <a class="brand-link" href="dashboard.php">
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
          <a href="transactions.php">Transactions</a>
          <a href="logout.php">Logout</a>
        </div>
      </nav>

      <div class="dashboard-header">
        <div>
          <span class="eyebrow">Profile</span>
          <h1>Your account settings</h1>
          <p>Update your name, email, password, or transfer PIN from one place.</p>
        </div>
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

      <?php if ($successMessage): ?>
        <div class="form-alert" style="border-color: rgba(52, 211, 153, 0.25); background: rgba(52, 211, 153, 0.08); color: #c0f4d4;">
          <?= htmlspecialchars($successMessage) ?>
        </div>
      <?php endif; ?>

      <div class="cards admin-summary" style="gap: 1rem; margin-top: 1.5rem;">
        <article class="card card-summary">
          <div class="profile-summary-top">
            <div>
              <span class="eyebrow">Account overview</span>
              <h2><?= htmlspecialchars($user['name']) ?></h2>
              <p><?= htmlspecialchars($user['email']) ?></p>
            </div>
            <span class="status-tag active">Verified</span>
          </div>
          <div class="profile-summary-details">
            <div class="summary-item">
              <span>Member since</span>
              <strong><?= date('F j, Y', strtotime($user['created_at'])) ?></strong>
            </div>
            <div class="summary-item">
              <span>Balance</span>
              <strong>$<?= number_format($user['balance'], 2) ?></strong>
            </div>
          </div>
        </article>

        <article class="card card-compact">
          <h2>Profile details</h2>
          <form method="post" class="auth-form">
            <input type="hidden" name="action" value="update_info">
            <label>
              Full name
              <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </label>
            <label>
              Email address
              <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </label>
            <button class="btn btn-primary" type="submit">Save profile</button>
          </form>
        </article>

        <article class="card card-compact">
          <h2>Change password</h2>
          <form method="post" class="auth-form">
            <input type="hidden" name="action" value="update_password">
            <label>
              Current password
              <input type="password" name="current_password" required>
            </label>
            <label>
              New password
              <input type="password" name="new_password" required>
            </label>
            <label>
              Confirm new password
              <input type="password" name="confirm_password" required>
            </label>
            <button class="btn btn-secondary" type="submit">Update password</button>
          </form>
        </article>

        <article class="card card-compact">
          <h2>Transfer PIN</h2>
          <p class="card-text"><?= empty($user['pin_hash']) ? 'You do not yet have a transfer PIN set.' : 'Update your 4-digit transfer PIN.' ?></p>
          <form method="post" class="auth-form">
            <input type="hidden" name="action" value="update_pin">
            <label>
              4-digit PIN
              <input type="password" name="pin" pattern="\d{4}" maxlength="4" required placeholder="0000">
            </label>
            <label>
              Confirm PIN
              <input type="password" name="confirm_pin" pattern="\d{4}" maxlength="4" required placeholder="0000">
            </label>
            <button class="btn btn-secondary" type="submit">Save PIN</button>
          </form>
        </article>
      </div>

      <div class="buttons profile-actions" style="margin-top: 2rem; justify-content:flex-start;">
        <a class="btn btn-secondary" href="dashboard.php">Back to dashboard</a>
      </div>
    </div>
  </section>
  <script src="assets/js/script.js"></script>
</body>
</html>
