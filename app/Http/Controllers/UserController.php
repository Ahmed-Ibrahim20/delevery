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
}
