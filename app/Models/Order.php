<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    // اسم الجدول (مش لازم لو نفس اسم الموديل بالجمع)
    protected $table = 'orders';

    // الأعمدة المسموح بالـ mass assignment
    protected $fillable = [
        'customer_name',
        'customer_phone',
        'customer_address',
        'delivery_fee',
        'total',
        'user_add_id',
        'delivery_id',
        'status',
        'notes',
        'application_percentage',
        'application_fee',
    ];
    protected $casts = [
        'application_fee' => 'decimal:2',
        'application_percentage' => 'decimal:2',
        // ... أي casts موجودة حالياً
    ];
    // mapping للحالات بالأرقام
    const STATUS = [
        0 => 'pending',
        1 => 'delivered',
        2 => 'cancelled',
        3 => 'complete',
    ];

    // دالة مساعدة ترجع النص بدل الرقم
    public function getStatusTextAttribute()
    {
        return self::STATUS[$this->status] ?? 'unknown';
    }

    // العلاقة مع المستخدم اللي أضاف الأوردر
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'user_add_id');
    }
    public function delivery()
    {
        return $this->belongsTo(User::class, 'delivery_id');
    }
}
