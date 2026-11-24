# Hipcall WordPress Eklentisi

🌐 **Dil / Language:** [English](README.md) | **Türkçe**

[Hipcall](https://www.hipcall.com.tr/) resmi WordPress eklentisi. Hipcall Canlı Destek (Webchat) bileşenini ve Pazarlama İzleyicisini (`h.js`) WordPress sitenize kolayca eklemenizi ve giriş yapmış WordPress üyelerini HMAC-SHA256 (`user_hash`) ile otomatik olarak doğrulamanızı (**Kimlik doğrulama**) sağlar.

## Gereksinimler

- WordPress **6.3** veya üzeri
- PHP **7.4** veya üzeri

## Özellikler

- **Bölge Seçimi:** Hem **Türkiye** ([https://use.hipcall.com.tr](https://use.hipcall.com.tr)) hem de **Global** ([https://use.hipcall.com](https://use.hipcall.com)) Hipcall hesaplarıyla uyumlu çalışır.
- **Widget Ayarları:** Hipcall panelindeki **Ayarlar › Gelen kutuları › (kutu) › Widget ayarları › Widget kodu** bölümünden ister sadece UUID'yi ister tüm `<script>` kodunu yapıştırabilirsiniz.
- **Kimlik Doğrulama (HMAC-SHA256):** **Ayarlar › Gelen kutuları › (kutu) › Widget ayarları › Kimlik doğrulama** bölümündeki gizli anahtar girildiğinde, WordPress'e giriş yapmış üyelerin e-posta adresi sunucu tarafında (`hash_hmac('sha256', $email, $secret_key)`) imzalanır. Gizli anahtar kaydedildikten sonra HTML kaynak koduna geri basılmaz.
- **İzleyiciyi Kur (`h.js`):** **Ayarlar › Pazarlama › İzleme › İzleyiciyi kur** bölümündeki **İzleyici Kimliği** (veya `<script>` etiketi) girildiğinde her sayfanın `<head>` bölümüne eşzamansız `h.js` izleyicisini ekler.
- **Oturum İzolasyonu:** Kullanıcı WordPress'ten çıkış yaptığında veya aynı tarayıcıda farklı bir hesaba geçtiğinde `hipcall_session_<widget_id>` oturumu hem `localStorage` hem `sessionStorage` üzerinden tamamen temizlenir.

## Testleri Çalıştırma

Eklentide PHP testleri (PHPUnit ve WordPress test kütüphanesi) ve JavaScript testleri (Jest) var. İkisi de her pull request'te GitHub Actions'ta otomatik çalışır.

**PHP (PHP 7.4+, Composer ve MySQL/MariaDB gerekir):**

```bash
composer update
bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 latest   # veya 6.3 gibi bir sürüm
vendor/bin/phpunit
```

Test veritabanı her çalıştırmada boşaltılır, gerçek bir sitenin veritabanını kullanmayın.

**JavaScript (Node.js 20+ gerekir):**

```bash
npm ci
npm test
```

## Önbellek (Cache) Notu

Kimlik doğrulama aktifken giriş yapmış kullanıcının adı, e-postası ve `user_hash` bilgisi sayfa HTML'ine yazılır. Önbellek (cache) eklentinizde **giriş yapmış kullanıcılar için sayfa önbelleklemenin kapalı olduğundan** emin olun.

## Geliştirme ve Sürüm Yayınlama

Değişikliklerin WordPress.org'a nasıl ulaştığı ve yeni sürümün nasıl yayınlanacağı (`bin/release.sh`) için [DEVELOPMENT.md](DEVELOPMENT.md) dosyasına bakın.

## Lisans

WordPress ile aynı lisans olan GPLv2 or later ile lisanslanmıştır. Detaylar için [LICENSE](LICENSE) dosyasına göz atabilirsiniz.
