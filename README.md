# ViewClass - المنصة الذهبية

نظام إدارة تعليمي متكامل مبني على Laravel 12

## المتطلبات
- PHP 8.3+
- MySQL 8.0+
- Composer
- Node.js 18+

## التثبيت

```bash
# استنساخ المشروع
git clone https://github.com/Mostafa1712002/view-class.git
cd view-class

# تثبيت الاعتماديات
composer install
npm install

# إعداد البيئة
cp .env.example .env
php artisan key:generate

# تهيئة قاعدة البيانات
php artisan migrate
php artisan db:seed

# تشغيل الخادم
php artisan serve
```

## بيانات الدخول الافتراضية
- البريد: admin@goldenplatform.com
- كلمة المرور: password

## الميزات
- إدارة المدارس والأقسام والصفوف
- إدارة المستخدمين (مدراء، معلمين، طلاب، أولياء أمور)
- نظام الصلاحيات والأدوار
- إدارة المواد والامتحانات
- تتبع الدرجات والحضور
- لوحات تحكم متعددة حسب الدور
- نظام الرسائل والإشعارات
- واجهة برمجة تطبيقات REST API
- دعم كامل للغة العربية وRTL

## الاختبارات

```bash
php artisan test
```

## الرخصة
MIT
