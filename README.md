# Hipcall WordPress Plugin

🌐 **Language / Dil:** **English** | [Türkçe](README.tr.md)

Official WordPress plugin for [Hipcall](https://www.hipcall.com/). Embed the Hipcall LiveChat (Webchat) widget and Marketing Tracker (`h.js`) on your WordPress site, and authenticate logged-in WordPress users using HMAC-SHA256 (`user_hash`).

## Requirements

- WordPress **6.3** or higher
- PHP **7.4** or higher

## Features

- **Multi-Region Support:** Supports both **Global** ([https://use.hipcall.com](https://use.hipcall.com)) and **Türkiye** ([https://use.hipcall.com.tr](https://use.hipcall.com.tr)) accounts.
- **Widget Settings:** Paste either the bare UUID or the full `<script>` widget code from **Settings › Inboxes › (inbox) › Widget settings**.
- **Identity Verification (HMAC-SHA256):** Configure the secret from **Settings › Inboxes › (inbox) › Widget settings › Identity verification**. Logged-in WordPress users are verified on the server side via `hash_hmac('sha256', $email, $secret_key)`. The secret is never echoed back into page HTML.
- **Install Tracker (`h.js`):** Paste the Tracker ID or `<script>` tag from **Settings › Marketing › Tracking** to load `h.js` asynchronously in `<head>`.
- **Session Isolation:** Clears `hipcall_session_<widget_id>` from both `localStorage` and `sessionStorage` when a user logs out or switches accounts.

## File Structure

```text
hipcall/
├── hipcall.php                        # Plugin bootstrap, constants, and includes
├── uninstall.php                      # Option cleanup on plugin deletion
├── readme.txt                         # WordPress.org Plugin Directory readme
├── includes/
│   ├── helpers.php                    # Region URLs and UUID extraction/validation
│   ├── class-hipcall-settings.php     # Admin settings API, sanitization, and UI
│   └── class-hipcall-frontend.php     # Script enqueuing and HMAC-SHA256 calculation
├── assets/
│   └── js/hipcall-widget.js           # Session isolation and hc('user', ...) queue init
└── languages/
    ├── hipcall.pot                    # Translation template (shipped with the plugin)
    └── hipcall-tr_TR.po               # Turkish translation source for translate.wordpress.org (not shipped)
```

## Running Tests

The plugin has PHP tests (PHPUnit with the WordPress test library) and JavaScript tests (Jest). Both run automatically in GitHub Actions on every pull request.

**PHP (needs PHP 7.4+, Composer, and a MySQL/MariaDB server):**

```bash
composer update
bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 latest   # or a version such as 6.3
vendor/bin/phpunit
```

`bin/install-wp-tests.sh` downloads WordPress core and the WordPress test library from GitHub into the system temp directory (override with `WP_CORE_DIR` / `WP_TESTS_DIR`). The test database is emptied on every run, so never point it at a real site's database.

**JavaScript (needs Node.js 20+):**

```bash
npm ci
npm test
```

| Suite | Covers |
| --- | --- |
| `tests/phpunit/tests/helpers/` | UUID extraction/validation, region default and base URL |
| `tests/phpunit/tests/settings/` | Sanitize callbacks (pasted snippets, invalid IDs, secret keep/clear, region not overwritten) and the settings page (secret never printed) |
| `tests/phpunit/tests/frontend/` | Script enqueuing per region, HMAC-SHA256 `user_hash`, no identity for logged-out users, inline config escaping, tracker `data-hipcall-id` |
| `tests/phpunit/tests/uninstall.php` | Options removed on uninstall |
| `tests/js/` | Session isolation on logout and account switch, `hc` command queue, browsers that block storage |

## Caching Note

When Identity verification is enabled, logged-in user details (`name`, `email`, `user_hash`) are embedded in the page HTML. Ensure full-page caching is **disabled for logged-in users** in your caching plugin or CDN.

## License

GPLv2 or later, the same license as WordPress. See [LICENSE](LICENSE) for details.
