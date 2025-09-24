<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'target_role',
        'title',
        'message',
        'notifiable_id',
        'notifiable_type',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constants للأدوار
    const ROLE_ADMIN = 0;
    const ROLE_DRIVER = 1;
    const ROLE_SHOP = 2;

    // أنواع الإشعارات
    const TYPE_ORDER_CREATED = 'order_created';
    const TYPE_ORDER_ACCEPTED = 'order_accepted';
    const TYPE_ORDER_DELIVERED = 'order_delivered';
    const TYPE_ORDER_COMPLETED = 'order_completed';
    const TYPE_USER_REGISTRATION = 'user_registration';
    const TYPE_COMPLAINT_CREATED = 'complaint_created';

    /**
     * العلاقة مع المستخدم المستقبل للإشعار
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * العلاقة Polymorphic مع الكيان المرتبط (Order, Complaint, etc.)
     */
    public function notifiable()
    {
        return $this->morphTo();
    }

    /**
     * Scope للإشعارات غير المقروءة
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope للإشعارات المقروءة
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope للإشعارات حسب الدور
     */
    public function scopeForRole($query, $role)
    {
        return $query->where('target_role', $role);
    }

    /**
     * Scope للإشعارات حسب المستخدم
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * تحديد الإشعار كمقروء
     */
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    /**
     * تحديد الإشعار كغير مقروء
     */
    public function markAsUnread()
    {
        $this->update(['is_read' => false]);
    }

    /**
     * الحصول على اسم الدور كنص
     */
    public function getRoleNameAttribute()
    {
        return match ($this->target_role) {
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_DRIVER => 'Driver',
            self::ROLE_SHOP => 'Shop',
            default => 'Unknown',
        };
    }

    /**
     * الحصول على رابط الإشعار (إذا كان مطلوب)
     */
    public function getLinkAttribute()
    {
        return match ($this->notifiable_type) {
            'App\Models\Order' => "/orders/{$this->notifiable_id}",
            'App\Models\Complaint' => "/complaints/{$this->notifiable_id}",
            'App\Models\User' => "/users/{$this->notifiable_id}",
            default => null,
        };
    }
}
