<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * قائمة المستخدمين (مع بحث وتصفية)
     */
    public function index(Request $request)
    {
        $searchUser = $request->query('search');
        $perPageUser = $request->query('perPage', 10);

        $users = $this->userService->indexUser($searchUser, $perPageUser);
        // dd($users);
        return response()->json([
            'status' => true,
            'message' => 'قائمة المستخدمين',
            'data' => $users
        ]);
    }

    /**
     * إنشاء مستخدم جديد
     */
    public function store(UserRequest $request)
    {
        $result = $this->userService->storeUser($request->validated());

        return response()->json($result, $result['status'] ? 201 : 500);
    }

    /**
     * استرجاع مستخدم للتعديل أو العرض
     */
    public function show($id)
    {
        $user = $this->userService->editUser($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'المستخدم غير موجود'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'تفاصيل المستخدم',
            'data' => $user
        ]);
    }

    /**
     * تحديث بيانات مستخدم
     */
    public function update(UserRequest $request, $id)
    {
        $result = $this->userService->updateUser($request->validated(), $id);

        return response()->json($result, $result['status'] ? 200 : 500);
    }

    /**
     * حذف مستخدم
     */
    public function destroy($id)
    {
        $result = $this->userService->destroyUser($id);

        return response()->json($result, $result['status'] ? 200 : 404);
    }

    /**
     * تغيير حالة الموافقة للمستخدم
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'is_approved' => 'required|boolean'
        ]);

        $result = $this->userService->approveUser($id, $request->is_approved);

        return response()->json($result, $result['status'] ? 200 : 404);
    }

    /**
     * تغيير كلمة المرور
     */
    public function changePassword(Request $request, $id)
    {
        $request->validate([
            'new_password' => 'required|string|min:6',
            'confirm_password' => 'required|string|min:6'
        ]);

        $result = $this->userService->changePassword(
            $id,
            $request->new_password,
            $request->confirm_password
        );

        return response()->json($result, $result['status'] ? 200 : 400);
    }

    /**
     * تغيير حالة النشاط للمستخدم
     */
    public function changeActiveStatus(Request $request, $id)
    {
        $request->validate([
            'is_active' => 'required|boolean'
        ]);

        $result = $this->userService->changeActiveStatus($id, $request->is_active);

        return response()->json($result, $result['status'] ? 200 : 404);
    }

    /**
     * تغيير نسبة العمولة للمستخدم
     */
    public function changeCommissionPercentage(Request $request, $id)
    {
        $request->validate([
            'commission_percentage' => 'required|numeric|min:0|max:100'
        ]);

        $result = $this->userService->changeCommissionPercentage($id, $request->commission_percentage);

        return response()->json($result, $result['status'] ? 200 : 404);
    }
}
