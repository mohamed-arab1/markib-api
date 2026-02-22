# المواقع والصور - Locations & Images

## رفع الصور للأماكن

### عبر Postman أو أي API Client:

**رفع صور لموقع:**
```
POST /api/admin/locations/{id}/images
Content-Type: multipart/form-data
Authorization: Bearer {token}

Body (form-data):
- images[]: ملف صورة (يمكن إضافة عدة ملفات)
```

**صيغ الصور المدعومة:** jpeg, png, jpg, webp (حتى 5MB لكل صورة)

**تعيين صورة رئيسية:**
```
POST /api/admin/locations/{id}/images/{image_id}/primary
Authorization: Bearer {token}
```

**حذف صورة:**
```
DELETE /api/admin/locations/{id}/images/{image_id}
Authorization: Bearer {token}
```

## ربط الرحلة بموقع

عند إنشاء أو تحديث رحلة من لوحة التحكم، أضف `location_id` في الـ request body.
