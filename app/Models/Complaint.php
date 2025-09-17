<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Complaint extends Model
{
    use HasFactory;

    // اسم الجدول
    protected $table = 'complaints';

    // الأعمدة المسموح بالـ mass assignment
    protected $fillable = [
        'complaint_text',
        'user_id',
        'status',
        'admin_notes',
    ];

    // mapping للحالات بالأرقام
    const STATUS = [
        0 => 'new',
        1 => 'under_review',
        2 => 'completed',
        3 => 'rejected',
    ];

    // دالة مساعدة ترجع النص بدل الرقم
    public function getStatusTextAttribute()
    {
        return self::STATUS[$this->status] ?? 'unknown';
    }

    // العلاقة مع المستخدم الذي أضاف الشكوى
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
