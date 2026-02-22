# تشغيل مشروع Laravel (مركب) عبر Apache في XAMPP

## تنبيه مهم

- **المنفذ 8000** (`http://127.0.0.1:8000`) = سيرفر **php artisan serve** (مدمج PHP)، **ليس** Apache.
- **Apache في XAMPP** يعمل عادة على المنفذ **80** → تفتح الموقع على **http://localhost**.

لو حابب تستخدم **Apache** بدل `php artisan serve`، اتبع الخطوات التالية.

---

## 1) نسخ المشروع لمجلد XAMPP

### على Windows
- مجلد XAMPP غالباً: `C:\xampp\htdocs`
- انسخ مجلد المشروع بالكامل إلى: `C:\xampp\htdocs\mylaravelproject`
- أو أنشئ اختصار (symlink) إن كنت تريد الإبقاء على المشروع في مكانه.

### على Linux
- مجلد XAMPP غالباً: `/opt/lampp/htdocs`
- انسخ المشروع:  
  `cp -r /home/shimaa-ahmed/mylaravelproject /opt/lampp/htdocs/`

---

## 2) إعداد Apache ليشير لمجلد `public` فقط

**مهم:** Apache يجب أن يفتح مجلد **`public`** فقط وليس جذر المشروع.

### على Windows (XAMPP)

1. افتح ملف: `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
2. أضف في آخره (عدّل المسار لو مختلف):

```apache
<VirtualHost *:80>
    ServerName markeb.local
    DocumentRoot "C:/xampp/htdocs/mylaravelproject/public"
    <Directory "C:/xampp/htdocs/mylaravelproject/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

3. عدّل ملف الـ hosts:
   - Windows: `C:\Windows\System32\drivers\etc\hosts` (كمسؤول)
   - أضف سطر: `127.0.0.1   markeb.local`
4. أعد تشغيل Apache من لوحة XAMPP.

**بدون VirtualHost:** يمكنك فتح المشروع كـ:  
`http://localhost/mylaravelproject/public`  
(الرابط غير جميل لكنه يعمل.)

### على Linux (XAMPP / LAMPP)

1. افتح: `/opt/lampp/etc/extra/httpd-vhosts.conf`
2. أضف:

```apache
<VirtualHost *:80>
    ServerName markeb.local
    DocumentRoot "/opt/lampp/htdocs/mylaravelproject/public"
    <Directory "/opt/lampp/htdocs/mylaravelproject/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

3. في `/etc/hosts` أضف: `127.0.0.1   markeb.local`
4. أعد تشغيل Apache من XAMPP.

---

## 3) تفعيل mod_rewrite

- **Windows XAMPP:** افتح `C:\xampp\apache\conf\httpd.conf` وتأكد أن السطر التالي **غير** معطّل (بدون # في البداية):  
  `LoadModule rewrite_module modules/mod_rewrite.so`
- **Linux LAMPP:** غالباً مفعّل. إن لم يعمل الـ routing جرّب:  
  `sudo /opt/lampp/lampp restart`

---

## 4) بعد الإعداد

- افتح في المتصفح: **http://localhost/mylaravelproject/public**  
  أو **http://markeb.local** إذا استخدمت VirtualHost.
- رابط الـ API يكون: **http://localhost/mylaravelproject/public/api** أو **http://markeb.local/api**.

في الواجهة الأمامية (Frontend) ضبط **VITE_API_URL** حسب الرابط الذي تستخدمه، مثلاً:  
`http://markeb.local/api` أو `http://localhost/mylaravelproject/public/api`.

---

## 5) لو Apache "شغال" في XAMPP لكن الصفحة لا تفتح

- تأكد أن المسار في **DocumentRoot** و **Directory** هو مجلد **public** داخل المشروع.
- تأكد أن **AllowOverride All** موجود حتى يعمل ملف **.htaccess**.
- راجع سجل أخطاء Apache:
  - Windows: `C:\xampp\apache\logs\error.log`
  - Linux: `/opt/lampp/logs/error_log`
