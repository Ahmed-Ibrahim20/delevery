<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ComplaintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'complaint_text' => 'required|string|min:10|max:2000',
        ];

        // إضافة قواعد إضافية للتحديث
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['status'] = 'sometimes|integer|in:0,1,2,3';
            $rules['admin_notes'] = 'sometimes|string|max:1000';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'complaint_text.required' => 'نص الشكوى مطلوب',
            'complaint_text.min' => 'نص الشكوى يجب أن يكون على الأقل 10 أحرف',
            'complaint_text.max' => 'نص الشكوى يجب ألا يزيد عن 2000 حرف',
            'status.integer' => 'حالة الشكوى يجب أن تكون رقم صحيح',
            'status.in' => 'حالة الشكوى غير صحيحة',
            'admin_notes.max' => 'ملاحظات الإدارة يجب ألا تزيد عن 1000 حرف',
        ];
    }
}
