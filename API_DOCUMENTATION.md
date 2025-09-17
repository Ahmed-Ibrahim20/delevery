# API Documentation - User Management

## New Endpoints Added

### 1. User Approval API
**Endpoint:** `PUT /api/v1/dashboard/users/{id}/approve`

**Description:** تغيير حالة الموافقة للمستخدم (قبول أو رفض)

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "is_approved": true  // true للقبول، false للرفض
}
```

**Response Success (200):**
```json
{
    "status": true,
    "message": "تم قبول المستخدم بنجاح",
    "data": {
        "id": 1,
        "name": "Ahmed Ibrahim",
        "phone": "01234567890",
        "role": 1,
        "is_approved": true,
        "is_active": true,
        // ... باقي بيانات المستخدم
    }
}
```

**Response Error (404):**
```json
{
    "status": false,
    "message": "المستخدم غير موجود"
}
```

---

### 2. Change Password API
**Endpoint:** `PUT /api/v1/dashboard/users/{id}/change-password`

**Description:** تغيير كلمة المرور مع التأكيد (بدون الحاجة لكلمة المرور القديمة)

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "new_password": "newpassword123",
    "confirm_password": "newpassword123"
}
```

**Response Success (200):**
```json
{
    "status": true,
    "message": "تم تغيير كلمة المرور بنجاح"
}
```

**Response Errors:**
```json
// كلمة المرور الجديدة وتأكيدها غير متطابقتان (400)
{
    "status": false,
    "message": "كلمة المرور الجديدة وتأكيد كلمة المرور غير متطابقتان"
}

// المستخدم غير موجود (404)
{
    "status": false,
    "message": "المستخدم غير موجود"
}
```

---

## Validation Rules

### User Approval Endpoint:
- `is_approved`: required|boolean

### Change Password Endpoint:
- `new_password`: required|string|min:6
- `confirm_password`: required|string|min:6

---

## Security Features

### User Approval:
- يتطلب مصادقة باستخدام Sanctum token
- يتحقق من وجود المستخدم قبل التحديث
- يسجل العمليات في اللوج للمراجعة

### Change Password:
- يتأكد من تطابق كلمة المرور الجديدة مع التأكيد
- يشفر كلمة المرور الجديدة باستخدام Hash
- يسجل العمليات في اللوج للأمان

---

## Example Usage with cURL

### Approve User:
```bash
curl -X PUT "http://127.0.0.1:8000/api/v1/dashboard/users/1/approve" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"is_approved": true}'
```

### Change Password:
```bash
curl -X PUT "http://127.0.0.1:8000/api/v1/dashboard/users/1/change-password" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "new_password": "newpassword123", 
    "confirm_password": "newpassword123"
  }'
```
