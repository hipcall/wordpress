=== Hipcall ===
Contributors: hipcall
Tags: hipcall, livechat, live chat, customer support, chat widget
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.2
License: MIT
License URI: https://opensource.org/licenses/MIT

Hipcall iletişim ve canlı destek bileşenlerini WordPress sitenize kolayca entegre edin ve giriş yapmış üyeler için HMAC-SHA256 doğrulaması sağlayın.

== Description ==

**Hipcall** resmi WordPress eklentisi, [Hipcall](https://www.hipcall.com.tr/) iletişim ve canlı destek (LiveChat) widget'ını hiçbir tema veya kod dosyasına dokunmadan WordPress sitenize eklemenizi sağlar.

= Öne Çıkan Özellikler / Key Features =

* **Kolay Kurulum (Easy Setup):** WordPress yönetim panelinden (**Ayarlar > Hipcall**) yalnızca `Widget ID` bilginizi girerek widget'ı hemen aktif edin.
* **Doğrulanmış Kullanıcılar (Verified Users - HMAC-SHA256):** `Secret Key` girildiğinde, WordPress sitenize giriş yapmış üyelerin e-posta adresi sunucu tarafında (PHP `hash_hmac`) HMAC-SHA256 ile imzalanır ve Hipcall'a **Verified (Doğrulanmış)** olarak iletilir.
* **Oturum Güvenliği (Session Isolation):** Kullanıcı WordPress'ten çıkış yaptığında veya hesap değiştirdiğinde tarayıcıdaki doğrulanmış oturum verisi otomatik olarak sıfırlanır.

== External Services ==

Bu eklenti, canlı destek ve müşteri iletişimi widget'ını sitenizde çalıştırmak için Hipcall bulut servislerine bağlanır:

* **Servis Sağlayıcı:** [Hipcall](https://www.hipcall.com.tr/)
* **Kullanılan Bağlantılar:**
  * `https://use.hipcall.com.tr/widget/{WIDGET_ID}` (Widget betiğinin ve oturumunun başlatılması)
  * `wss://use.hipcall.com.tr/socket` (Gerçek zamanlı mesajlaşma bağlantısı)
* **Gönderilen Veriler:** Ziyaretçinin bulunduğu sayfa adresi (`page_url`), sayfa başlığı (`page_title`) ve eğer `Secret Key` tanımlanmışsa giriş yapmış WordPress kullanıcısının görünen adı (`name`), e-posta adresi (`email`) ve sunucuda üretilen HMAC-SHA256 doğrulama özeti (`user_hash`).
* **Kullanım Koşulları ve Gizlilik Politikası:**
  * [Hipcall Web Sitesi](https://www.hipcall.com.tr/)
  * [Hipcall Gizlilik Politikası](https://www.hipcall.com.tr/gizlilik-politikasi/)
  * [Hipcall Kullanım Koşulları](https://www.hipcall.com.tr/kullanim-kosullari/)

== Installation ==

1. Eklentiyi WordPress panelinizden **Eklentiler > Yeni Ekle** menüsünden aratarak veya `hipcall.zip` dosyasını yükleyerek kurun.
2. Eklentiyi etkinleştirin.
3. **Ayarlar > Hipcall** (`Settings > Hipcall`) sayfasına gidin.
4. Hipcall panelinizden (`https://use.hipcall.com.tr/`) aldığınız **Widget ID** ve isteğe bağlı **Secret Key** bilgilerini girip kaydedin.

== Frequently Asked Questions ==

= Widget ID ve Secret Key bilgilerini nereden alabilirim? =

[Hipcall Panelinize](https://use.hipcall.com.tr/) giriş yaptıktan sonra **Ayarlar > Gelen Kutuları (Inboxes) > Webchat** bölümünden Widget ID ve kimlik doğrulama gizli anahtarınızı (`whsec_...`) alabilirsiniz.

= Secret Key girmek zorunlu mu? =

Hayır, isteğe bağlıdır. Yalnızca `Widget ID` girerseniz canlı destek bileşeni tüm ziyaretçiler için standart modda çalışır. `Secret Key` girdiğinizde ise WordPress'e giriş yapmış üyeleriniz Hipcall temsilci ekranında otomatik olarak **Verified (Doğrulanmış)** olarak görünür.

== Changelog ==

= 1.0.0 =
* İlk sürüm: Hipcall widget entegrasyonu ve HMAC-SHA256 tabanlı kullanıcı kimlik doğrulaması (Verified User).
