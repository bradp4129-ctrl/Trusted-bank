<?php

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'trusted_bank');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

define('ADMIN_EMAIL', 'admin@trustedbank.local');

define('ADMIN_NAME', 'Administrator');

define('ADMIN_PASSWORD', 'Admin1234!');

function getDbConnection(): PDO
{
    static $pdo;
    if ($pdo) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        exit('Database connection failed. Check your MySQL credentials in functions.php and ensure MySQL is running.');
    }

    return $pdo;
}

function getDb(): PDO
{
    $pdo = getDbConnection();
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET ' . DB_CHARSET . ' COLLATE ' . DB_COLLATION);
    $pdo->exec('USE `' . DB_NAME . '`');
    return $pdo;
}

function createDatabaseSchema(): void
{
    $db = getDb();

    $db->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(200) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            balance DECIMAL(12,2) NOT NULL DEFAULT 0,
            is_admin TINYINT(1) NOT NULL DEFAULT 0,
            verified TINYINT(1) NOT NULL DEFAULT 0,
            verification_code VARCHAR(10) DEFAULT NULL,
            pin_hash VARCHAR(255) DEFAULT NULL,
            profile_picture VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET . ' COLLATE=' . DB_COLLATION . ';'
    );

    $db->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) NOT NULL DEFAULT 0');
    $db->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS verified TINYINT(1) NOT NULL DEFAULT 0');
    $db->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS verification_code VARCHAR(10) DEFAULT NULL');
    $db->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS pin_hash VARCHAR(255) DEFAULT NULL');
    $db->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) DEFAULT NULL');
    $db->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS password_reset_code VARCHAR(10) DEFAULT NULL');
    $db->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS password_reset_expires_at DATETIME DEFAULT NULL');

    $adminCount = (int)$db->query('SELECT COUNT(*) FROM users WHERE is_admin = 1')->fetchColumn();
    if ($adminCount === 0) {
        $defaultPassword = password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT IGNORE INTO users (name, email, password_hash, is_admin, balance, verified) VALUES (?, ?, ?, 1, 0, 1)');
        $stmt->execute([ADMIN_NAME, ADMIN_EMAIL, $defaultPassword]);
    }

    $db->exec(
        'CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM("deposit", "withdrawal", "transfer") NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET . ' COLLATE=' . DB_COLLATION . ';'
    );
}

function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function ensureSessionStarted(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function redirect(string $path)
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    header("Location: $path");
    exit;
}

