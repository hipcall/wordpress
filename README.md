# Hipcall WordPress Plugin

Official WordPress plugin for [Hipcall](https://www.hipcall.com/). Easily embed Hipcall communication widgets on your WordPress site and automatically verify logged-in WordPress users using HMAC-SHA256 (`user_hash`).

## Features

- **Easy Integration:** Add your Hipcall **Widget ID** from **Settings > Hipcall** (`Ayarlar > Hipcall`) without editing theme files.
- **Verified Users (HMAC-SHA256):** When a **Secret Key** is configured, logged-in WordPress users are automatically authenticated on the server side via `hash_hmac('sha256', $email, $secret_key)` and passed to `hc('user', { email, name, user_hash })`.
- **Session Isolation:** Automatically resets verified visitor session data in `localStorage` when a user logs out or switches accounts.

## Installation

1. Download or clone this repository into `wp-content/plugins/hipcall` (or upload `hipcall.zip` via **Plugins > Add New > Upload Plugin**).
2. Activate the **Hipcall** plugin in WordPress.
3. Navigate to **Settings > Hipcall** (`Ayarlar > Hipcall`).
4. Enter your **Widget ID** and (optionally) your **Secret Key**, then click **Save Changes**.

## License

MIT License. See [LICENSE](LICENSE) for details.
