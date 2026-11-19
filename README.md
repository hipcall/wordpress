# Hipcall WordPress Plugin

🌐 **Language / Dil:** **English** | [Türkçe](README.tr.md)

Official WordPress plugin for [Hipcall](https://www.hipcall.com/). Easily embed the Hipcall LiveChat (Webchat) widget and Marketing Tracker (`h.js`) on your WordPress site, and automatically verify logged-in WordPress users using HMAC-SHA256 (`user_hash`).

## Features

- **Multi-Region Support:** Supports both **Global** ([https://use.hipcall.com](https://use.hipcall.com)) and **Türkiye** ([https://use.hipcall.com.tr](https://use.hipcall.com.tr)) accounts.
- **LiveChat (Webchat Widget):** Add your Hipcall **Widget ID** from **Settings > Hipcall** without editing theme files.
- **Verified Users (HMAC-SHA256):** When a **Secret Key** is configured, logged-in WordPress users are automatically authenticated on the server side via `hash_hmac('sha256', $email, $secret_key)` and passed to `hc('user', { email, name, user_hash })`.
- **Marketing Tracker (`h.js`):** When a **Tracker ID** is configured, automatically injects `<script async src="https://use.hipcall.com/track/v1/h.js" data-hipcall-id="..."></script>` into the `<head>` of every page for marketing attribution, UTM tracking, and form tracking.
- **Session Isolation:** Automatically resets verified visitor session data in `localStorage` when a user logs out or switches accounts.

## Installation

1. Download or clone this repository into `wp-content/plugins/hipcall` (or upload `hipcall.zip` via **Plugins > Add New > Upload Plugin**).
2. Activate the **Hipcall** plugin in WordPress.
3. Navigate to **Settings > Hipcall**.
4. Select your **Region / Server** (`Global - use.hipcall.com` or `Türkiye - use.hipcall.com.tr`).
5. Enter your **Widget ID**, optional **Secret Key**, and/or **Tracker ID** (from [https://use.hipcall.com](https://use.hipcall.com)), then click **Save Changes**.

## License

MIT License. See [LICENSE](LICENSE) for details.
