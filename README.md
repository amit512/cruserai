# HomeCraft PHP (No Framework)

A minimal full-stack PHP app for a handmade marketplace.

## Quick start
1. Create a MySQL database named `homecraft` (or change in `config/config.php`).
2. Import `schema.sql`.
3. Put this folder in your PHP server root. Suggested docroot is `public/`.
4. Update `config/config.php` `APP_URL` if you want absolute URLs.
5. Visit `/public/register.php` to create an account.
6. For seller features, choose role **Seller** during registration.

## Structure
- `config/` – configuration & session/CSRF helpers
- `app/` – simple models using PDO (User, Product)
- `includes/` – header/footer layout
- `public/` – public pages (home, catalog, product, auth)
- `seller/` – seller dashboard & product management
- `actions/` – POST handlers
- `_legacy_uploaded/` – your original files for reference

## Notes
- Passwords hashed with `password_hash`.
- CSRF protection on all POSTs.
- File uploads stored in `public/uploads/`.
- Tailwind via CDN for styling.
