<?php if (!defined('APP_NAME')) { require_once __DIR__ . '/../../src/config.php'; } ?>
</main>
<footer class="site-footer" style="background:#245C2A;color:#F5FFF5;">
    <div class="container footer-grid">
        <div>
            <h4>About <?= APP_NAME ?></h4>
            <p>Making sustainable living stylish and affordable for everyone.</p>
        </div>
        <div>
            <h4>Quick Links</h4>
            <ul>
                <li><a href="<?= APP_URL ?>/public/community.php">Community Hub</a></li>
                <li><a href="<?= APP_URL ?>/public/category.php">Shop All</a></li>
                <li><a href="<?= APP_URL ?>/public/user_dashboard.php">Account</a></li>
            </ul>
        </div>
        <div>
            <h4>Stay Connected</h4>
            <p>Follow us for eco-friendly tips & updates.</p>
            <div class="social-icons">
                <a href="#" aria-label="Facebook"><img src="<?= APP_URL ?>/public/assets/images/icon_fb.svg" alt="Facebook" width="24" height="24" loading="lazy"></a>
                <a href="#" aria-label="Instagram"><img src="<?= APP_URL ?>/public/assets/images/icon_ig.svg" alt="Instagram" width="24" height="24" loading="lazy"></a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© <?= date('Y') ?> <?= APP_NAME ?> — All rights reserved.</p>
    </div>
</footer>
<script src="<?= APP_URL ?>/public/assets/js/main.js"></script>
</body>
</html>
