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
     * قائمة المستخدمين مع إمكانية البحث والتصفية وإحصائيات الطلبات
     */
    public function indexUser($searchUser = null, $perPageUser = 10)
    {
        $users = $this->model->when($searchUser, function ($query) use ($searchUser) {
            $query->where('name', 'like', "%{$searchUser}%")
                ->orWhere('email', 'like', "%{$searchUser}%")
                ->orWhere('phone', 'like', "%{$searchUser}%");
        })
        ->withCount([
            'orders as completed_orders_count' => function ($query) {
                $query->where('status', 1); // delivered
            },
            'orders as pending_orders_count' => function ($query) {
                $query->where('status', 0); // pending
            },
            'orders as total_orders_count'
        ])
        ->withSum('orders as total_delivery_fees', 'delivery_fee')
        ->paginate($perPageUser);

        // إضافة حسابات العمولة لكل مستخدم
        $users->getCollection()->transform(function ($user) {
            $totalDeliveryFees = $user->total_delivery_fees ?? 0;
            $commissionPercentage = $user->commission_percentage ?? 0;
            
            // حساب عمولة التطبيق
            $appCommission = ($totalDeliveryFees * $commissionPercentage) / 100;
            
            $user->app_percentage = $commissionPercentage;
            $user->app_commission = round($appCommission, 2);
            $user->total_delivery_fees = round($totalDeliveryFees, 2);
            
            return $user;
        });

        return $users;
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

    /**
     * تغيير حالة الموافقة للمستخدم
     */
    public function approveUser($userId, $approvalStatus)
    {
        try {
            $user = $this->model->find($userId);

            if (!$user) {
                return [
                    'status' => false,
                    'message' => 'المستخدم غير موجود'
                ];
            }

            $user->update(['is_approved' => $approvalStatus]);

            $message = $approvalStatus ? 'تم قبول المستخدم بنجاح' : 'تم رفض المستخدم بنجاح';

            return [
                'status' => true,
                'message' => $message,
                'data' => $user
            ];
        } catch (\Exception $e) {
            Log::error('User approval failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء تغيير حالة الموافقة'
            ];
        }
    }

    /**
     * تغيير كلمة المرور مع التأكيد
     */
    public function changePassword($userId, $newPassword, $confirmPassword)
    {
        try {
            $user = $this->model->find($userId);

            if (!$user) {
                return [
                    'status' => false,
                    'message' => 'المستخدم غير موجود'
                ];
            }

            // التحقق من تطابق كلمة المرور الجديدة مع التأكيد
            if ($newPassword !== $confirmPassword) {
                return [
                    'status' => false,
                    'message' => 'كلمة المرور الجديدة وتأكيد كلمة المرور غير متطابقتان'
                ];
            }

            // تحديث كلمة المرور
            $user->update(['password' => Hash::make($newPassword)]);

            return [
                'status' => true,
                'message' => 'تم تغيير كلمة المرور بنجاح'
            ];
        } catch (\Exception $e) {
            Log::error('Password change failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء تغيير كلمة المرور'
            ];
        }
    }

    /**
     * تغيير حالة النشاط للمستخدم
     */
    public function changeActiveStatus($userId, $isActive)
    {
        try {
            $user = $this->model->find($userId);

            if (!$user) {
                return [
                    'status' => false,
                    'message' => 'المستخدم غير موجود'
                ];
            }

            $user->update(['is_active' => $isActive]);

            $statusText = $isActive ? 'نشط' : 'غير نشط';

            return [
                'status' => true,
                'message' => "تم تغيير حالة المستخدم إلى: {$statusText}",
                'data' => $user
            ];
        } catch (\Exception $e) {
            Log::error('User active status change failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء تغيير حالة النشاط'
            ];
        }
    }

    /**
     * تغيير نسبة العمولة للمستخدم
     */
    public function changeCommissionPercentage($userId, $commissionPercentage)
    {
        try {
            $user = $this->model->find($userId);

            if (!$user) {
                return [
                    'status' => false,
                    'message' => 'المستخدم غير موجود'
                ];
            }

            $user->update(['commission_percentage' => $commissionPercentage]);

            return [
                'status' => true,
                'message' => "تم تحديث نسبة العمولة إلى: {$commissionPercentage}%",
                'data' => $user
            ];
        } catch (\Exception $e) {
            Log::error('Commission percentage change failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث نسبة العمولة'
            ];
        }
    }

    /**
     * تغيير حالة التوفر للسائق
     */
    public function changeAvailabilityStatus($userId, $isAvailable)
    {
        try {
            $user = $this->model->find($userId);

            if (!$user) {
                return [
                    'status' => false,
                    'message' => 'المستخدم غير موجود'
                ];
            }

            // التحقق من أن المستخدم سائق
            if ($user->role !== User::ROLE_DRIVER) {
                return [
                    'status' => false,
                    'message' => 'هذه الخاصية متاحة للسائقين فقط'
                ];
            }

            $user->update(['is_available' => $isAvailable]);

            $statusText = $isAvailable ? 'متاح' : 'غير متاح';

            return [
                'status' => true,
                'message' => "تم تحديث حالة التوفر إلى: {$statusText}",
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'is_available' => $user->is_available,
                    'status_text' => $statusText
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Availability status change failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة التوفر'
            ];
        }
    }

    /**
     * جلب السائقين المتاحين
     */
    public function getAvailableDrivers()
    {
        try {
            $availableDrivers = $this->model->where('role', User::ROLE_DRIVER)
                ->where('is_available', true)
                ->where('is_active', true)
                ->where('is_approved', true)
                ->select('id', 'name', 'phone', 'address', 'commission_percentage', 'is_available')
                ->get();

            return [
                'status' => true,
                'message' => 'قائمة السائقين المتاحين',
                'data' => [
                    'drivers' => $availableDrivers,
                    'count' => $availableDrivers->count()
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Get available drivers failed: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب السائقين المتاحين'
            ];
        }
    }
}
