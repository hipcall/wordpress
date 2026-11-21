=== Hipcall ===
Contributors: hipcall
Tags: hipcall, livechat, customer-support, marketing, chat
Requires at least: 6.3
Tested up to: 7.1
Stable tag: 1.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed the Hipcall LiveChat widget and Marketing Tracker (h.js) on WordPress with automatic HMAC-SHA256 user verification.

== Description ==

**Hipcall** is the official WordPress plugin for [Hipcall](https://www.hipcall.com/). It allows you to integrate the Hipcall LiveChat (Webchat) widget and Marketing Tracker (`h.js`) into your WordPress site without editing theme files.

Supports both **Global (`use.hipcall.com`)** and **Türkiye (`use.hipcall.com.tr`)** server regions.

= Key Features =

* **Multi-Region Support:** Works with both Global ([use.hipcall.com](https://use.hipcall.com/)) and Türkiye ([use.hipcall.com.tr](https://use.hipcall.com.tr/)) Hipcall accounts.
* **LiveChat Widget:** Enable the Hipcall chat widget across your site by entering your Widget ID (or pasting the widget code) under **Settings > Hipcall**.
* **Identity Verification (HMAC-SHA256):** When an **Identity verification** secret is configured, logged-in WordPress users are authenticated on the server side using PHP `hash_hmac('sha256', $email, $secret_key)` and identified as **Verified** in the Hipcall inbox.
* **Marketing Tracker (`h.js`):** When a **Tracker ID** is configured, the plugin adds the asynchronous `h.js` script to the `<head>` of every page to track campaign attribution (UTM parameters, ad clicks) and form submissions.
* **Session Isolation:** Automatically clears the widget session from `localStorage`, `sessionStorage` and its fallback cookie when a logged-in user logs out or when a different user logs in on the same browser.

== External Services ==

This plugin connects to Hipcall cloud services to provide the live chat widget and marketing tracker on your website:

* **Service Provider:** [Hipcall Global](https://www.hipcall.com/) / [Hipcall Türkiye](https://www.hipcall.com.tr/)
* **Endpoints Used (depending on the selected Region):**
  * Global: `https://use.hipcall.com/widget/{WIDGET_ID}`, `wss://use.hipcall.com/socket`, `https://use.hipcall.com/track/v1/h.js`
  * Türkiye: `https://use.hipcall.com.tr/widget/{WIDGET_ID}`, `wss://use.hipcall.com.tr/socket`, `https://use.hipcall.com.tr/track/v1/h.js`
* **Data Sent:**
  * **LiveChat Widget:** Chat messages (`text`), current page URL (`page_url`), page title (`page_title`), HTTP referrer (`referrer`), browser user agent (`user_agent`), visitor tracker cookies (`hc_visitor_id`, `hc_consent`), and—only when an Identity verification secret is configured and a WordPress user is logged in—the user's display name (`name`), email address (`email`), and the server-computed HMAC-SHA256 hash (`user_hash`).
  * **Marketing Tracker (`h.js`):** Page URL, HTTP referrer, device category, campaign/attribution parameters (`utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `gclid`, `gbraid`, `wbraid`, `fbclid`, `msclkid`, `dclid`), and form fields on forms explicitly marked with `data-hipcall-form`.
* **Cookies & Browser Storage:**
  * `hc_visitor_id` (Cookie, **1-year** expiration): Set by `h.js` (`SameSite=Lax`) to assign a pseudonymous visitor ID and link marketing attribution across visits and chat sessions.
  * `hipcall_session_{WIDGET_ID}` (`localStorage`, `sessionStorage`, and fallback 30-day cookie): Stores the active chat session token and visitor state.
  * `hipcall_wp_identity_{WIDGET_ID}` (`localStorage` and `sessionStorage`): Stores only the HMAC-SHA256 `user_hash` of the currently logged-in WordPress user so the chat session can be cleared automatically on logout or account switch.
* **Terms of Use & Privacy Policy:**
  * Global: [Hipcall Website](https://www.hipcall.com/) | [Privacy Policy](https://www.hipcall.com/privacy-policy/) | [Terms of Service](https://www.hipcall.com/terms-of-service/)
  * Türkiye: [Hipcall Türkiye](https://www.hipcall.com.tr/) | [Privacy Policy](https://www.hipcall.com.tr/gizlilik-politikasi/) | [Terms of Service](https://www.hipcall.com.tr/kullanim-kosullari/)

== Installation ==

1. Install the plugin via **Plugins > Add New** in your WordPress dashboard by searching for **Hipcall** or uploading `hipcall.zip`.
2. Activate the **Hipcall** plugin.
3. Navigate to **Settings > Hipcall**.
4. Select your **Region** (`Global - use.hipcall.com` or `Türkiye - use.hipcall.com.tr`).
5. Paste your **Widget code / ID**, optional **Identity verification** secret, and/or **Tracker ID**, then click **Save Changes**.

== Frequently Asked Questions ==

= Where can I find my Widget code and Identity verification secret? =

In your Hipcall portal ([use.hipcall.com](https://use.hipcall.com/) or [use.hipcall.com.tr](https://use.hipcall.com.tr/)), go to **Settings › Inboxes › (your inbox) › Widget settings**. Copy the widget code (or UUID) under **Widget code**, and copy your HMAC secret (`whsec_...`) under **Identity verification**.

= Where can I find my Tracker ID (h.js)? =

In your Hipcall portal, go to **Settings › Marketing › Tracking**. Under **Install tracker**, copy your **Tracker ID** or the script tag.

= Do I need to configure my page caching plugin? =

When **Identity verification** is enabled and a user is logged into WordPress, their display name, email address, and `user_hash` are rendered in the page HTML. Most WordPress caching plugins bypass full-page cache for logged-in users by default. Make sure **full-page caching is disabled for logged-in users** in your caching plugin or CDN so one user's identity is never served to another visitor.

= Is WordPress Multisite supported? =

This plugin is designed for standard single-site WordPress installations (or per-site activation). Network-wide Multisite uninstallation cleanup is not currently supported.

== Screenshots ==

1. Settings > Hipcall: choose your region and paste your widget code, identity verification secret and tracker ID.
2. The Hipcall live chat widget on a WordPress site, with a logged-in user's conversation.
3. Logged-in WordPress users appear as Verified in the Hipcall inbox.

== Changelog ==

= 1.1.0 =
* License changed to GPLv2 or later, the same license as WordPress.
* Fix: Logged-in users are now identified in the chat widget even when the browser blocks storage access (for example Safari with all cookies blocked).
* Improvement: Session isolation also clears the widget's fallback session cookie when a user logs out or another user logs in.
* Added a translation template (languages/hipcall.pot) and screenshots for the plugin directory.

= 1.0.0 =
* Initial release: Hipcall Webchat widget integration, HMAC-SHA256 logged-in user identity verification, Marketing Tracker (`h.js`) support, and Global / Türkiye region selection.

== Upgrade Notice ==

= 1.1.0 =
Relicensed under GPLv2 or later. Fixes logged-in user identification in browsers that block storage.

= 1.0.0 =
Initial stable release of the official Hipcall WordPress plugin.
