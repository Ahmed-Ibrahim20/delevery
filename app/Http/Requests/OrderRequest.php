<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class OrderRequest extends FormRequest
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
        $orderId = $this->route('order'); // في حالة التحديث

        $commonRules = [
            'customer_name'   => 'required|string|max:150',
            'customer_phone'  => 'required|string|max:30',
            'customer_address'=> 'required|string|max:255',
            'delivery_fee'    => 'required|numeric|min:0',
            'total'           => 'required|numeric|min:0',
            'user_add_id'     => 'nullable|exists:users,id',
            'delivery_id'     => 'nullable|exists:users,id',
            'status'          => [
                'required',
                'integer',
                Rule::in([0, 1, 2,3]) // 0=pending,1=delivered,2=cancelled
            ],
            'notes'           => 'nullable|string|max:500',
        ];

        if ($this->isMethod('POST')) {
            return $commonRules; // إنشاء طلب جديد
        }

        if ($this->isMethod('PATCH') || $this->isMethod('PUT')) {
            return $commonRules; // تحديث طلب
        }

        return $commonRules;
    }

    /**
     * رسائل الخطأ
     */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'اسم العميل مطلوب.',
            'customer_name.max' => 'اسم العميل يجب ألا يتجاوز 150 حرف.',
            'customer_phone.required' => 'رقم الهاتف مطلوب.',
            'customer_phone.max' => 'رقم الهاتف يجب ألا يتجاوز 30 رقم.',
            'customer_address.required' => 'العنوان مطلوب.',
            'delivery_fee.required' => 'تكلفة التوصيل مطلوبة.',
            'delivery_fee.numeric' => 'تكلفة التوصيل يجب أن تكون رقم.',
            'total.required' => 'الإجمالي مطلوب.',
            'total.numeric' => 'الإجمالي يجب أن يكون رقم.',
            'status.required' => 'حالة الطلب مطلوبة.',
            'status.in' => 'قيمة حالة الطلب غير صحيحة. (0: Pending, 1: Delivered, 2: Cancelled)',
            'user_add_id.exists' => 'المستخدم المُضيف غير موجود.',
            'delivery_id.exists' => 'المستخدم المُضيف غير موجود.',
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
        if ($this->has('customer_phone')) {
            $this->merge([
                'customer_phone' => preg_replace('/[^0-9]/', '', $this->customer_phone)
            ]);
        }
    }
}

