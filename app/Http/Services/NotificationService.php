<?php

namespace App\Http\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    protected Notification $notificationModel;
    protected User $userModel;

    public function __construct(Notification $notificationModel, User $userModel)
    {
        $this->notificationModel = $notificationModel;
        $this->userModel = $userModel;
    }

    /**
     * إنشاء إشعار جديد
     */
    public function createNotification(array $data)
    {
        try {
            $notification = $this->notificationModel->create([
                'user_id' => $data['user_id'] ?? null,
                'target_role' => $data['target_role'] ?? null,
                'title' => $data['title'],
                'message' => $data['message'],
                'notifiable_id' => $data['notifiable_id'] ?? null,
                'notifiable_type' => $data['notifiable_type'] ?? null,
                'is_read' => false,
            ]);

            // إرسال Real-time notification (سيتم إضافته لاحقاً)
            $this->sendRealTimeNotification($notification);

            return [
                'status' => true,
                'message' => 'تم إنشاء الإشعار بنجاح',
                'data' => $notification
            ];
        } catch (\Exception $e) {
            Log::error('Create notification failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء إنشاء الإشعار'
            ];
        }
    }

    /**
     * إنشاء إشعار للدور (Role-based)
     */
    public function createRoleNotification($targetRole, $title, $message, $notifiable = null)
    {
        try {
            // للأدمن: إشعار واحد فقط للأدمن الأساسي
            if ($targetRole == Notification::ROLE_ADMIN) {
                $user = $this->userModel->where('role', $targetRole)
                    ->where('is_active', true)
                    ->first(); // أول أدمن نشط فقط
                
                if (!$user) {
                    return [
                        'status' => false,
                        'message' => 'لا يوجد أدمن نشط'
                    ];
                }

                $notificationData = [
                    'user_id' => $user->id,
                    'target_role' => $targetRole,
                    'title' => $title,
                    'message' => $message,
                ];

                if ($notifiable) {
                    $notificationData['notifiable_id'] = $notifiable->id;
                    $notificationData['notifiable_type'] = get_class($notifiable);
                }

                Log::info('Creating notification for admin', [
                    'user_id' => $user->id,
                    'title' => $title,
                    'notifiable_id' => $notifiable ? $notifiable->id : null
                ]);

                $notification = $this->notificationModel->create($notificationData);

                Log::info('Notification created successfully', [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id
                ]);

                // إرسال Real-time notification
                $this->sendRealTimeNotification($notification);

                return [
                    'status' => true,
                    'message' => 'تم إنشاء إشعار للأدمن',
                    'data' => $notification
                ];
            }

            // للسائقين: إشعار لكل السائقين النشطين
            $users = $this->userModel->where('role', $targetRole)
                ->where('is_active', true)
                ->get();

            $notifications = [];
            foreach ($users as $user) {
                $notificationData = [
                    'user_id' => $user->id,
                    'target_role' => $targetRole,
                    'title' => $title,
                    'message' => $message,
                ];

                if ($notifiable) {
                    $notificationData['notifiable_id'] = $notifiable->id;
                    $notificationData['notifiable_type'] = get_class($notifiable);
                }

                $notification = $this->notificationModel->create($notificationData);
                $notifications[] = $notification;

                // إرسال Real-time notification
                $this->sendRealTimeNotification($notification);
            }

            return [
                'status' => true,
                'message' => "تم إنشاء {count($notifications)} إشعار للدور المحدد",
                'data' => $notifications
            ];
        } catch (\Exception $e) {
            Log::error('Create role notification failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء إنشاء إشعارات الدور'
            ];
        }
    }

    /**
     * جلب إشعارات المستخدم
     */
    public function getUserNotifications($userId, $perPage = 15, $unreadOnly = false)
    {
        try {
            $query = $this->notificationModel->forUser($userId)
                ->with('notifiable')
                ->orderBy('created_at', 'desc');

            if ($unreadOnly) {
                $query->unread();
            }

            $notifications = $query->paginate($perPage);

            return [
                'status' => true,
                'message' => 'إشعارات المستخدم',
                'data' => $notifications
            ];
        } catch (\Exception $e) {
            Log::error('Get user notifications failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب الإشعارات'
            ];
        }
    }

    /**
     * جلب إشعارات الدور
     */
    public function getRoleNotifications($role, $perPage = 15)
    {
        try {
            $notifications = $this->notificationModel->forRole($role)
                ->with(['user', 'notifiable'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return [
                'status' => true,
                'message' => 'إشعارات الدور',
                'data' => $notifications
            ];
        } catch (\Exception $e) {
            Log::error('Get role notifications failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب إشعارات الدور'
            ];
        }
    }

    /**
     * تحديد إشعار كمقروء
     */
    public function markAsRead($notificationId, $userId = null)
    {
        try {
            $query = $this->notificationModel->where('id', $notificationId);
            
            if ($userId) {
                $query->where('user_id', $userId);
            }

            $notification = $query->first();

            if (!$notification) {
                return [
                    'status' => false,
                    'message' => 'الإشعار غير موجود'
                ];
            }

            $notification->markAsRead();

            return [
                'status' => true,
                'message' => 'تم تحديد الإشعار كمقروء',
                'data' => $notification
            ];
        } catch (\Exception $e) {
            Log::error('Mark notification as read failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث الإشعار'
            ];
        }
    }

    /**
     * تحديد جميع إشعارات المستخدم كمقروءة
     */
    public function markAllAsRead($userId)
    {
        try {
            $count = $this->notificationModel->forUser($userId)
                ->unread()
                ->update(['is_read' => true]);

            return [
                'status' => true,
                'message' => "تم تحديد {$count} إشعار كمقروء",
                'data' => ['marked_count' => $count]
            ];
        } catch (\Exception $e) {
            Log::error('Mark all notifications as read failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث الإشعارات'
            ];
        }
    }

    /**
     * حذف إشعار
     */
    public function deleteNotification($notificationId, $userId = null)
    {
        try {
            $query = $this->notificationModel->where('id', $notificationId);
            
            if ($userId) {
                $query->where('user_id', $userId);
            }

            $notification = $query->first();

            if (!$notification) {
                return [
                    'status' => false,
                    'message' => 'الإشعار غير موجود'
                ];
            }

            $notification->delete();

            return [
                'status' => true,
                'message' => 'تم حذف الإشعار بنجاح'
            ];
        } catch (\Exception $e) {
            Log::error('Delete notification failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء حذف الإشعار'
            ];
        }
    }

    /**
     * إحصائيات الإشعارات للمستخدم
     */
    public function getUserNotificationStats($userId)
    {
        try {
            $totalCount = $this->notificationModel->forUser($userId)->count();
            $unreadCount = $this->notificationModel->forUser($userId)->unread()->count();
            $readCount = $totalCount - $unreadCount;

            return [
                'status' => true,
                'message' => 'إحصائيات الإشعارات',
                'data' => [
                    'total_count' => $totalCount,
                    'unread_count' => $unreadCount,
                    'read_count' => $readCount
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Get notification stats failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات'
            ];
        }
    }

    /**
     * إرسال Real-time notification (سيتم تطويره لاحقاً)
     */
    private function sendRealTimeNotification($notification)
    {
        // TODO: إضافة Pusher/WebSocket integration
        // broadcast(new NotificationSent($notification));
        Log::info('Real-time notification sent', ['notification_id' => $notification->id]);
    }

    /**
     * إشعارات خاصة بالأوردرات
     */
    public function createOrderNotification($order, $type)
    {
        switch ($type) {
            case Notification::TYPE_ORDER_CREATED:
                // إشعار للسائقين بطلب جديد
                return $this->createRoleNotification(
                    Notification::ROLE_DRIVER,
                    'طلب جديد',
                    "طلب رقم #{$order->id} بانتظارك - {$order->customer_name}",
                    $order
                );

            case Notification::TYPE_ORDER_ACCEPTED:
                // إشعار للمحل بقبول الطلب
                $shopNotification = null;
                if ($order->addedBy) {
                    $shopNotification = $this->createNotification([
                        'user_id' => $order->addedBy->id,
                        'target_role' => Notification::ROLE_SHOP,
                        'title' => 'تم قبول الطلب',
                        'message' => "تم قبول طلب رقم #{$order->id} من قبل السائق",
                        'notifiable_id' => $order->id,
                        'notifiable_type' => get_class($order),
                    ]);
                }

                // إشعار للأدمن بقبول الطلب
                $adminNotification = $this->createRoleNotification(
                    Notification::ROLE_ADMIN,
                    'تم قبول طلب',
                    "تم قبول طلب رقم #{$order->id} من قبل السائق " . ($order->delivery->name ?? 'غير محدد'),
                    $order
                );

                return $shopNotification ?? $adminNotification;

            case Notification::TYPE_ORDER_DELIVERED:
                // إشعار للمحل بتسليم الطلب
                $shopNotification = null;
                if ($order->addedBy) {
                    $shopNotification = $this->createNotification([
                        'user_id' => $order->addedBy->id,
                        'target_role' => Notification::ROLE_SHOP,
                        'title' => 'تم تسليم الطلب',
                        'message' => "تم تسليم طلب رقم #{$order->id} للعميل",
                        'notifiable_id' => $order->id,
                        'notifiable_type' => get_class($order),
                    ]);
                }

                // إشعار للأدمن بتسليم الطلب
                $adminNotification = $this->createRoleNotification(
                    Notification::ROLE_ADMIN,
                    'تم توصيل طلب بنجاح',
                    "تم توصيل طلب رقم #{$order->id} بنجاح من قبل السائق " . ($order->delivery->name ?? 'غير محدد'),
                    $order
                );

                return $shopNotification ?? $adminNotification;
        }

        return ['status' => false, 'message' => 'نوع الإشعار غير مدعوم'];
    }

    /**
     * إشعارات خاصة بالمستخدمين
     */
    public function createUserNotification($user, $type)
    {
        switch ($type) {
            case Notification::TYPE_USER_REGISTRATION:
                // إشعار للأدمن بتسجيل مستخدم جديد
                return $this->createRoleNotification(
                    Notification::ROLE_ADMIN,
                    'طلب فتح حساب جديد',
                    'طلب فتح حساب جديد من (' . ($user->name ?? 'غير محدد') . ') في انتظار الموافقة',
                    $user
                );
        }

        return ['status' => false, 'message' => 'نوع الإشعار غير مدعوم'];
    }

    /**
     * إشعارات خاصة بالشكاوى
     */
    public function createComplaintNotification($complaint, $type)
    {
        switch ($type) {
            case Notification::TYPE_COMPLAINT_CREATED:
                // إشعار للأدمن بشكوى جديدة
                return $this->createRoleNotification(
                    Notification::ROLE_ADMIN,
                    'شكوى جديدة',
                    'شكوى جديدة من ' . ($complaint->user->name ?? 'مستخدم'),
                    $complaint
                );
        }

        return ['status' => false, 'message' => 'نوع الإشعار غير مدعوم'];
    }

    /**
     * جلب إشعارات المحلات للأدمن
     */
    public function getShopNotificationsForAdmin($perPage = 15)
    {
        try {
            $notifications = $this->notificationModel
                ->where('target_role', Notification::ROLE_SHOP)
                ->with(['user', 'notifiable'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return [
                'status' => true,
                'message' => 'إشعارات المحلات',
                'data' => $notifications
            ];
        } catch (\Exception $e) {
            Log::error('Get shop notifications for admin failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب إشعارات المحلات'
            ];
        }
    }

    /**
     * جلب جميع الإشعارات للأدمن (جميع الصلاحيات)
     */
    public function getAllNotificationsForAdmin($perPage = 15, $unreadOnly = false)
    {
        try {
            $query = $this->notificationModel
                ->whereIn('target_role', [Notification::ROLE_ADMIN, Notification::ROLE_DRIVER, Notification::ROLE_SHOP])
                ->with(['user', 'notifiable'])
                ->orderBy('created_at', 'desc');

            if ($unreadOnly) {
                $query->where('is_read', false);
            }

            $notifications = $query->paginate($perPage);

            return [
                'status' => true,
                'message' => $unreadOnly ? 'الإشعارات غير المقروءة للأدمن' : 'جميع الإشعارات للأدمن',
                'data' => $notifications
            ];
        } catch (\Exception $e) {
            Log::error('Get all notifications for admin failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب الإشعارات'
            ];
        }
    }

    /**
     * إحصائيات الإشعارات للأدمن (جميع الصلاحيات)
     */
    public function getAdminNotificationStats()
    {
        try {
            $totalCount = $this->notificationModel
                ->whereIn('target_role', [Notification::ROLE_ADMIN, Notification::ROLE_DRIVER, Notification::ROLE_SHOP])
                ->count();
                
            $unreadCount = $this->notificationModel
                ->whereIn('target_role', [Notification::ROLE_ADMIN, Notification::ROLE_DRIVER, Notification::ROLE_SHOP])
                ->where('is_read', false)
                ->count();
                
            $readCount = $totalCount - $unreadCount;

            return [
                'status' => true,
                'message' => 'إحصائيات إشعارات الأدمن',
                'data' => [
                    'total_count' => $totalCount,
                    'unread_count' => $unreadCount,
                    'read_count' => $readCount
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Get admin notification stats failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات'
            ];
        }
    }
}
