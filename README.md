# DragonStone - Local setup (XAMPP)

1. Copy `dragonstone/` into `C:\xampp\htdocs\` (Windows) or `/opt/lampp/htdocs/` (Linux).
2. Start Apache and MySQL via XAMPP.
3. Import `sql/dragonstone_schema_and_seed.sql` into phpMyAdmin.
    - If you used the placeholder admin row, run `php src/create_admin.php` in terminal to create a proper admin account (or use provided script).
4. Edit `src/config.php`:
    - Set DB credentials for local XAMPP (defaults already set).
    - Put your PayPal sandbox client ID & secret in `'paypal'` block.
5. Ensure `public/uploads/products/` exists and is writable by Apache.
6. Visit `http://localhost/dragonstone/public/` in your browser.
7. Admin: `http://localhost/dragonstone/admin/index.php`
8. For PayPal webhook testing locally use ngrok and set webhook URL in PayPal sandbox dev account.

Deployment to 000Webhost:
- Comment the local DB block in `src/config.php`, uncomment and replace the 000Webhost block with your 000Webhost DB credentials.
- Upload files (place `public/` files into 000Webhost `public_html`).
