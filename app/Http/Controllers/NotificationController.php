<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * جلب إشعارات المستخدم الحالي
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->query('perPage', 15);
        $unreadOnly = $request->query('unread_only', false);

        // إذا كان المستخدم أدمن، جلب إشعاراته + إشعارات المحلات
        if ($user->role === 0) {
            $result = $this->notificationService->getAllNotificationsForAdmin($perPage);
        } else {
            // للمستخدمين العاديين، جلب إشعاراتهم فقط
            $result = $this->notificationService->getUserNotifications($user->id, $perPage, $unreadOnly);
        }

        return response()->json($result, $result['status'] ? 200 : 500);
    }

    /**
     * جلب إشعارات غير مقروءة فقط
     */
    public function unread(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->query('perPage', 15);

        // إذا كان المستخدم أدمن، جلب إشعاراته غير المقروءة + إشعارات المحلات غير المقروءة
        if ($user->role === 0) {
            $result = $this->notificationService->getAllNotificationsForAdmin($perPage, true);
        } else {
            // للمستخدمين العاديين، جلب إشعاراتهم غير المقروءة فقط
            $result = $this->notificationService->getUserNotifications($user->id, $perPage, true);
        }

        return response()->json($result, $result['status'] ? 200 : 500);
    }

    /**
     * تحديد إشعار كمقروء
     */
    public function markAsRead($id)
    {
        $userId = Auth::user()->id;
        $result = $this->notificationService->markAsRead($id, $userId);

        return response()->json($result, $result['status'] ? 200 : 404);
    }

    /**
     * تحديد جميع الإشعارات كمقروءة
     */
    public function markAllAsRead()
    {
        $userId = Auth::user()->id;
        $result = $this->notificationService->markAllAsRead($userId);

        return response()->json($result, $result['status'] ? 200 : 500);
    }

    /**
     * حذف إشعار
     */
    public function destroy($id)
    {
        $userId = Auth::user()->id;
        $result = $this->notificationService->deleteNotification($id, $userId);

        return response()->json($result, $result['status'] ? 200 : 404);
    }

    /**
     * إحصائيات الإشعارات للمستخدم الحالي
     */
    public function stats()
    {
        $user = Auth::user();

        // إذا كان المستخدم أدمن، جلب إحصائيات إشعاراته + إشعارات المحلات
        if ($user->role === 0) {
            $result = $this->notificationService->getAdminNotificationStats();
        } else {
            // للمستخدمين العاديين، جلب إحصائياتهم فقط
            $result = $this->notificationService->getUserNotificationStats($user->id);
        }

        return response()->json($result, $result['status'] ? 200 : 500);
    }

    /**
     * جلب إشعارات الدور (للأدمن فقط)
     */
    public function roleNotifications(Request $request, $role)
    {
        // التحقق من أن المستخدم أدمن
        if (Auth::user()->role !== 0) {
            return response()->json([
                'status' => false,
                'message' => 'غير مسموح لك بالوصول لهذه البيانات'
            ], 403);
        }

        $perPage = $request->query('perPage', 15);
        $result = $this->notificationService->getRoleNotifications($role, $perPage);

        return response()->json($result, $result['status'] ? 200 : 500);
    }

    /**
     * إنشاء إشعار يدوي (للأدمن فقط)
     */
    public function create(Request $request)
    {
        // التحقق من أن المستخدم أدمن
        if (Auth::user()->role !== 0) {
            return response()->json([
                'status' => false,
                'message' => 'غير مسموح لك بإنشاء إشعارات'
            ], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'target_type' => 'required|in:user,role',
            'target_id' => 'required_if:target_type,user|integer',
            'target_role' => 'required_if:target_type,role|integer|in:0,1,2',
        ]);

        if ($request->target_type === 'user') {
            // إشعار لمستخدم محدد
            $result = $this->notificationService->createNotification([
                'user_id' => $request->target_id,
                'title' => $request->title,
                'message' => $request->message,
            ]);
        } else {
            // إشعار لدور محدد
            $result = $this->notificationService->createRoleNotification(
                $request->target_role,
                $request->title,
                $request->message
            );
        }

        return response()->json($result, $result['status'] ? 201 : 500);
    }

    /**
     * جلب تفاصيل إشعار محدد
     */
    public function show($id)
    {
        $userId = Auth::user()->id;
        
        try {
            $notification = \App\Models\Notification::where('id', $id)
                ->where('user_id', $userId)
                ->with('notifiable')
                ->first();

            if (!$notification) {
                return response()->json([
                    'status' => false,
                    'message' => 'الإشعار غير موجود'
                ], 404);
            }

            // تحديد الإشعار كمقروء عند عرضه
            if (!$notification->is_read) {
                $notification->markAsRead();
            }

            return response()->json([
                'status' => true,
                'message' => 'تفاصيل الإشعار',
                'data' => $notification
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب الإشعار'
            ], 500);
        }
    }

    /**
     * تبديل حالة القراءة للإشعار
     */
    public function toggleRead($id)
    {
        $userId = Auth::user()->id;
        
        try {
            $notification = \App\Models\Notification::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$notification) {
                return response()->json([
                    'status' => false,
                    'message' => 'الإشعار غير موجود'
                ], 404);
            }

            if ($notification->is_read) {
                $notification->markAsUnread();
                $message = 'تم تحديد الإشعار كغير مقروء';
            } else {
                $notification->markAsRead();
                $message = 'تم تحديد الإشعار كمقروء';
            }

            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => $notification
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث الإشعار'
            ], 500);
        }
    }

    /**
     * جلب إشعارات المحلات للأدمن
     */
    public function getShopNotifications(Request $request)
    {
        // التحقق من أن المستخدم أدمن
        if (Auth::user()->role !== 0) {
            return response()->json([
                'status' => false,
                'message' => 'غير مسموح لك بالوصول لهذه البيانات'
            ], 403);
        }

        $perPage = $request->query('perPage', 15);
        $result = $this->notificationService->getShopNotificationsForAdmin($perPage);

        return response()->json($result, $result['status'] ? 200 : 500);
    }

    /**
     * جلب جميع الإشعارات للأدمن (إشعاراته + إشعارات المحلات)
     */
    public function getAllForAdmin(Request $request)
    {
        // التحقق من أن المستخدم أدمن
        if (Auth::user()->role !== 0) {
            return response()->json([
                'status' => false,
                'message' => 'غير مسموح لك بالوصول لهذه البيانات'
            ], 403);
        }

        $perPage = $request->query('perPage', 15);
        $result = $this->notificationService->getAllNotificationsForAdmin($perPage);

        return response()->json($result, $result['status'] ? 200 : 500);
    }
}
