<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AvailabilityRequest extends FormRequest
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
        return [
            'is_available' => 'required|boolean',
        ];
    }

    /**
     * رسائل الخطأ
     */
    public function messages(): array
    {
        return [
            'is_available.required' => 'حقل حالة التوفر مطلوب.',
            'is_available.boolean' => 'حقل حالة التوفر يجب أن يكون true أو false.',
        ];
    }

    /**
     * عند فشل التحقق
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => 'خطأ في البيانات المرسلة',
            'errors' => $validator->errors()
        ], 422));
    }
}