function saveProfilePicture(array $file): ?string
{
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes, true)) {
        return null;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        return null;
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = sprintf('uploads/profile_%s.%s', bin2hex(random_bytes(8)), $extension);
    $targetPath = __DIR__ . DIRECTORY_SEPARATOR . $filename;

    if (!is_dir(dirname($targetPath))) {
        mkdir(dirname($targetPath), 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return null;
    }

    return $filename;
}

function updateUserProfilePicture(int $userId, ?string $profilePicture): void
{
    $stmt = getDb()->prepare('UPDATE users SET profile_picture = ? WHERE id = ?');
    $stmt->execute([$profilePicture, $userId]);
}

function findUserByEmail(string $email)
{
    $stmt = getDb()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

function findUserById(int $id)
{
    $stmt = getDb()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function createUser(string $name, string $email, string $passwordHash, bool $isAdmin = false, float $balance = 0.0, ?string $verificationCode = null, ?string $pinHash = null, ?string $profilePicture = null): int
{
    $db = getDb();
    $stmt = $db->prepare('INSERT INTO users (name, email, password_hash, is_admin, balance, verification_code, pin_hash, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$name, $email, $passwordHash, $isAdmin ? 1 : 0, $balance, $verificationCode, $pinHash, $profilePicture]);
    return (int)$db->lastInsertId();
}

function verifyUserPin(array $user, string $pin): bool
{
    if (empty($user['pin_hash'])) {
        return false;
    }

    return password_verify($pin, $user['pin_hash']);
}

function setUserPin(int $userId, string $pin): void
{
    $hash = password_hash($pin, PASSWORD_DEFAULT);
    $stmt = getDb()->prepare('UPDATE users SET pin_hash = ? WHERE id = ?');
    $stmt->execute([$hash, $userId]);
}

function authenticateUser(string $email, string $password)
{
    $user = findUserByEmail($email);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return null;
    }
    return $user;
}

function generateVerificationCode(int $length = 6): string
{
    $max = 10 ** $length - 1;
    return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
}

function sendEmail(string $to, string $subject, string $message): bool
{
    $headers = "From: Trusted Bank <no-reply@trustedbank.local>\r\n" .
               "Reply-To: support@trustedbank.local\r\n" .
               "Content-Type: text/plain; charset=UTF-8\r\n";

    if (!function_exists('mail')) {
        return false;
    }

    // Suppress warnings on local systems without an SMTP server.
    return @mail($to, $subject, $message, $headers);
}

function sendVerificationEmail(array $user, string $code): bool
{
    $subject = 'Trusted Bank account verification code';
    $message = "Hello {$user['name']},\n\n" .
               "Use the following code to verify your Trusted Bank account:\n\n" .
               "{$code}\n\n" .
               "If you did not request this, please ignore this message.\n\n" .
               "Trusted Bank";
    return sendEmail($user['email'], $subject, $message);
}

function sendPasswordResetEmail(array $user, string $code): bool
{
    $subject = 'Trusted Bank password reset code';
    $message = "Hello {$user['name']},\n\n" .
               "Use the following code to reset your Trusted Bank password:\n\n" .
               "{$code}\n\n" .
               "This code expires in 60 minutes. If you did not request a reset, please contact support.\n\n" .
               "Trusted Bank";
    return sendEmail($user['email'], $subject, $message);
}

function verifyUserCode(int $userId, string $code): bool
{
    $stmt = getDb()->prepare('SELECT verification_code FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $storedCode = $stmt->fetchColumn();
    if (!$storedCode || $storedCode !== $code) {
        return false;
    }

    $stmt = getDb()->prepare('UPDATE users SET verified = 1, verification_code = NULL WHERE id = ?');
    $stmt->execute([$userId]);
    return true;
}

function setVerificationCode(int $userId, string $code): void
{
    $stmt = getDb()->prepare('UPDATE users SET verification_code = ? WHERE id = ?');
    $stmt->execute([$code, $userId]);
}

function setPasswordResetCode(int $userId, string $code, string $expiresAt): void
{
    $stmt = getDb()->prepare('UPDATE users SET password_reset_code = ?, password_reset_expires_at = ? WHERE id = ?');
    $stmt->execute([$code, $expiresAt, $userId]);
}

function clearPasswordResetCode(int $userId): void
{
    $stmt = getDb()->prepare('UPDATE users SET password_reset_code = NULL, password_reset_expires_at = NULL WHERE id = ?');
    $stmt->execute([$userId]);
}

function verifyPasswordResetCode(int $userId, string $code): bool
{
    $stmt = getDb()->prepare('SELECT password_reset_code, password_reset_expires_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row || !$row['password_reset_code'] || $row['password_reset_code'] !== $code) {
        return false;
    }
    if ($row['password_reset_expires_at'] && strtotime($row['password_reset_expires_at']) < time()) {
        return false;
    }
    return true;
}

function resetUserPassword(int $userId, string $passwordHash): void
{
    $stmt = getDb()->prepare('UPDATE users SET password_hash = ?, password_reset_code = NULL, password_reset_expires_at = NULL WHERE id = ?');
    $stmt->execute([$passwordHash, $userId]);
}

function getVerificationCode(int $userId): ?string
{
    $stmt = getDb()->prepare('SELECT verification_code FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    return $stmt->fetchColumn() ?: null;
}

function isUserVerified(array $user): bool
{
    return !empty($user['verified']);
}

function getTransactions(int $userId, int $limit = 10): array
{
    $stmt = getDb()->prepare('SELECT type, amount, description, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getAllUsers(): array
{
    $stmt = getDb()->query('SELECT id, name, email, balance, is_admin, verified, pin_hash, created_at FROM users ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

function searchUsers(string $term): array
{
    $pattern = '%' . strtolower($term) . '%';
    $stmt = getDb()->prepare('SELECT id, name, email, balance, is_admin, verified, pin_hash, created_at FROM users WHERE LOWER(name) LIKE ? OR LOWER(email) LIKE ? ORDER BY created_at DESC');
    $stmt->execute([$pattern, $pattern]);
    return $stmt->fetchAll();
}

function getAllTransactions(int $limit = 20): array
{
    $stmt = getDb()->prepare('SELECT t.id, u.email AS user_email, t.type, t.amount, t.description, t.created_at FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT ?');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function toggleUserAdmin(int $userId, bool $isAdmin): void
{
    $stmt = getDb()->prepare('UPDATE users SET is_admin = ? WHERE id = ?');
    $stmt->execute([$isAdmin ? 1 : 0, $userId]);
}

function deleteUserById(int $userId): void
{
    $stmt = getDb()->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$userId]);
}

function isAdmin(array $user): bool
{
    return !empty($user['is_admin']);
}

function requireAdmin()
{
    ensureSessionStarted();
    if (empty($_SESSION['user'])) {
        redirect('login.php');
    }
    $current = currentUser();
    if (!$current || !isAdmin($current) || ($current['email'] ?? '') !== ADMIN_EMAIL) {
        redirect('dashboard.php');
    }
}

function updateUserBalance(int $userId, float $amount): void
{
    $stmt = getDb()->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
    $stmt->execute([$amount, $userId]);
}

function updateUserInfo(int $userId, string $name, string $email): void
{
    $stmt = getDb()->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
    $stmt->execute([$name, $email, $userId]);
}

function updateUserPassword(int $userId, string $passwordHash): void
{
    $stmt = getDb()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([$passwordHash, $userId]);
}

function addTransaction(int $userId, string $type, float $amount, ?string $description = null, ?int $recipientId = null): int
{
    $db = getDb();
    $db->beginTransaction();
    try {
        $user = findUserById($userId);
        if (!$user) {
            throw new RuntimeException('User not found.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        if ($type === 'withdrawal') {
            if ($user['balance'] < $amount) {
                throw new RuntimeException('Insufficient balance for this withdrawal.');
            }

            $stmt = $db->prepare('INSERT INTO transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)');
            $stmt->execute([$userId, $type, $amount, $description]);
            updateUserBalance($userId, -$amount);

            $db->commit();
            return (int)$db->lastInsertId();
        }

        if ($type === 'deposit') {
            $stmt = $db->prepare('INSERT INTO transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)');
            $stmt->execute([$userId, $type, $amount, $description]);
            updateUserBalance($userId, $amount);

            $db->commit();
            return (int)$db->lastInsertId();
        }

        if ($type === 'transfer') {
            if (!$recipientId) {
                throw new InvalidArgumentException('A transfer recipient is required.');
            }
            if ($recipientId === $userId) {
                throw new InvalidArgumentException('You cannot transfer funds to yourself.');
            }

            $recipient = findUserById($recipientId);
            if (!$recipient) {
                throw new RuntimeException('Recipient account not found.');
            }
            if ($user['balance'] < $amount) {
                throw new RuntimeException('Insufficient balance for this transfer.');
            }

            $senderDescription = $description ? "Transfer to {$recipient['email']}: {$description}" : "Transfer to {$recipient['email']}";
            $recipientDescription = $description ? "Transfer from {$user['email']}: {$description}" : "Transfer from {$user['email']}";

            $stmt = $db->prepare('INSERT INTO transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)');
            $stmt->execute([$userId, $type, $amount, $senderDescription]);
            $senderTransactionId = (int)$db->lastInsertId();
            updateUserBalance($userId, -$amount);

            $stmt->execute([$recipientId, $type, $amount, $recipientDescription]);
            updateUserBalance($recipientId, $amount);

            $db->commit();
            return $senderTransactionId;
        }

        throw new InvalidArgumentException('Invalid transaction type.');
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function loginUser(array $user): void
{
    ensureSessionStarted();
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'is_admin' => $user['is_admin'] ?? 0,
    ];
}

function requireLogin()
{
    ensureSessionStarted();
    if (empty($_SESSION['user'])) {
        redirect('login.php');
    }
}

function currentUser()
{
    return $_SESSION['user'] ?? null;
}
