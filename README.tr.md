# Hipcall WordPress Eklentisi

🌐 **Dil / Language:** [English](README.md) | **Türkçe**

[Hipcall](https://www.hipcall.com.tr/) resmi WordPress eklentisi. Hipcall iletişim ve canlı destek bileşenlerini WordPress sitenize kolayca eklemenizi ve giriş yapmış WordPress üyelerini HMAC-SHA256 (`user_hash`) ile otomatik olarak doğrulamanızı (**Verified User**) sağlar.

## Özellikler

- **Kolay Kurulum:** Tema dosyalarına veya koda müdahale etmeden **Ayarlar > Hipcall** ekranından **Widget ID** bilginizi girerek hemen kullanmaya başlayın.
- **Doğrulanmış Kullanıcılar (HMAC-SHA256):** **Secret Key (Gizli Anahtar)** girildiğinde, WordPress'e giriş yapmış üyelerin e-posta adresi sunucu tarafında (`hash_hmac('sha256', $email, $secret_key)`) şifrelenir ve `hc('user', { email, name, user_hash })` ile güvenli şekilde Hipcall'a iletilir.
- **Oturum Güvenliği:** Kullanıcı WordPress'ten çıkış yaptığında veya farklı bir hesaba geçtiğinde tarayıcıdaki doğrulanmış oturum verisi otomatik olarak sıfırlanır.

## Kurulum

1. Bu depoyu `wp-content/plugins/hipcall` klasörüne indirin (veya `hipcall.zip` dosyasını WordPress yönetim panelinden **Eklentiler > Yeni Ekle > Eklenti Yükle** menüsünden yükleyin).
2. WordPress panelinden **Hipcall** eklentisini etkinleştirin.
3. **Ayarlar > Hipcall** sayfasına gidin.
4. [Hipcall Panelinizden](https://use.hipcall.com.tr/) aldığınız **Widget ID** ve (isteğe bağlı) **Secret Key** bilgilerini girip **Değişiklikleri Kaydet** butonuna tıklayın.

## Lisans

MIT Lisansı ile lisanslanmıştır. Detaylar için [LICENSE](LICENSE) dosyasına göz atabilirsiniz.
