# إعداد Apache وحل أخطاء 500

## 1. إعداد Apache

- **DocumentRoot** يجب أن يشير إلى مجلد **`public`** فقط وليس جذر المشروع:
  - ✅ `DocumentRoot "/path/to/mylaravelproject/public"`
  - ❌ لا تستخدم `DocumentRoot "/path/to/mylaravelproject"`
- تفعيل **mod_rewrite**: `sudo a2enmod rewrite`
- **AllowOverride All** لمجلد `public` حتى يعمل ملف `.htaccess`
- استخدم الملف النموذجي: `apache-markeb.conf.example`

بعد التعديل:
```bash
sudo systemctl reload apache2
```

---

## 2. سبب أخطاء 500 (من سجل Laravel)

اللوج أظهر أن **المشكلة من تطبيق Laravel وليس من Apache**:

| الخطأ | السبب | الحل |
|------|--------|------|
| `no such table: personal_access_tokens` | جداول قاعدة البيانات غير منفذة (migrations) | تشغيل `php artisan migrate` |
| `Connection refused` (MySQL) | الاتصال بقاعدة MySQL فاشل | استخدام SQLite في `.env` أو تشغيل MySQL |
| صلاحيات المجلدات | السيرفر لا يكتب في `storage` أو `bootstrap/cache` | تعديل المالك/الصلاحيات (انظر أدناه) |

---

## 3. خطوات حل 500 (نفّذها بالترتيب)

### أ) التأكد من قاعدة البيانات

المشروع مضبوط على **SQLite** في `.env`:

```
DB_CONNECTION=sqlite
```

تأكد من وجود الملف:
```bash
ls -la database/database.sqlite
```
إن لم يكن موجوداً:
```bash
touch database/database.sqlite
```

### ب) تشغيل الـ Migrations

```bash
cd /home/shimaa-ahmed/mylaravelproject
php artisan migrate
```

هذا ينشئ جدول `personal_access_tokens` وباقي الجداول الناقصة.

### ج) مسح الكاش

```bash
php artisan config:clear
php artisan cache:clear
```

### د) صلاحيات المجلدات (عند استخدام Apache)

مستخدم Apache (مثلاً `www-data`) يجب أن يكتب في `storage` و `bootstrap/cache`:

```bash
cd /home/shimaa-ahmed/mylaravelproject
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

عدّل `www-data` إذا كان مستخدم السيرفر مختلفاً في نظامك.

---

## 4. رابط الواجهة الأمامية بالـ API

الواجهة على `localhost:5173` تحتاج عنوان الـ API. إذا كان Laravel يعمل عبر Apache على نفس الجهاز:

- إن كان Apache على المنفذ 80: اضبط في **frontend** متغير البيئة:
  - `VITE_API_URL=http://localhost/api`  
  أو حسب اسم الدومين (مثلاً `http://markeb.local/api`).
- إن كان Laravel يعمل بـ `php artisan serve` على المنفذ 8000:
  - `VITE_API_URL=http://localhost:8000/api`

ثم أعد تشغيل خادم الواجهة (Vite) بعد تغيير `.env`.

---

## 5. التحقق من اللوج بعد التعديل

لرؤية آخر أخطاء 500:

```bash
tail -100 storage/logs/laravel.log
```

مع `APP_DEBUG=true` في `.env` يمكن رؤية تفاصيل الخطأ في المتصفح (في بيئة التطوير فقط).
