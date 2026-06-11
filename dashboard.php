<?php
require_once __DIR__ . '/functions.php';
requireLogin();
$current = currentUser();
$user = findUserById((int)$current['id']);

if (!$user) {
    redirect('logout.php');
}
$recentTransactions = getTransactions($user['id'], 4);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | Trusted Bank</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <section class="section">
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
          <a href="transactions.php">Transactions</a>
          <a href="profile.php">Profile</a>
          <?php if (!empty($user['is_admin'])): ?>
            <a href="admin.php">Admin</a>
          <?php endif; ?>
          <a href="logout.php">Logout</a>
        </div>
      </nav>

      <div class="dashboard-header">
        <div class="dashboard-user-card">
          <div>
            <span class="eyebrow">Dashboard</span>
            <h1>Welcome back, <?= htmlspecialchars($user['name']) ?></h1>
            <p>Review your account summary, latest activity, and quick banking actions.</p>
          </div>
        </div>
        <div class="dashboard-actions">
          <a class="btn btn-secondary" href="transactions.php?type=deposit">Deposit</a>
          <a class="btn btn-secondary" href="transactions.php?type=withdrawal">Withdraw</a>
          <a class="btn btn-secondary" href="transactions.php?type=transfer">Transfer</a>
          <a class="btn btn-primary" href="logout.php">Logout</a>
        </div>
      </div>

      <div class="quick-actions">
        <a class="quick-action" href="transactions.php?type=deposit">
          <span class="quick-action-icon">+</span>
          <span>Deposit</span>
        </a>
        <a class="quick-action" href="transactions.php?type=withdrawal">
          <span class="quick-action-icon">-</span>
          <span>Withdraw</span>
        </a>
        <a class="quick-action" href="transactions.php?type=transfer">
          <span class="quick-action-icon">↔</span>
          <span>Transfer</span>
        </a>
        <a class="quick-action" href="profile.php">
          <span class="quick-action-icon">i</span>
          <span>Profile</span>
        </a>
      </div>

      <div class="cards dashboard-summary">
        <article class="card card-strong">
          <div class="dashboard-balance-top">
            <div>
              <h3>Available balance</h3>
            </div>
            <button id="toggleBalanceBtn" class="btn btn-secondary icon-button" type="button" aria-label="Hide balance">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 6.5c3 0 5.5 1.9 6.5 4.5-1 2.6-3.5 4.5-6.5 4.5s-5.5-1.9-6.5-4.5C6.5 8.4 9 6.5 12 6.5m0-2C7.2 4.5 3.4 7.6 2 12c1.4 4.4 5.2 7.5 10 7.5s8.6-3.1 10-7.5C20.6 7.6 16.8 4.5 12 4.5zm0 5.5a2 2 0 1 1 0 4 2 2 0 0 1 0-4z"/></svg>
            </button>
          </div>
          <p id="balanceValue" class="card-amount balance-value" data-amount="$<?= number_format($user['balance'], 2) ?>">$<?= number_format($user['balance'], 2) ?></p>
        </article>
        <article class="card">
          <h3>Account email</h3>
          <p><?= htmlspecialchars($user['email']) ?></p>
        </article>
        <article class="card">
          <h3>Member since</h3>
          <p><?= date('F j, Y', strtotime($user['created_at'])) ?></p>
        </article>
      </div>

      <article class="card card-table" style="margin-top: 2rem;">
        <div class="section-header">
          <div>
            <h2>Recent activity</h2>
            <p>Review your latest deposits, withdrawals, and transfers in one place.</p>
          </div>
        </div>

        <?php if ($recentTransactions): ?>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Type</th>
                  <th>Amount</th>
                  <th>Description</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentTransactions as $transaction): ?>
                  <tr>
                    <td><?= ucfirst(htmlspecialchars($transaction['type'])) ?></td>
                    <td>$<?= number_format($transaction['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($transaction['description'] ?? '—') ?></td>
                    <td><?= date('M j, Y g:i A', strtotime($transaction['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="contact-card">
            <p>No transactions yet. Start by making a deposit or withdrawal.</p>
          </div>
        <?php endif; ?>

        <div class="buttons" style="justify-content:flex-start; margin-top:1.5rem;">
          <a class="btn btn-primary" href="transactions.php">View all transactions</a>
        </div>
      </article>
    </div>
  </section>
  <script src="assets/js/script.js"></script>
</body>
</html>
