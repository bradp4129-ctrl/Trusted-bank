<?php
$title = 'Trusted Bank';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="hero">
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
          <a href="#services">Services</a>
          <a href="contact.php">Contact</a>
          <a href="login.php">Sign In</a>
        </div>
      </nav>

      <div class="hero-grid">
        <div class="hero-copy">
          <span class="eyebrow">Trusted Bank</span>
          <h1>Secure digital banking for every customer.</h1>
          <p>Manage accounts, move money, and grow your savings with a trusted online banking experience tailored for modern users.</p>
          <div class="buttons">
            <a class="btn btn-primary" href="register.php">Open an account</a>
            <a class="btn btn-secondary" href="login.php">Get started</a>
          </div>
        </div>
        <div class="hero-highlight">
          <div class="feature-card">
            <h3>Trusted protection</h3>
            <p>Encrypted accounts, secure sign-in, and 24/7 monitoring keep your money safe.</p>
          </div>
          <div class="feature-card">
            <h3>Fast transfers</h3>
            <p>Send and receive money quickly, with clear tracking and simple controls.</p>
          </div>
        </div>
      </div>
    </div>
  </header>

  <section id="services" class="section section-features">
    <div class="container">
      <h2>Banking solutions that work for you</h2>
      <p>Trusted Bank gives businesses and individuals a simple way to manage money, track spending, and stay secure online.</p>
      <div class="cards">
        <article class="card">
          <h3>Secure accounts</h3>
          <p>Monitoring, encryption, and fast authentication keep your funds protected around the clock.</p>
        </article>
        <article class="card">
          <h3>Easy transfers</h3>
          <p>Move money domestically or internationally with an intuitive dashboard and clear pricing.</p>
        </article>
        <article class="card">
          <h3>Smart savings</h3>
          <p>Build toward your goals with savings plans, automatic deposits, and progress tracking.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <h2>Ready to start?</h2>
      <p>Launch Trusted Bank as your online banking platform. The first step is to build a safe, responsive website with clear customer messaging.</p>
      <div class="contact-card">
        <p>Need support? Visit our <a href="contact.php">Contact</a> page to reach support and access customer help.</p>
        <p>We can also improve your verification flow, photo capture, and account management before submission.</p>
      </div>
    </div>
  </section>

  <footer>
    <div class="container">
      <p>&copy; <span id="current-year"></span> Trusted Bank. All rights reserved.</p>
    </div>
  </footer>

  <script src="assets/js/script.js"></script>
</body>
</html>
