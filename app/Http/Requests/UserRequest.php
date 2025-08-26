<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\User;

class UserRequest extends FormRequest
{
    /**
     * هل المستخدم مخوّل لتنفيذ الطلب؟
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق
     */
    public function rules(): array
    {
        $userId = $this->route('user');

        $commonRules = [
            'name' => 'required|string|max:100',
            'email' => [
                'nullable', // خليت الإيميل اختياري بناءً على الموديل
                'string',
                'email',
                'max:150',
                Rule::unique('users')->ignore($userId),
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users')->ignore($userId),
            ],
            'address' => 'nullable|string|max:255',
            'user_add_id' => 'nullable|exists:users,id',
            'role' => [
                'required',
                'integer',
                Rule::in([
                    User::ROLE_ADMIN,
                    User::ROLE_DRIVER,
                    User::ROLE_SHOP,
                    User::ROLE_OTHER
                ]),
            ],
            'is_approved' => 'nullable|boolean',
            'is_active'   => 'nullable|boolean',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'avatar' => 'nullable|image|max:2048', // صورة اختيارية
            'notes' => 'nullable|string|max:1000',
        ];

        if ($this->isMethod('POST')) {
            return array_merge($commonRules, [
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);
        }

        if ($this->isMethod('PATCH') || $this->isMethod('PUT')) {
            return array_merge($commonRules, [
                'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            ]);
        }

        return $commonRules;
    }

    /**
     * رسائل الخطأ
     */
    public function messages(): array
    {
        return [
            'name.required' => 'حقل الاسم مطلوب.',
            'name.max' => 'يجب ألا يتجاوز الاسم 100 حرف.',
            'email.email' => 'يجب أن يكون البريد الإلكتروني صالحاً.',
            'email.max' => 'يجب ألا يتجاوز البريد الإلكتروني 150 حرف.',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.max' => 'يجب ألا يتجاوز رقم الهاتف 20 رقماً.',
            'phone.unique' => 'رقم الهاتف مستخدم بالفعل.',
            'password.required' => 'حقل كلمة المرور مطلوب.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
            'role.required' => 'حقل الصلاحية مطلوب.',
            'role.in' => 'قيمة الصلاحية غير صحيحة. (0: Admin, 1: Driver, 2: Shop, 3: Other)',
            'user_add_id.exists' => 'المستخدم المُضيف غير موجود.',
            'commission_percentage.numeric' => 'النسبة يجب أن تكون رقم.',
            'commission_percentage.min' => 'النسبة يجب ألا تقل عن 0.',
            'commission_percentage.max' => 'النسبة يجب ألا تزيد عن 100.',
            'avatar.image' => 'الصورة يجب أن تكون بصيغة صحيحة.',
            'avatar.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',
        ];
    }

    /**
     * عند فشل التحقق
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors()
        ], 422));
    }

    /**
     * تجهيز البيانات قبل التحقق
     */
    protected function prepareForValidation()
    {
        if ($this->has('email') && $this->email !== null) {
            $this->merge([
                'email' => strtolower(trim($this->email))
            ]);
        }

        if ($this->has('phone')) {
            $this->merge([
                'phone' => preg_replace('/[^0-9]/', '', $this->phone)
            ]);
        }
    }
}
