<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Events\UserRegistered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * تسجيل مستخدم جديد
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'     => 'required|string|max:100',
                'phone'    => 'required|string|max:20|unique:users,phone',
                // 'password' => 'required|string|confirmed|min:8',
                'role'     => 'required|in:1,2,3', // driver, shop, other
                'address' => 'nullable|string|max:255',
                'catogrey' => 'nullable|string|max:255',
                'store_name' => 'nullable|string|max:255',
                'image'    => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ], $this->messages());

            $data = [
                'name'     => $validated['name'],
                'phone'    => $validated['phone'],
                'role'     => $validated['role'],
                'address'     => $validated['address'] ?? null,
                'catogrey'     => $validated['catogrey'] ?? null,
                'store_name'     => $validated['catogrey'] ?? null,
                // 'password' => Hash::make($validated['password']),
            ];

            // التعامل مع الصورة (بنفس أسلوبك)
            if ($request->hasFile('image') && $request->file('image') instanceof \Illuminate\Http\UploadedFile) {
                $folder = public_path('users');
                if (!file_exists($folder)) {
                    mkdir($folder, 0777, true);
                }
                $filename = uniqid('user_') . '.' . $request->file('image')->getClientOriginalExtension();
                $request->file('image')->move($folder, $filename);
                $data['image'] = 'users/' . $filename;
            }

            $user = User::create($data);

            // إطلاق Event لإشعار الأدمن بطلب فتح حساب جديد
            event(new UserRegistered($user));

            return response()->json([
                'message' => 'تم إرسال طلب فتح الحساب بنجاح في انتظار الموافقة',
                'user'    => $user,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'حدثت أخطاء في التحقق من البيانات',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشل في تسجيل الحساب',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تسجيل الدخول
     */
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'phone'    => 'required|string',
                'password' => 'required|string',
            ], $this->messages());

            // محاولة تسجيل الدخول
            if (!Auth::attempt(['phone' => $validated['phone'], 'password' => $validated['password']])) {
                throw ValidationException::withMessages([
                    'phone' => ['بيانات تسجيل الدخول غير صحيحة.'],
                ]);
            }

            $user = User::where('phone', $validated['phone'])->first();

            // ✅ الشرط المهم: لازم يكون نشط
            if (!$user->is_active) {
                // لو مش نشط نرجعه برسالة خطأ ونمنع الدخول
                Auth::logout();
                return response()->json([
                    'message' => 'الحساب غير نشط، لا يمكنك تسجيل الدخول.',
                ], 403);
            }

            // لو نشط يعمل توكن
            $token = $user->createToken('API Token')->plainTextToken;

            return response()->json([
                'message' => 'تم تسجيل الدخول بنجاح',
                'token'   => $token,
                'role'    => $user->role,
                'user'    => $user,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'فشل في تسجيل الدخول',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء تسجيل الدخول',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    /**
     * تسجيل الخروج
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'تم تسجيل الخروج بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء تسجيل الخروج',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تغيير كلمة المرور
     */
    public function resetPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'old_password'          => 'required|string',
                'new_password'          => 'required|string|min:8|confirmed',
                // لازم تبعت new_password و new_password_confirmation
            ], [
                'old_password.required'  => 'كلمة المرور القديمة مطلوبة',
                'new_password.required'  => 'كلمة المرور الجديدة مطلوبة',
                'new_password.min'       => 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل',
                'new_password.confirmed' => 'تأكيد كلمة المرور غير مطابق',
            ]);

            /** @var \App\Models\User $user */
            $user = Auth::user();

            // التحقق من كلمة المرور القديمة
            if (!Hash::check($validated['old_password'], $user->password)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'كلمة المرور القديمة غير صحيحة'
                ], 400);
            }

            // تحديث كلمة المرور
            $user->password = Hash::make($validated['new_password']);
            $user->save();

            return response()->json([
                'status'  => true,
                'message' => 'تم تغيير كلمة المرور بنجاح'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'حدثت أخطاء في التحقق من البيانات',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء تغيير كلمة المرور',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب بيانات المستخدم الحالي
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * الرسائل المخصصة
     */
    protected function messages()
    {
        return [
            'name.required'     => 'الاسم مطلوب.',
            'name.string'       => 'يجب أن يكون الاسم نصًا.',
            'name.max'          => 'الاسم طويل جدًا.',

            'phone.required'    => 'رقم الهاتف مطلوب.',
            'phone.string'      => 'يجب أن يكون رقم الهاتف نصًا.',
            'phone.max'         => 'رقم الهاتف طويل جدًا.',
            'phone.unique'      => 'هذا الرقم مستخدم بالفعل.',

            'password.required' => 'كلمة المرور مطلوبة.',
            'password.string'   => 'يجب أن تكون كلمة المرور نصًا.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'password.min'      => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',

            'role.required'     => 'يجب اختيار الصلاحية.',
            'role.in'           => 'الصلاحية غير صحيحة.',

            'image.image'       => 'يجب أن يكون الملف صورة.',
            'image.mimes'       => 'الصورة يجب أن تكون jpg أو jpeg أو png.',
            'image.max'         => 'حجم الصورة يجب ألا يتجاوز 2MB.',
        ];
    }
}
