<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Http\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * قائمة الطلبات (مع بحث وتصفية)
     */
    public function index(Request $request)
    {
        $searchOrder = $request->query('search');
        $perPageOrder = $request->query('perPage', 10);
        $userId = $request->query('user_id');

        $orders = $this->orderService->indexOrder($searchOrder, $perPageOrder, $userId);

        return response()->json([
            'status' => true,
            'message' => $userId ? 'قائمة طلبات المستخدم' : 'قائمة الطلبات',
            'data' => $orders
        ]);
    }

    /**
     * إنشاء طلب جديد
     */
    public function store(OrderRequest $request)
    {
        $result = $this->orderService->storeOrder($request->validated());

        return response()->json($result, $result['status'] ? 201 : 500);
    }

    /**
     * استرجاع تفاصيل طلب
     */
    public function show($id)
    {
        $order = $this->orderService->editOrder($id);

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'الطلب غير موجود'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'تفاصيل الطلب',
            'data' => $order
        ]);
    }

    /**
     * تحديث بيانات طلب
     */
    public function update(OrderRequest $request, $id)
    {
        $result = $this->orderService->updateOrder($request->validated(), $id);

        return response()->json($result, $result['status'] ? 200 : 500);
    }

    /**
     * حذف طلب
     */
    public function destroy($id)
    {
        $result = $this->orderService->destroyOrder($id);

        return response()->json($result, $result['status'] ? 200 : 404);
    }

    /**
     * تغيير حالة الطلب
     */
    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|integer|in:0,1,2,3'
        ]);

        $result = $this->orderService->changeOrderStatus($id, $request->status);

        return response()->json($result, $result['status'] ? 200 : 404);
    }

    /**
     * جلب الطلبات الجارية (status = 1)
     */
    public function getActiveOrders(Request $request)
    {
        $searchOrder = $request->query('search');
        $perPageOrder = $request->query('perPage', 10);

        $result = $this->orderService->getActiveOrders($searchOrder, $perPageOrder);

        return response()->json([
            'status' => true,
            'message' => 'قائمة الطلبات الجارية',
            'data' => $result
        ]);
    }
}
