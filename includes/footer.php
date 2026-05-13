</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <h4>Vitage</h4>
            <p>Hand-picked vintage clothing, restored with love and shipped worldwide. Every piece tells a story.</p>
        </div>
        <div>
            <h4>Shop</h4>
            <ul>
                <li><a href="<?= url('shop.php?cat=pants') ?>">Pants</a></li>
                <li><a href="<?= url('shop.php?cat=shirts') ?>">Shirts</a></li>
                <li><a href="<?= url('shop.php?cat=tshirts') ?>">T-Shirts</a></li>
                <li><a href="<?= url('shop.php?cat=shoes') ?>">Shoes</a></li>
                <li><a href="<?= url('shop.php?cat=accessories') ?>">Accessories</a></li>
            </ul>
        </div>
        <div>
            <h4>Account</h4>
            <ul>
                <?php if (is_logged_in()): ?>
                    <li><a href="<?= url('account.php') ?>">My Account</a></li>
                    <li><a href="<?= url('cart.php') ?>">My Cart</a></li>
                    <li><a href="<?= url('logout.php') ?>">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?= url('login.php') ?>">Login</a></li>
                    <li><a href="<?= url('register.php') ?>">Create account</a></li>
                <?php endif; ?>
            </ul>
        </div>
        <div>
            <h4>Visit us</h4>
            <p>Open Tue–Sun, 11am–7pm<br>33 Old Market Lane<br>Vintage District</p>
        </div>
    </div>
    <div class="footer-bottom">
        <span>&copy; <?= date('Y') ?> Vitage — A university project.</span>
        <span>Made with old soul &amp; new code.</span>
    </div>
</footer>

<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
