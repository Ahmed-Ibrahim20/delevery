<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * أرقام الأدوار كـ Constants لسهولة التعامل
     */
    public const ROLE_ADMIN  = 0;
    public const ROLE_DRIVER = 1;
    public const ROLE_SHOP   = 2;
    public const ROLE_OTHER  = 3;

    /**
     * الحقول المسموح تعبئتها جماعياً
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'role',
        'password',
        'image',
        'address',
        'is_approved',
        'is_active',
        'commission_percentage',
        'user_add_id',
        'avatar',
        'notes',
        'created_by',
    ];

    /**
     * الحقول المخفية عند الإرجاع في JSON
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * تحويل أنواع البيانات تلقائياً
     */
    protected $casts = [
        'is_approved'           => 'boolean',
        'is_active'             => 'boolean',
        'commission_percentage' => 'decimal:2',
        'role'                  => 'integer',
    ];

    /**
     * علاقة مع المستخدم الذي أضافه
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * علاقة مع المستخدم الذي أضافه في user_add_id
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'user_add_id');
    }

    /**
     * علاقة مع المستخدمين اللي أضافهم هو
     */
    public function addedUsers()
    {
        return $this->hasMany(User::class, 'user_add_id');
    }

    /**
     * جلب اسم الدور كنص
     */
    public function getRoleNameAttribute()
    {
        return match ($this->role) {
            self::ROLE_ADMIN  => 'Admin',
            self::ROLE_DRIVER => 'Driver',
            self::ROLE_SHOP   => 'Shop',
            self::ROLE_OTHER  => 'Other',
            default           => 'Unknown',
        };
    }
}
