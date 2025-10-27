<?php

namespace App\Http\Services;

use App\Models\Order;
use App\Models\User;
use App\Events\OrderCreated;
use App\Events\OrderAccepted;
use App\Events\OrderDelivered;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class OrderService
{
    protected Order $model;

    public function __construct(Order $model)
    {
        $this->model = $model;
    }

    /**
     * قائمة الطلبات مع إمكانية البحث والتصفية
     */
    public function indexOrder($searchOrder = null, $perPageOrder = 10, $userId = null)
    {
        return $this->model
            ->when($userId, function ($query) use ($userId) {
                $query->where('user_add_id', $userId);
            })
            ->when($searchOrder, function ($query) use ($searchOrder) {
                $query->where('customer_name', 'like', "%{$searchOrder}%")
                    ->orWhere('customer_phone', 'like', "%{$searchOrder}%")
                    ->orWhere('customer_address', 'like', "%{$searchOrder}%");
            })
            ->with('addedBy:id,name,phone,role,address,store_name', 'delivery:id,name,phone,role')
            ->orderBy('id', 'desc')
            ->paginate($perPageOrder);
    }

    /**
     * إنشاء طلب جديد
     */
    public function storeOrder(array $requestData)
    {
        try {
            $data = Arr::only($requestData, [
                'customer_name',
                'customer_phone',
                'customer_address',
                'delivery_fee',
                'total',
                'delivery_id',
                'status',
                'notes'
            ]);

            // من أضاف الطلب (المستخدم الحالي)
            $data['user_add_id'] = Auth::id();

            // جلب المستخدم الحالي ونسبة العمولة المخزنة في users.commission_percentage
            $user = Auth::user();
            $commissionPercentage = $user->commission_percentage ?? 0; // نسبة مئوية مثل 10 أو 5.5

            // قيمة التوصيل المستخدمة للحساب (تحويل إلى float للتأكد)
            $deliveryFee = isset($data['delivery_fee']) ? (float) $data['delivery_fee'] : 0.0;

            // حساب المبلغ المستحق للتطبيق بناءً على النسبة
            $applicationFee = round($deliveryFee * ($commissionPercentage / 100), 2);

            // حفظ النسبة والمبلغ في بيانات الأوردر قبل الإنشاء
            $data['application_percentage'] = $commissionPercentage;
            $data['application_fee'] = $applicationFee;

            $order = $this->model->create($data);

            // إطلاق Event لإشعار السائقين بطلب جديد
            event(new OrderCreated($order));

            return [
                'status' => true,
                'message' => 'تم إنشاء الطلب بنجاح',
                'data' => $order
            ];
        } catch (\Exception $e) {
            Log::error('Order creation failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء إنشاء الطلب'
            ];
        }
    }

    /**
     * استرجاع بيانات طلب للتعديل
     */
    public function editOrder($orderId)
    {
        return $this->model->find($orderId);
    }

    /**
     * تحديث بيانات طلب
     */
    public function updateOrder(array $requestData, $orderId)
    {
        try {
            $order = $this->model->find($orderId);

            if (!$order) {
                return [
                    'status' => false,
                    'message' => 'الطلب غير موجود'
                ];
            }

            $data = Arr::only($requestData, [
                'customer_name',
                'customer_phone',
                'customer_address',
                'delivery_fee',
                'total',
                'delivery_id',
                'status',
                'notes',
                'user_add_id' // لو مسموح تغيير صاحب الأوردر
            ]);

            // نحدد أي مستخدم نأخذ منه نسبة العمولة:
            // إذا تم تمرير user_add_id في التحديث نستخدمه، وإلا نستخدم صاحب الأوردر الحالي
            $commissionUserId = $data['user_add_id'] ?? $order->user_add_id ?? null;
            $commissionPercentage = 0;

            if ($commissionUserId) {
                $commissionUser = \App\Models\User::find($commissionUserId);
                $commissionPercentage = $commissionUser->commission_percentage ?? 0;
            }

            // نحدد delivery_fee الفعلية (من البيانات الجديدة أو من الأوردر الحالي)
            $deliveryFee = isset($data['delivery_fee']) ? (float) $data['delivery_fee'] : ((float) $order->delivery_fee ?? 0.0);
            $applicationFee = round($deliveryFee * ($commissionPercentage / 100), 2);

            // نحفظ النسبة والمبلغ في المصفوفة ليتم حفظها في الأوردر
            $data['application_percentage'] = $commissionPercentage;
            $data['application_fee'] = $applicationFee;

            // حفظ الحالة القديمة للمقارنة
            $oldStatus = $order->status;

            $order->update($data);

            // لو حابب ترجّع البيانات المحدثة من قاعدة البيانات بدل الكائن القديم:
            $order->refresh();

            // إطلاق Events حسب تغيير الحالة
            $this->handleStatusChangeEvents($order, $oldStatus);

            return [
                'status' => true,
                'message' => 'تم تحديث بيانات الطلب بنجاح',
                'data' => $order
            ];
        } catch (\Exception $e) {
            Log::error('Order update failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث الطلب'
            ];
        }
    }

    /**
     * حذف طلب
     */
    public function destroyOrder($orderId)
    {
        try {
            $order = $this->model->find($orderId);

            if (!$order) {
                return [
                    'status' => false,
                    'message' => 'الطلب غير موجود'
                ];
            }

            $order->delete();

            return [
                'status' => true,
                'message' => 'تم حذف الطلب بنجاح'
            ];
        } catch (\Exception $e) {
            Log::error('Order deletion failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء حذف الطلب'
            ];
        }
    }

    /**
     * تغيير حالة الطلب
     */
    public function changeOrderStatus($orderId, $status)
    {
        try {
            $order = $this->model->find($orderId);

            if (!$order) {
                return [
                    'status' => false,
                    'message' => 'الطلب غير موجود'
                ];
            }

            // تحقق من صلاحية وإتاحة السائق عند محاولة قبول الطلب (status = 1)
            if ((int)$status === 1) {
                $driver = Auth::user();

                if (!$driver || $driver->role !== User::ROLE_DRIVER) {
                    return [
                        'status' => false,
                        'message' => 'هذه العملية مسموحة للسائقين فقط'
                    ];
                }

                if (!$driver->is_available) {
                    return [
                        'status' => false,
                        'message' => 'لا يمكنك قبول الطلب حالياً لأن حالتك: مشغول'
                    ];
                }
            }

            // حفظ الحالة القديمة للمقارنة
            $oldStatus = $order->status;

            // تحديث الحالة وربطها بالدليفري (المستخدم الحالي عند القبول)
            $updateData = ['status' => $status];
            if ((int)$status === 1) {
                $updateData['delivery_id'] = Auth::id();
            }
            $order->update($updateData);

            // السائق يتحكم في حالته بنفسه - مفيش تحكم أوتوماتيكي

            // إطلاق Events حسب تغيير الحالة
            $this->handleStatusChangeEvents($order, $oldStatus);

            $statusText = match ($status) {
                0 => 'قيد الانتظار',
                1 => 'تم القبول',
                3 => 'تم توصيله بنجاح',
                default => 'غير معروف'
            };

            return [
                'status' => true,
                'message' => "تم تغيير حالة الطلب إلى: {$statusText}",
                'data' => $order->load('addedBy') // تحميل بيانات اللي أضاف الأوردر برضه
            ];
        } catch (\Exception $e) {
            Log::error('Order status change failed: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء تغيير حالة الطلب'
            ];
        }
    }

    /**
     * معالجة Events حسب تغيير حالة الطلب
     */
    private function handleStatusChangeEvents($order, $oldStatus)
    {
        try {
            // تحديث البيانات مع العلاقات
            $order->load(['addedBy', 'delivery']);

            // إذا تم قبول الطلب (من pending إلى أي حالة أخرى وتم تعيين سائق)
            if ($oldStatus == 1 && $order->status != 10 && $order->delivery_id) {
                event(new OrderAccepted($order));
                Log::info('OrderAccepted event fired', ['order_id' => $order->id]);
            }

            // إذا تم تسليم الطلب (status = 1)
            if ($order->status == 3 && $oldStatus != 3) {
                event(new OrderDelivered($order));
                Log::info('OrderDelivered event fired', ['order_id' => $order->id]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle status change events: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $order->status
            ]);
        }
    }

    /**
     * جلب الطلبات الجارية (status = 1)
     */
    public function getActiveOrders($searchOrder = null, $perPageOrder = 10)
    {
        try {
            $orders = $this->model
                ->where('status', 1) // الطلبات الجارية فقط
                ->when($searchOrder, function ($query) use ($searchOrder) {
                    $query->where('customer_name', 'like', "%{$searchOrder}%")
                        ->orWhere('customer_phone', 'like', "%{$searchOrder}%")
                        ->orWhere('customer_address', 'like', "%{$searchOrder}%");
                })
                ->with([
                    'addedBy:id,name,phone,role',
                    'delivery:id,name,phone,role,is_available'
                ])
                ->orderBy('created_at', 'desc')
                ->paginate($perPageOrder);

            return $orders;
        } catch (\Exception $e) {
            Log::error('Get active orders failed: ' . $e->getMessage());
            return collect(); // إرجاع collection فارغة في حالة الخطأ
        }
    }
}
