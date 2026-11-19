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

## Önbellek (Cache) Notu

Kimlik doğrulama aktifken giriş yapmış kullanıcının adı, e-postası ve `user_hash` bilgisi sayfa HTML'ine yazılır. Önbellek (cache) eklentinizde **giriş yapmış kullanıcılar için sayfa önbelleklemenin kapalı olduğundan** emin olun.

## Lisans

MIT Lisansı ile lisanslanmıştır. Detaylar için [LICENSE](LICENSE) dosyasına göz atabilirsiniz.
