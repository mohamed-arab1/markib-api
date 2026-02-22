<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\Policy;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Default Settings
        $settings = [
            ['key' => 'site_name', 'value' => 'نور النيل', 'type' => 'string', 'group' => 'general', 'label_ar' => 'اسم الموقع'],
            ['key' => 'site_email', 'value' => 'info@nileboats.com', 'type' => 'string', 'group' => 'general', 'label_ar' => 'البريد الإلكتروني'],
            ['key' => 'site_phone', 'value' => '+201234567890', 'type' => 'string', 'group' => 'general', 'label_ar' => 'رقم الهاتف'],
            ['key' => 'booking_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'booking', 'label_ar' => 'تفعيل الحجز'],
            ['key' => 'min_booking_hours', 'value' => '24', 'type' => 'integer', 'group' => 'booking', 'label_ar' => 'الحد الأدنى للحجز (ساعات)'],
            ['key' => 'max_passengers', 'value' => '20', 'type' => 'integer', 'group' => 'booking', 'label_ar' => 'الحد الأقصى للركاب'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        // Default Policies
        $policies = [
            [
                'slug' => 'booking-policy',
                'title' => 'Booking Policy',
                'title_ar' => 'سياسة الحجز',
                'content' => 'Our booking policy...',
                'content_ar' => "سياسة الحجز لرحلات نور النيل:\n\n1. يجب الحجز قبل 24 ساعة على الأقل من موعد الرحلة.\n2. يتم تأكيد الحجز بعد الدفع الكامل.\n3. يجب التواجد في موقع الانطلاق قبل 15 دقيقة من موعد الرحلة.\n4. الأطفال أقل من 5 سنوات مجاناً.\n5. يرجى إحضار بطاقة الهوية أثناء الصعود.",
                'is_active' => true,
            ],
            [
                'slug' => 'cancellation-policy',
                'title' => 'Cancellation Policy',
                'title_ar' => 'سياسة الإلغاء',
                'content' => 'Our cancellation policy...',
                'content_ar' => "سياسة الإلغاء:\n\n1. الإلغاء قبل 48 ساعة: استرداد كامل المبلغ.\n2. الإلغاء قبل 24 ساعة: استرداد 50% من المبلغ.\n3. الإلغاء قبل أقل من 24 ساعة: لا يوجد استرداد.\n4. في حالة إلغاء الرحلة من قبلنا: استرداد كامل المبلغ.\n5. يمكن تغيير موعد الرحلة مرة واحدة مجاناً قبل 48 ساعة.",
                'is_active' => true,
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'title_ar' => 'سياسة الخصوصية',
                'content' => 'Our privacy policy...',
                'content_ar' => "سياسة الخصوصية:\n\n1. نحن نحترم خصوصيتك ونلتزم بحماية بياناتك الشخصية.\n2. نستخدم بياناتك فقط لمعالجة الحجوزات وتحسين خدماتنا.\n3. لن نشارك بياناتك مع أطراف ثالثة دون موافقتك.\n4. يمكنك طلب حذف بياناتك في أي وقت.\n5. نستخدم تقنيات تشفير حديثة لحماية معلوماتك.",
                'is_active' => true,
            ],
        ];

        foreach ($policies as $policy) {
            Policy::updateOrCreate(['slug' => $policy['slug']], $policy);
        }
    }
}
