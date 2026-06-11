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
$successMessage = '';
$receipt = null;
$selectedType = $_POST['type'] ?? $_GET['type'] ?? '';

$transactions = getTransactions($user['id'], 25);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'perform_transaction';

    if ($action === 'set_pin') {
        $pin = trim($_POST['pin'] ?? '');
        $confirmPin = trim($_POST['confirm_pin'] ?? '');

        if (!preg_match('/^\d{4}$/', $pin)) {
            $errors[] = 'PIN must be a 4-digit number.';
        }
        if ($pin !== $confirmPin) {
            $errors[] = 'PIN entries do not match.';
        }

        if (empty($errors)) {
            try {
                setUserPin($user['id'], $pin);
                $successMessage = 'PIN set successfully. You can now transfer funds.';
                $user = findUserById($user['id']);
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
    } else {
        $type = $selectedType;
        $amount = floatval($_POST['amount'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        $recipientEmail = trim($_POST['recipient_email'] ?? '');
        $pin = trim($_POST['pin'] ?? '');
        $recipientUser = null;

        if (!in_array($type, ['deposit', 'withdrawal', 'transfer'], true)) {
            $errors[] = 'Please select deposit, withdrawal, or transfer.';
        }
        if ($amount <= 0) {
            $errors[] = 'Please enter a valid amount greater than zero.';
        }

        if ($type === 'transfer') {
            if (empty($user['pin_hash'])) {
                $errors[] = 'You need to set a transfer PIN before sending money.';
            }
            if ($pin === '') {
                $errors[] = 'Please enter your 4-digit transfer PIN to authorize this transaction.';
            }

            if (!$recipientEmail || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid recipient email address for transfers.';
            } elseif ($recipientEmail === $user['email']) {
                $errors[] = 'You cannot transfer funds to yourself.';
            } else {
                $recipientUser = findUserByEmail($recipientEmail);
                if (!$recipientUser) {
                    $errors[] = 'Recipient account was not found.';
                }
            }
        }

        if ($type !== 'transfer') {
            $pin = '';
        }

        if (empty($errors) && $type === 'transfer' && !verifyUserPin($user, $pin)) {
            $errors[] = 'The PIN you entered is incorrect.';
        }

        if (empty($errors)) {
            try {
                addTransaction($user['id'], $type, $amount, $description ?: null, $recipientUser['id'] ?? null);
                if ($type === 'transfer') {
                    $successMessage = 'Transfer completed successfully.';
                } else {
                    $successMessage = 'Transaction completed successfully.';
                }
                $user = findUserById($user['id']);
                $transactions = getTransactions($user['id'], 10);
                $receipt = [
                    'type' => $type,
                    'amount' => $amount,
                    'description' => $description ?: ($type === 'transfer' ? 'Transfer completed' : 'Transaction completed'),
                    'recipient' => $recipientUser['email'] ?? null,
                    'date' => date('M j, Y g:i A'),
                    'balance' => $user['balance'],
                ];
                if (empty($successMessage) && $receipt) {
                    $successMessage = 'Your transaction completed successfully.';
                }

                $transactions = getTransactions($user['id'], 10);
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
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
  <title>Transactions | Trusted Bank</title>
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
          <a href="dashboard.php">Dashboard</a>
          <a href="index.php">Home</a>
          <a href="logout.php">Logout</a>
        </div>
      </nav>

      <div class="dashboard-header">
        <div>
          <span class="eyebrow">Transactions</span>
          <h1>Manage your funds</h1>
          <p>Deposit, withdraw, or transfer funds safely. Use deposit to add money to your account balance.</p>
        </div>
        <div class="dashboard-actions">
          <a class="btn btn-secondary" href="dashboard.php">Back to dashboard</a>
          <a class="btn btn-primary" href="logout.php">Logout</a>
        </div>
      </div>

      <div class="transaction-grid">
        <div>
          <?php if ($errors): ?>
            <div class="form-alert form-alert-error">
              <ul>
                <?php foreach ($errors as $error): ?>
                  <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <?php if ($successMessage || $receipt): ?>
            <article id="transaction-result" class="card receipt-card" style="margin-bottom: 1.5rem;">
              <?php if ($successMessage): ?>
                <div class="form-alert form-alert-success" style="margin-bottom:1rem;">
                  <?= htmlspecialchars($successMessage) ?>
                </div>
              <?php endif; ?>

              <?php if ($receipt): ?>
                <div class="receipt-status receipt-success">
                  <span class="status-ring" aria-hidden="true">
                    <svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                      <circle cx="24" cy="24" r="22" fill="rgba(52, 211, 153, 0.16)" stroke="rgba(52, 211, 153, 0.55)" stroke-width="3" />
                      <path d="M16 25.5l5 5 11-12" fill="none" stroke="#34d399" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                  </span>
                  <div class="status-text">
                    <p class="status-label">Transaction successful</p>
                    <p class="status-subtext">Your receipt is shown below.</p>
                  </div>
                </div>
                <div class="receipt-header">
                  <h3>Transaction receipt</h3>
                  <p>Keep this record for your reference.</p>
                </div>
                <div class="receipt-details">
                  <div><strong>Type</strong><span><?= htmlspecialchars(ucfirst($receipt['type'])) ?></span></div>
                  <?php if ($receipt['recipient']): ?>
                    <div><strong>Recipient</strong><span><?= htmlspecialchars($receipt['recipient']) ?></span></div>
                  <?php endif; ?>
                  <div><strong>Amount</strong><span>$<?= number_format($receipt['amount'], 2) ?></span></div>
                  <div><strong>Description</strong><span><?= htmlspecialchars($receipt['description']) ?></span></div>
                  <div><strong>Date</strong><span><?= htmlspecialchars($receipt['date']) ?></span></div>
                  <div><strong>New balance</strong><span>$<?= number_format($receipt['balance'], 2) ?></span></div>
                </div>
              <?php endif; ?>
            </article>
          <?php endif; ?>

          <?php if (empty($user['pin_hash'])): ?>
            <article class="card card-summary" style="margin-bottom: 1.5rem;">
              <h3>Set your transfer PIN</h3>
              <p>This account does not have a PIN set yet. You must create a 4-digit PIN before you can transfer funds.</p>
              <form method="post" class="auth-form">
                <input type="hidden" name="action" value="set_pin">
                <label>
                  PIN
                  <input type="password" name="pin" pattern="\d{4}" maxlength="4" required placeholder="0000">
                </label>
                <label>
                  Confirm PIN
                  <input type="password" name="confirm_pin" pattern="\d{4}" maxlength="4" required placeholder="0000">
                </label>
                <button class="btn btn-primary" type="submit">Save PIN</button>
              </form>
            </article>
          <?php endif; ?>

          <form method="post" class="auth-form" data-transaction-form data-skip-global-submit>
            <input type="hidden" name="action" value="perform_transaction">
            <label>
              Transaction type
              <select name="type" required>
                <option value="">Select type</option>
                <option value="deposit" <?= $selectedType === 'deposit' ? 'selected' : '' ?>>Deposit</option>
                <option value="withdrawal" <?= $selectedType === 'withdrawal' ? 'selected' : '' ?>>Withdrawal</option>
                <option value="transfer" <?= $selectedType === 'transfer' ? 'selected' : '' ?>>Transfer</option>
              </select>
              <span id="transfer-pin-note" style="display: <?= $selectedType === 'transfer' ? 'block' : 'none' ?>; margin-top:0.75rem; color: var(--muted); font-size: 0.95rem;">Transfer will ask for your 4-digit PIN in a secure popup.</span>
            </label>
            <label id="recipient-field" style="<?= $selectedType === 'transfer' ? '' : 'display:none;' ?>">
              Recipient email
              <input type="email" name="recipient_email" value="<?= htmlspecialchars($_POST['recipient_email'] ?? '') ?>" <?= $selectedType === 'transfer' ? 'required' : '' ?> placeholder="recipient@example.com">
            </label>
            <input type="hidden" name="pin" id="transfer-pin-hidden" value="">
            <label>
              Amount
              <input type="number" name="amount" step="0.01" min="0.01" value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>" required>
            </label>
            <label>
              Description
              <input type="text" name="description" value="<?= htmlspecialchars($_POST['description'] ?? '') ?>">
            </label>
            <button class="btn btn-primary" type="button" id="transaction-submit-button">Submit transaction</button>
          </form>

          <div id="transfer-pin-modal" class="modal-overlay" style="display:none;">
            <div class="modal-card">
              <h3>Enter transfer PIN</h3>
              <p>Enter your 4-digit transfer PIN to authorize the transaction securely.</p>
              <form id="transfer-pin-form" class="auth-form" data-skip-global-submit>
                <label>
                  4-digit PIN
                  <input type="password" name="transfer_pin" id="transfer-pin-input" pattern="\d{4}" maxlength="4" required placeholder="0000">
                </label>
                <div class="buttons" style="justify-content:flex-end;">
                  <button class="btn btn-secondary" type="button" id="cancel-transfer-pin">Cancel</button>
                  <button class="btn btn-primary" type="submit">Confirm transfer</button>
                </div>
                <p class="small-note" style="margin-top: 1rem;">If the PIN is incorrect, the transfer will be rejected and you will be prompted again.</p>
              </form>
            </div>
          </div>


        </div>

        <aside class="card card-summary">
          <h3>Account summary</h3>
          <p><strong>Balance:</strong> $<?= number_format($user['balance'], 2) ?></p>
          <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        </aside>
      </div>

      <div class="section-features" style="padding-top: 2rem; padding-bottom: 0;">
        <div class="card card-table">
          <div class="section-header">
            <div>
              <h2>All transactions</h2>
              <p>Review your latest account activity in a compact, table-style view.</p>
            </div>
          </div>

          <?php if ($transactions): ?>
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
                  <?php foreach ($transactions as $txn): ?>
                    <tr>
                      <td><?= ucfirst(htmlspecialchars($txn['type'])) ?></td>
                      <td>$<?= number_format($txn['amount'], 2) ?></td>
                      <td><?= htmlspecialchars($txn['description'] ?? '—') ?></td>
                      <td><?= date('M j, Y g:i A', strtotime($txn['created_at'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="contact-card">
              <p>You do not have any transactions yet. Use the form above to create your first deposit or withdrawal.</p>
            </div>
          <?php endif; ?>

          <div class="buttons" style="justify-content:flex-start; margin-top:1.5rem;">
            <a class="btn btn-primary" href="transactions.php">Refresh</a>
          </div>
        </div>
      </div>
    </div>
  </section>
  <script src="assets/js/script.js"></script>
</body>
</html>
