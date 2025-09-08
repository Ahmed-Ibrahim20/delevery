<?php

namespace App\Http\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserService
{
    protected User $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * قائمة المستخدمين مع إمكانية البحث والتصفية
     */
    public function indexUser($searchUser = null, $perPageUser = 10)
    {
        return $this->model->when($searchUser, function ($query) use ($searchUser) {
            $query->where('name', 'like', "%{$searchUser}%")
                ->orWhere('email', 'like', "%{$searchUser}%")
                ->orWhere('phone', 'like', "%{$searchUser}%");
        })->paginate($perPageUser);
    }

    /**
     * إنشاء مستخدم جديد
     */
    public function storeUser(array $requestData)
    {
        try {
            $data = Arr::only($requestData, [
                'name',
                // 'email',
                'phone',
                'password',
                'catogrey',
                'address',
                'role',
                'is_approved',
                'is_active',
                'commission_percentage',
                'notes'
            ]);

            $data['password'] = Hash::make($data['password']);
            $data['user_add_id'] = Auth::id();

            // رفع الصورة إن وجدت
            if (!empty($requestData['avatar']) && $requestData['avatar']->isValid()) {
                $data['avatar'] = $requestData['avatar']->store('avatars', 'public');
            }

            $user = $this->model->create($data);

            return [
                'status' => true,
                'message' => 'تم إنشاء المستخدم بنجاح',
                'data' => $user
            ];
        } catch (\Exception $e) {
            Log::error('User creation failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء إنشاء المستخدم'
            ];
        }
    }

    /**
     * استرجاع بيانات مستخدم للتعديل
     */
    public function editUser($userId)
    {
        return $this->model->find($userId);
    }

    /**
     * تحديث بيانات مستخدم
     */
    public function updateUser(array $requestData, $userId)
    {
        try {
            $user = $this->model->find($userId);

            if (!$user) {
                return [
                    'status' => false,
                    'message' => 'المستخدم غير موجود'
                ];
            }

            $data = Arr::only($requestData, [
                'name',
                // 'email',
                'phone',
                'address',
                'role',
                'catogrey',
                'is_approved',
                'is_active',
                'commission_percentage',
                'notes'
            ]);

            // إضافة كلمة السر فقط لو موجودة
            if (!empty($requestData['password'])) {
                $data['password'] = Hash::make($requestData['password']);
            }

            // تحديث الصورة لو موجودة
            if (!empty($requestData['avatar']) && $requestData['avatar']->isValid()) {
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $data['avatar'] = $requestData['avatar']->store('avatars', 'public');
            }

            $user->update($data);

            return [
                'status' => true,
                'message' => 'تم تحديث بيانات المستخدم بنجاح',
                'data' => $user
            ];
        } catch (\Exception $e) {
            Log::error('User update failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء التحديث'
            ];
        }
    }

    /**
     * حذف مستخدم
     */
    public function destroyUser($userId)
    {
        try {
            $user = $this->model->find($userId);

            if (!$user) {
                return [
                    'status' => false,
                    'message' => 'المستخدم غير موجود'
                ];
            }

            // حذف الصورة لو موجودة
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->delete();

            return [
                'status' => true,
                'message' => 'تم حذف المستخدم بنجاح'
            ];
        } catch (\Exception $e) {
            Log::error('User deletion failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء حذف المستخدم'
            ];
        }
    }
}
