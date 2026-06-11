<?php
require_once __DIR__ . '/functions.php';
createDatabaseSchema();
requireAdmin();
$current = currentUser();
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_user') {
        $name = sanitize($_POST['name'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $balance = floatval($_POST['balance'] ?? 0);

        $pin = $_POST['pin'] ?? '';
        $confirmPin = $_POST['confirm_pin'] ?? '';

        if ($name === '') {
            $errors[] = 'Please enter the user name.';
        }
        if (!$email) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (!preg_match('/^\d{4}$/', $pin)) {
            $errors[] = 'PIN must be a 4-digit number.';
        }
        if ($pin !== $confirmPin) {
            $errors[] = 'PIN entries do not match.';
        }
        if ($email && findUserByEmail($email)) {
            $errors[] = 'A user with that email already exists.';
        }

        if (empty($errors)) {
            try {
                createUser($name, $email, password_hash($password, PASSWORD_DEFAULT), false, $balance, null, password_hash($pin, PASSWORD_DEFAULT));
                $successMessage = 'New customer account created successfully.';
                $_POST = [];
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
    } elseif ($action === 'delete_user') {
        $userId = intval($_POST['user_id'] ?? 0);

        if (!$userId) {
            $errors[] = 'Invalid user selected.';
        } elseif ($userId === $current['id']) {
            $errors[] = 'You cannot delete your own admin account.';
        } else {
            $targetUser = findUserById($userId);
            if (!$targetUser) {
                $errors[] = 'Selected user not found.';
            } elseif (!empty($targetUser['is_admin'])) {
                $errors[] = 'Admin accounts cannot be deleted from this dashboard.';
            } else {
                try {
                    deleteUserById($userId);
                    $successMessage = 'User account deleted successfully.';
                } catch (Throwable $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }
    } elseif ($action === 'update_user') {
        $userId = intval($_POST['user_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $pin = $_POST['pin'] ?? '';
        $confirmPin = $_POST['confirm_pin'] ?? '';

        if (!$userId) {
            $errors[] = 'Invalid user selected.';
        } elseif ($userId === $current['id']) {
            $errors[] = 'You cannot edit your own admin account here.';
        } else {
            $targetUser = findUserById($userId);
            if (!$targetUser) {
                $errors[] = 'Selected user not found.';
            } elseif (!empty($targetUser['is_admin'])) {
                $errors[] = 'Admin accounts cannot be edited from this dashboard.';
            }
        }

        if (empty($errors)) {
            if ($name === '') {
                $errors[] = 'Please enter the user name.';
            }
            if (!$email) {
                $errors[] = 'Please enter a valid email address.';
            }
            $existingUser = findUserByEmail($email);
            if ($existingUser && $existingUser['id'] !== $userId) {
                $errors[] = 'A different user already uses that email address.';
            }
            if ($pin !== '' || $confirmPin !== '') {
                if (!preg_match('/^\d{4}$/', $pin)) {
                    $errors[] = 'PIN must be a 4-digit number.';
                }
                if ($pin !== $confirmPin) {
                    $errors[] = 'PIN entries do not match.';
                }
            }
        }

        if (empty($errors)) {
            try {
                updateUserInfo($userId, $name, $email);
                if ($pin !== '') {
                    setUserPin($userId, $pin);
                    $successMessage = 'Customer details and PIN updated successfully.';
                } else {
                    $successMessage = 'Customer details updated successfully.';
                }
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
    } elseif ($action === 'adjust_balance') {
        $userId = intval($_POST['user_id'] ?? 0);
        $adjustType = $_POST['adjust_type'] ?? '';
        $amount = floatval($_POST['amount'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');

        if (!$userId) {
            $errors[] = 'Invalid user selected.';
        } elseif (!in_array($adjustType, ['credit', 'debit'], true)) {
            $errors[] = 'Please choose credit or debit.';
        } elseif ($amount <= 0) {
            $errors[] = 'Amount must be greater than zero.';
        }

        $targetUser = findUserById($userId);
        if (!$targetUser) {
            $errors[] = 'Selected user not found.';
        }

        if (empty($errors)) {
            try {
                if ($adjustType === 'credit') {
                    addTransaction($userId, 'deposit', $amount, $description ?: 'Admin credit');
                    $successMessage = 'Customer account credited successfully.';
                } else {
                    addTransaction($userId, 'withdrawal', $amount, $description ?: 'Admin debit');
                    $successMessage = 'Customer account debited successfully.';
                }
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_user'])) {
    $userId = intval($_GET['delete_user']);
    if (!$userId) {
        $errors[] = 'Invalid user selected for deletion.';
    } elseif ($userId === $current['id']) {
        $errors[] = 'You cannot delete your own admin account.';
    } else {
        $targetUser = findUserById($userId);
        if (!$targetUser) {
            $errors[] = 'Selected user not found.';
        } elseif (!empty($targetUser['is_admin'])) {
            $errors[] = 'Admin accounts cannot be deleted from this dashboard.';
        } else {
            try {
                deleteUserById($userId);
                $successMessage = 'User account deleted successfully.';
                redirect('admin.php');
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}

$userSearch = sanitize($_GET['user_search'] ?? '');
$editUserId = intval($_GET['edit_user'] ?? 0);
$editUser = $editUserId ? findUserById($editUserId) : null;
$users = $userSearch !== '' ? searchUsers($userSearch) : getAllUsers();
$transactions = getAllTransactions(25);
$totalBalance = array_sum(array_column($users, 'balance'));
$totalAdmins = count(array_filter($users, fn($user) => !empty($user['is_admin'])));
$activeUsers = count(array_filter($users, fn($user) => !empty($user['verified']) && !empty($user['pin_hash']) && empty($user['is_admin'])));
$pendingUsers = count(array_filter($users, fn($user) => empty($user['verified']) && empty($user['is_admin'])));
$totalUsers = count($users);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | Trusted Bank</title>
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
          <span class="brand-title">Trusted Bank Admin</span>
        </a>
        <div class="nav-links">
          <a href="dashboard.php">User dashboard</a>
          <a href="logout.php">Logout</a>
        </div>
      </nav>

      <div class="dashboard-header">
        <div>
          <span class="eyebrow">Admin</span>
          <h1>Admin dashboard</h1>
          <p>Only authorized admin accounts may access this page. Manage customers, review activity, and keep your system secure.</p>
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

      <div class="dashboard-actions" style="margin-top: 1.5rem;">
        <a class="btn btn-primary" href="#create-customer">Create customer</a>
        <a class="btn btn-secondary" href="#users">Manage users</a>
        <a class="btn btn-secondary" href="#transactions">Recent transactions</a>
      </div>

      <div class="cards admin-summary" style="margin-top: 2rem;">
        <article class="card card-strong">
          <h3>Total accounts</h3>
          <p class="card-amount"><?= $totalUsers ?> users</p>
        </article>
        <article class="card">
          <h3>Active customers</h3>
          <p><?= $activeUsers ?> users</p>
        </article>
        <article class="card">
          <h3>Pending verification</h3>
          <p><?= $pendingUsers ?> users</p>
        </article>
        <article class="card">
          <h3>Total balance</h3>
          <p>$<?= number_format($totalBalance, 2) ?></p>
        </article>
      </div>

      <section id="create-customer" class="admin-grid" style="margin-top: 2rem; gap: 1.5rem;">
        <article class="card">
          <h2>Create new customer account</h2>
          <p>Use this form to add a new customer without admin access.</p>
          <form method="post" class="auth-form">
            <input type="hidden" name="action" value="create_user">
            <label>
              Full name
              <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </label>
            <label>
              Email address
              <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </label>
            <label>
              Password
              <input type="password" name="password" required>
            </label>
            <label>
              Initial balance
              <input type="number" name="balance" step="0.01" min="0" value="<?= htmlspecialchars($_POST['balance'] ?? '0.00') ?>">
            </label>
            <label>
              4-digit PIN
              <input type="password" name="pin" pattern="\d{4}" maxlength="4" required placeholder="0000" value="<?= htmlspecialchars($_POST['pin'] ?? '') ?>">
            </label>
            <label>
              Confirm PIN
              <input type="password" name="confirm_pin" pattern="\d{4}" maxlength="4" required placeholder="0000">
            </label>
            <button class="btn btn-primary" type="submit">Create customer</button>
          </form>
        </article>

        <article class="card card-summary">
          <h3>Admin quick details</h3>
          <p><strong>Signed in as:</strong> <?= htmlspecialchars($current['email']) ?></p>
          <p><strong>Admin role:</strong> <?= !empty($current['is_admin']) ? 'Authorized admin' : 'Restricted' ?></p>
          <p><strong>Customer accounts:</strong> <?= $totalUsers - $totalAdmins ?></p>
          <p><strong>Last updated:</strong> <?= date('F j, Y, g:i A') ?></p>
        </article>
      </section>

      <?php if ($editUser && empty($editUser['is_admin'])): ?>
        <article class="card card-table" style="margin-top: 1.5rem;">
          <div class="section-header">
            <div>
              <h2>Edit customer</h2>
              <p>Update customer details or credit/debit their account.</p>
            </div>
          </div>

          <form method="post" class="auth-form">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
            <label>
              Full name
              <input type="text" name="name" value="<?= htmlspecialchars($editUser['name']) ?>" required>
            </label>
            <label>
              Email address
              <input type="email" name="email" value="<?= htmlspecialchars($editUser['email']) ?>" required>
            </label>
            <label>
              New 4-digit PIN
              <input type="password" name="pin" pattern="\d{4}" maxlength="4" placeholder="0000">
            </label>
            <label>
              Confirm PIN
              <input type="password" name="confirm_pin" pattern="\d{4}" maxlength="4" placeholder="0000">
            </label>
            <button class="btn btn-primary" type="submit">Save user details</button>
          </form>

          <form method="post" class="auth-form" style="margin-top: 1.5rem;">
            <input type="hidden" name="action" value="adjust_balance">
            <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
            <label>
              Adjustment type
              <select name="adjust_type" required>
                <option value="credit">Credit (add funds)</option>
                <option value="debit">Debit (remove funds)</option>
              </select>
            </label>
            <label>
              Amount
              <input type="number" name="amount" step="0.01" min="0.01" required>
            </label>
            <label>
              Description
              <input type="text" name="description" placeholder="Reason for adjustment">
            </label>
            <button class="btn btn-secondary" type="submit">Apply balance change</button>
          </form>
        </article>
      <?php endif; ?>

      <section class="admin-section-grid" style="margin-top: 2rem; gap: 1.5rem;">
        <article class="card card-table">
          <div class="section-header">
            <div>
              <h2>User management</h2>
              <p>Search users by name or email, and manage customer accounts in one place.</p>
            </div>
            <form method="get" class="admin-search">
            <label>
              Search users
              <input type="search" name="user_search" value="<?= htmlspecialchars($userSearch) ?>" placeholder="Name or email">
            </label>
          </form>
        </div>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Balance</th>
                <th>Role</th>
                <th>Joined</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($users): ?>
                <?php foreach ($users as $user): ?>
                  <tr>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>$<?= number_format($user['balance'], 2) ?></td>
                    <td>
                      <?php if (!empty($user['is_admin'])): ?>
                        <span class="status-tag admin">Admin</span>
                      <?php elseif (!empty($user['verified'])): ?>
                        <span class="status-tag active">Active</span>
                      <?php else: ?>
                        <span class="status-tag pending">Pending</span>
                      <?php endif; ?>
                    </td>
                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                    <td>
                      <?php if ($user['id'] === $current['id']): ?>
                        <span class="tag tag-current">Current admin</span>
                      <?php elseif (!empty($user['is_admin'])): ?>
                        <span class="tag tag-admin">Admin</span>
                      <?php else: ?>
                        <div class="table-actions">
                          <a class="btn btn-secondary" href="?user_search=<?= urlencode($userSearch) ?>&edit_user=<?= $user['id'] ?>">Edit</a>
                          <form method="post" action="admin.php" style="display:inline-block; margin:0 0 0 0.5rem;">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button class="btn btn-secondary" type="submit" name="action" value="delete_user" onclick="return confirm('Delete this user? This cannot be undone.');">Delete</button>
                          </form>
                        </div>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6">No users match your search.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </article>

      <article class="card card-table">
        <div class="section-header">
          <div>
            <h2>Recent transactions</h2>
            <p>Compact view of the latest activity across customer accounts.</p>
          </div>
        </div>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>User</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Description</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($transactions): ?>
                <?php foreach ($transactions as $txn): ?>
                  <tr>
                    <td><?= htmlspecialchars($txn['user_email']) ?></td>
                    <td><?= ucfirst(htmlspecialchars($txn['type'])) ?></td>
                    <td>$<?= number_format($txn['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($txn['description'] ?? '—') ?></td>
                    <td><?= date('M j, Y', strtotime($txn['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5">No recent transactions available.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </article>
      </section>

      <div class="buttons" style="margin-top:2rem; justify-content:flex-start;">
        <a class="btn btn-secondary" href="dashboard.php">Back to Dashboard</a>
        <a class="btn btn-primary" href="logout.php">Logout</a>
      </div>
    </div>
  </section>
  <script src="assets/js/script.js"></script>
</body>
</html>
