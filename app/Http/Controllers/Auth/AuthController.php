<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
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
                'role'     => 'required|in:0,1,2,3', // admin, driver, shop, other
                'image'    => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ], $this->messages());

            $data = [
                'name'     => $validated['name'],
                'phone'    => $validated['phone'],
                'role'     => $validated['role'],
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

            if (!Auth::attempt(['phone' => $validated['phone'], 'password' => $validated['password']])) {
                throw ValidationException::withMessages([
                    'phone' => ['بيانات تسجيل الدخول غير صحيحة.'],
                ]);
            }

            $user  = User::where('phone', $validated['phone'])->first();
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
            'password.confirmed'=> 'تأكيد كلمة المرور غير مطابق.',
            'password.min'      => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',

            'role.required'     => 'يجب اختيار الصلاحية.',
            'role.in'           => 'الصلاحية غير صحيحة.',

            'image.image'       => 'يجب أن يكون الملف صورة.',
            'image.mimes'       => 'الصورة يجب أن تكون jpg أو jpeg أو png.',
            'image.max'         => 'حجم الصورة يجب ألا يتجاوز 2MB.',
        ];
    }
}