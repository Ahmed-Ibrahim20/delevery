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

### Change Driver Availability Status (Admin):
```bash
curl -X PUT "http://127.0.0.1:8000/api/v1/dashboard/users/2/change-availability-status" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "is_available": true
  }'
```

### Toggle My Availability (Driver):
```bash
curl -X PUT "http://127.0.0.1:8000/api/v1/dashboard/users/toggle-my-availability" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "is_available": false
  }'
```

### Get Available Drivers:
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/dashboard/users/available-drivers" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
    "status": true,
    "message": "قائمة السائقين المتاحين",
    "data": {
        "drivers": [
            {
                "id": 2,
                "name": "أحمد السائق",
                "phone": "01234567890",
                "address": "القاهرة",
                "commission_percentage": 15.00,
                "is_available": true
            }
        ],
        "count": 1
    }
}
```

---

# Reports API Documentation

## Overview
تقارير شاملة للأدمن والدليفري والمحلات تتضمن إحصائيات الطلبات والعمولات والأرباح.

---

## 1. Admin Report API
**Endpoint:** `GET /api/v1/dashboard/reports/admin`

**Description:** تقرير شامل للأدمن يعرض إحصائيات المنصة مع أفضل الأداء للمتاجر والسائقين

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Query Parameters:**
```
start_date: 2024-01-01 (optional)
end_date: 2024-12-31 (optional)
```

**Response Success (200):**
```json
{
    "status": true,
    "message": "تقرير الأدمن الشامل",
    "data": {
        "summary": {
            "completed_orders_count": 150,
            "total_orders_value": 45000.00,
            "total_delivery_fees": 3000.00,
            "shop_commission_total": 1200.50,
            "driver_commission_total": 450.00,
            "total_platform_revenue": 1650.50,
            "period": {
                "start_date": "2024-01-01",
                "end_date": "2024-12-31"
            }
        },
        "general_statistics": {
            "total_shops_count": 25,
            "total_drivers_count": 15,
            "active_shops_count": 18,
            "active_drivers_count": 12
        },
        "top_performance": {
            "top_shops": [
                {
                    "id": 3,
                    "name": "محل الكترونيات",
                    "phone": "01987654321",
                    "commission_percentage": 5.0,
                    "orders_count": 45,
                    "total_orders_value": 12000.00,
                    "commission_paid_to_platform": 600.00
                }
            ],
            "top_drivers": [
                {
                    "id": 2,
                    "name": "أحمد السائق",
                    "phone": "01555666777",
                    "commission_percentage": 15.0,
                    "orders_count": 38,
                    "total_delivery_fees": 950.00,
                    "commission_paid_to_platform": 142.50
                }
            ]
        }
    }
}
```

---

## 2. Comprehensive Report API
**Endpoint:** `GET /api/v1/dashboard/reports/comprehensive`

**Description:** تقرير شامل يتضمن تقارير جميع الدليفريز والمحلات

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Query Parameters:**
```
start_date: 2024-01-01 (optional)
end_date: 2024-12-31 (optional)
```

**Response Success (200):**
```json
{
    "status": true,
    "message": "التقرير الشامل",
    "data": {
        "admin_report": {
            "completed_orders_count": 150,
            "total_application_fee": 1500.75,
            "average_application_percentage": 10.5,
            "total_orders_value": 45000.00
        },
        "deliveries_reports": [
            {
                "delivery_info": {
                    "id": 2,
                    "name": "أحمد محمد",
                    "phone": "01234567890",
                    "commission_percentage": 15.0
                },
                "completed_orders_count": 25,
                "total_delivery_fees": 500.00,
                "total_commission": 75.00
            }
        ],
        "shops_reports": [
            {
                "shop_info": {
                    "id": 3,
                    "name": "محل الكترونيات",
                    "phone": "01987654321",
                    "commission_percentage": 5.0
                },
                "completed_orders_count": 40,
                "total_orders_value": 12000.00,
                "total_commission": 600.00
            }
        ],
        "summary": {
            "total_deliveries": 5,
            "total_shops": 8,
            "period": {
                "start_date": "2024-01-01",
                "end_date": "2024-12-31"
            }
        }
    }
}
```

---

## 3. Driver Report API
**Endpoint:** `GET /api/v1/dashboard/reports/delivery/{id}`

**Description:** تقرير سائق محدد مع صافي الأرباح

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Query Parameters:**
```
start_date: 2024-01-01 (optional)
end_date: 2024-12-31 (optional)
```

**Response Success (200):**
```json
{
    "status": true,
    "message": "تقرير السائق",
    "data": {
        "driver_info": {
            "id": 2,
            "name": "أحمد السائق",
            "phone": "01234567890",
            "commission_percentage": 15.0
        },
        "completed_orders_count": 25,
        "total_delivery_fees": 500.00,
        "application_percentage": 15.0,
        "application_commission": 75.00,
        "net_profit": 425.00,
        "period": {
            "start_date": "2024-01-01",
            "end_date": "2024-12-31"
        }
    }
}
```

---

## 4. Shop Report API
**Endpoint:** `GET /api/v1/dashboard/reports/shop/{id}`

**Description:** كل شيء مضبوط ومجهز حسب المتطلبات بالضبط! 🚀**

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Query Parameters:**
```
start_date: 2024-01-01 (optional)
end_date: 2024-12-31 (optional)
```

**Response Success (200):**
```json
{
    "status": true,
    "message": "تقرير المحل",
    "data": {
        "shop_info": {
            "id": 3,
            "name": "محل الكترونيات",
            "phone": "01987654321",
            "commission_percentage": 5.0
        },
        "completed_orders_count": 40,
        "total_orders_value": 12000.00,
        "total_delivery_fees": 800.00,
        "application_percentage": 5.0,
        "application_commission": 600.00,
        "net_profit": 11400.00,
        "period": {
            "start_date": "2024-01-01",
            "end_date": "2024-12-31"
        }
    }
}
```

---

## 5. My Delivery Report API
**Endpoint:** `GET /api/v1/dashboard/reports/my-delivery`

**Description:** تقرير الدليفري الحالي (للدليفري نفسه)

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Query Parameters:**
```
start_date: 2024-01-01 (optional)
end_date: 2024-12-31 (optional)
```

**Response:** نفس استجابة Driver Report API

---

## 6. My Shop Report API
**Endpoint:** `GET /api/v1/dashboard/reports/my-shop`

**Description:** تقرير المحل الحالي (للمحل نفسه)

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Query Parameters:**
```
start_date: 2024-01-01 (optional)
end_date: 2024-12-31 (optional)
```

**Response:** نفس استجابة Shop Report API

---

## Validation Rules

### All Report Endpoints:
- `start_date`: nullable|date
- `end_date`: nullable|date|after_or_equal:start_date

---

## Security Features

### Reports Security:
- يتطلب مصادقة باستخدام Sanctum token
- يتحقق من صحة المعرفات المرسلة
- يفلتر البيانات حسب الأدوار والصلاحيات
- يسجل العمليات في اللوج للمراجعة

---

## Example Usage with cURL

### Admin Report:
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/dashboard/reports/admin?start_date=2024-01-01&end_date=2024-12-31" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Delivery Report:
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/dashboard/reports/delivery/2?start_date=2024-01-01" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### My Shop Report:
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/dashboard/reports/my-shop" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Important Notes

1. **Order Status**: التقارير تعتمد على الطلبات المكتملة فقط (status = 3)
2. **Commission Calculation**: العمولات تحسب بناءً على النسبة المحددة لكل مستخدم
3. **Date Filtering**: يمكن فلترة التقارير بالتاريخ أو عرض كل البيانات
4. **User Roles**: 
   - Admin (0): يمكنه رؤية جميع التقارير
   - Driver (1): يمكنه رؤية تقريره فقط
   - Shop (2): يمكنه رؤية تقريره فقط

---

# Orders API Documentation

## Get Active Orders (Status = 1)

**Endpoint:** `GET /api/v1/dashboard/orders/active`

**Description:** جلب جميع الطلبات الجارية (المسلمة) التي حالتها status = 1

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Query Parameters:**
```
perPage: 15 (optional) - عدد الطلبات في الصفحة
search: "أحمد" (optional) - البحث في اسم العميل أو الهاتف أو العنوان
```

**Example Request:**
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/dashboard/orders/active?perPage=15&search=أحمد" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response Success (200):**
```json
{
    "status": true,
    "message": "قائمة الطلبات الجارية",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 15,
                "customer_name": "أحمد محمد",
                "customer_phone": "01234567890",
                "customer_address": "القاهرة، مصر الجديدة",
                "delivery_fee": 25.00,
                "total": 150.00,
                "status": 1,
                "application_percentage": 15.00,
                "application_fee": 3.75,
                "notes": "طلب عاجل",
                "created_at": "2024-01-15T14:30:00.000000Z",
                "updated_at": "2024-01-15T15:45:00.000000Z",
                "added_by": {
                    "id": 3,
                    "name": "محل الإلكترونيات",
                    "phone": "01111222333",
                    "role": 2
                },
                "delivery": {
                    "id": 5,
                    "name": "سائق أحمد",
                    "phone": "01555666777",
                    "role": 1,
                    "is_available": false
                }
            }
        ],
        "total": 25,
        "per_page": 15,
        "current_page": 1,
        "last_page": 2,
        "from": 1,
        "to": 15
    }
}
```

**Features:**
- ✅ عرض الطلبات الجارية فقط (status = 1)
- ✅ البحث في اسم العميل، الهاتف، أو العنوان
- ✅ Pagination للتعامل مع الطلبات الكثيرة
- ✅ عرض بيانات المحل الذي أضاف الطلب
- ✅ عرض بيانات السائق المكلف بالتوصيل
- ✅ عرض نسبة ومبلغ عمولة التطبيق
- ✅ ترتيب حسب تاريخ الإنشاء (الأحدث أولاً)

**Use Cases:**
- متابعة الطلبات الجارية للأدمن
- عرض الطلبات النشطة للمحلات
- تتبع أداء السائقين
- إحصائيات الطلبات الجارية
