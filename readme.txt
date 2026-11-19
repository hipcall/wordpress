=== Hipcall ===
Contributors: hipcall
Tags: hipcall, livechat, customer-support, marketing, chat
Requires at least: 5.0
Tested up to: 7.1
Stable tag: 1.0.0
Requires PHP: 7.2
License: MIT
License URI: https://opensource.org/licenses/MIT

Hipcall canlı destek (Webchat) ve pazarlama izleyicisini (h.js) WordPress sitenize ekleyin, üyeleri HMAC-SHA256 ile doğrulayın.

== Description ==

**Hipcall** resmi WordPress eklentisi, [Hipcall](https://www.hipcall.com.tr/) canlı destek (Webchat) widget'ını ve pazarlama izleyicisini (`h.js`) hiçbir tema veya kod dosyasına dokunmadan WordPress sitenize eklemenizi sağlar.

= Öne Çıkan Özellikler / Key Features =

* **Canlı Destek (Webchat Widget):** WordPress yönetim panelinden (**Ayarlar > Hipcall**) yalnızca `Widget ID` bilginizi girerek canlı destek balonunu hemen aktif edin.
* **Doğrulanmış Kullanıcılar (Verified Users - HMAC-SHA256):** `Secret Key` girildiğinde, WordPress sitenize giriş yapmış üyelerin e-posta adresi sunucu tarafında (PHP `hash_hmac`) HMAC-SHA256 ile imzalanır ve Hipcall'a **Verified (Doğrulanmış)** olarak iletilir.
* **Pazarlama İzleyicisi (Marketing Tracker - `h.js`):** `İzleyici Kimliği (Tracker ID)` girildiğinde, sayfa yüklemesini engellemeyen eşzamansız `h.js` pazarlama ve UTM/form izleme betiğini her sayfanın `<head>` bölümüne otomatik olarak ekler.
* **Oturum Güvenliği (Session Isolation):** Kullanıcı WordPress'ten çıkış yaptığında veya hesap değiştirdiğinde tarayıcıdaki doğrulanmış oturum verisi otomatik olarak sıfırlanır.

== External Services ==

Bu eklenti, canlı destek widget'ını ve pazarlama izleyicisini sitenizde çalıştırmak için Hipcall bulut servislerine bağlanır (This plugin connects to Hipcall cloud services to provide the live chat widget and marketing tracker):

* **Service Provider / Servis Sağlayıcı:** [Hipcall](https://www.hipcall.com.tr/)
* **Endpoints Used / Kullanılan Bağlantılar:**
  * `https://use.hipcall.com.tr/widget/{WIDGET_ID}` (Loads the live chat widget script and initializes visitor session)
  * `wss://use.hipcall.com.tr/socket` (Establishes real-time messaging WebSocket connection)
  * `https://use.hipcall.com.tr/track/v1/h.js` (Loads the asynchronous marketing attribution and form tracking script when Tracker ID is configured)
* **Data Sent / Gönderilen Veriler:** Current page URL (`page_url`), page title (`page_title`), referrer, UTM/campaign parameters, and—only when a Secret Key is configured and a WordPress user is logged in—the user's display name (`name`), email address (`email`), and the server-computed HMAC-SHA256 verification hash (`user_hash`).
* **Terms & Privacy / Kullanım Koşulları ve Gizlilik Politikası:**
  * [Hipcall Website](https://www.hipcall.com.tr/)
  * [Hipcall Privacy Policy / Gizlilik Politikası](https://www.hipcall.com.tr/gizlilik-politikasi/)
  * [Hipcall Terms of Use / Kullanım Koşulları](https://www.hipcall.com.tr/kullanim-kosullari/)

== Installation ==

1. Eklentiyi WordPress panelinizden **Eklentiler > Yeni Ekle** menüsünden aratarak veya `hipcall.zip` dosyasını yükleyerek kurun.
2. Eklentiyi etkinleştirin.
3. **Ayarlar > Hipcall** (`Settings > Hipcall`) sayfasına gidin.
4. Hipcall panelinizden (`https://use.hipcall.com.tr/`) aldığınız **Widget ID**, isteğe bağlı **Secret Key** ve **İzleyici Kimliği (Tracker ID)** bilgilerini girip kaydedin.

== Frequently Asked Questions ==

= Widget ID ve Secret Key bilgilerini nereden alabilirim? =

[Hipcall Panelinize](https://use.hipcall.com.tr/) giriş yaptıktan sonra **Ayarlar > Gelen Kutuları (Inboxes) > Webchat** bölümünden Widget ID ve kimlik doğrulama gizli anahtarınızı (`whsec_...`) alabilirsiniz.

= İzleyici Kimliği (Tracker ID - h.js) bilgisini nereden alabilirim? =

[Hipcall Panelinize](https://use.hipcall.com.tr/portal/settings/marketing/tracking/) giriş yaptıktan sonra **Ayarlar > Pazarlama > İzleme** bölümünden İzleyici Kimliğinizi alabilirsiniz.

= Secret Key veya Tracker ID girmek zorunlu mu? =

Hayır, her iki bileşen de bağımsız olarak veya birlikte kullanılabilir. Yalnızca `Widget ID` girerek canlı desteği, yalnızca `Tracker ID` girerek pazarlama izleyicisini (`h.js`) veya tümünü birden aktif edebilirsiniz.

== Changelog ==

= 1.0.0 =
* İlk sürüm: Hipcall Webchat widget entegrasyonu, HMAC-SHA256 tabanlı kullanıcı kimlik doğrulaması (Verified User) ve Pazarlama İzleyicisi (`h.js`) desteği.
