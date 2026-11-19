=== Hipcall ===
Contributors: hipcall
Donate link: https://www.hipcall.com/
Tags: hipcall, livechat, customer-support, marketing, chat
Requires at least: 5.0
Tested up to: 7.1
Stable tag: 1.0.0
Requires PHP: 7.2
License: MIT
License URI: https://opensource.org/licenses/MIT

Embed the Hipcall LiveChat widget and Marketing Tracker (h.js) on WordPress with automatic HMAC-SHA256 user verification.

== Description ==

**Hipcall** is the official WordPress plugin for [Hipcall](https://www.hipcall.com/). It allows you to easily integrate the Hipcall LiveChat (Webchat) widget and Marketing Tracker (`h.js`) into your WordPress site without editing any theme files or writing code.

Supports both **Global (`use.hipcall.com`)** and **Türkiye (`use.hipcall.com.tr`)** server regions.

= Key Features =

* **Multi-Region Support:** Works seamlessly with both Global ([use.hipcall.com](https://use.hipcall.com/)) and Türkiye ([use.hipcall.com.tr](https://use.hipcall.com.tr/)) Hipcall accounts.
* **LiveChat (Webchat Widget):** Enable the Hipcall live chat bubble across your site by simply entering your `Widget ID` under **Settings > Hipcall**.
* **Verified Users (HMAC-SHA256):** When a `Secret Key` is configured, logged-in WordPress users are automatically verified on the server side using PHP `hash_hmac('sha256', $email, $secret_key)` and identified as **Verified** in the Hipcall agent inbox.
* **Marketing Tracker (`h.js`):** When a `Tracker ID` is configured, the plugin automatically adds the asynchronous `h.js` script to the `<head>` of every page to track campaign attribution (UTM parameters, ad clicks) and form submissions.
* **Session Isolation:** Automatically clears verified visitor session data in `localStorage` when a user logs out of WordPress or switches accounts.

= Türkçe Özet (Turkish Summary) =

**Hipcall** resmi WordPress eklentisi, [Hipcall Türkiye](https://www.hipcall.com.tr/) (`use.hipcall.com.tr`) ve Global (`use.hipcall.com`) hesaplarınız için Canlı Destek (Webchat) balonunu ve Pazarlama İzleyicisini (`h.js`) hiçbir tema dosyasına dokunmadan WordPress sitenize eklemenizi sağlar. Giriş yapmış WordPress üyelerini sunucu tarafında **HMAC-SHA256** (`user_hash`) ile otomatik olarak doğrular (**Verified User**).

== External Services ==

This plugin connects to Hipcall cloud services to provide the live chat widget and marketing tracker on your website:

* **Service Provider:** [Hipcall Global](https://www.hipcall.com/) / [Hipcall Türkiye](https://www.hipcall.com.tr/)
* **Endpoints Used (depending on the selected Region):**
  * Global: `https://use.hipcall.com/widget/{WIDGET_ID}`, `wss://use.hipcall.com/socket`, `https://use.hipcall.com/track/v1/h.js`
  * Türkiye: `https://use.hipcall.com.tr/widget/{WIDGET_ID}`, `wss://use.hipcall.com.tr/socket`, `https://use.hipcall.com.tr/track/v1/h.js`
* **Data Sent:** Current page URL (`page_url`), page title (`page_title`), referrer, UTM/campaign parameters, and—only when a Secret Key is configured and a WordPress user is logged in—the user's display name (`name`), email address (`email`), and the server-computed HMAC-SHA256 verification hash (`user_hash`).
* **Terms of Use & Privacy Policy:**
  * Global: [Hipcall Website](https://www.hipcall.com/) | [Privacy Policy](https://www.hipcall.com/privacy-policy/) | [Terms of Service](https://www.hipcall.com/terms-of-service/)
  * Türkiye: [Hipcall Türkiye](https://www.hipcall.com.tr/) | [Gizlilik Politikası](https://www.hipcall.com.tr/gizlilik-politikasi/) | [Kullanım Koşulları](https://www.hipcall.com.tr/kullanim-kosullari/)

== Installation ==

1. Install the plugin via **Plugins > Add New** in your WordPress dashboard by searching for **Hipcall** or uploading `hipcall.zip`.
2. Activate the **Hipcall** plugin.
3. Navigate to **Settings > Hipcall** (`Ayarlar > Hipcall`).
4. Select your **Region / Server** (`Global - use.hipcall.com` or `Türkiye - use.hipcall.com.tr`).
5. Enter your **Widget ID**, optional **Secret Key**, and/or **Tracker ID** and click **Save Changes**.

== Frequently Asked Questions ==

= Where can I find my Widget ID and Secret Key? =

Log in to your Hipcall Dashboard ([use.hipcall.com](https://use.hipcall.com/) for Global, or [use.hipcall.com.tr](https://use.hipcall.com.tr/) for Türkiye) and navigate to **Settings > Inboxes > Webchat** (`Ayarlar > Gelen Kutuları > Webchat`) to copy your Widget ID and identity verification Secret Key (`whsec_...`).

= Where can I find my Tracker ID (h.js)? =

In your Hipcall Dashboard ([Global](https://use.hipcall.com/portal/settings/marketing/tracking/) or [Türkiye](https://use.hipcall.com.tr/portal/settings/marketing/tracking/)), go to **Settings > Marketing > Tracking** (`Ayarlar > Pazarlama > İzleme`) to copy your Tracker ID.

= Are both Widget ID and Tracker ID required? =

No. Both features work independently or together. You can configure only the LiveChat widget, only the Marketing Tracker (`h.js`), or both at the same time.

== Screenshots ==

1. Hipcall settings page in the WordPress admin dashboard (Settings > Hipcall).
2. Hipcall LiveChat widget displayed on the website frontend.

== Changelog ==

= 1.0.0 =
* Initial release: Hipcall Webchat widget integration, HMAC-SHA256 logged-in user verification (Verified User), Marketing Tracker (`h.js`) support, and Global (`use.hipcall.com`) / Türkiye (`use.hipcall.com.tr`) region selection.

== Upgrade Notice ==

= 1.0.0 =
Initial stable release of the official Hipcall WordPress plugin.
