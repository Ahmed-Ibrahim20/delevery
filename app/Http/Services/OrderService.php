<?php

namespace App\Http\Services;

use App\Models\Order;
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
    public function indexOrder($searchOrder = null, $perPageOrder = 10)
    {
        return $this->model
            ->when($searchOrder, function ($query) use ($searchOrder) {
                $query->where('customer_name', 'like', "%{$searchOrder}%")
                    ->orWhere('customer_phone', 'like', "%{$searchOrder}%")
                    ->orWhere('customer_address', 'like', "%{$searchOrder}%");
            })
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

            $data['user_add_id'] = Auth::id();

            $order = $this->model->create($data);

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
                'notes'
            ]);

            $order->update($data);

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
}
