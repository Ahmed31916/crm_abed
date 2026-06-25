<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

/**
 * ============================================================
 * UserRequest — v6 modifications
 * ============================================================
 *
 * القواعد المُعدّلة:
 *
 * 1) password:
 *    - مطلوب عند الإنشاء (store): required + confirmed + Password::defaults()
 *    - اختياري عند التعديل (update): nullable + confirmed + Password::defaults()
 *      (المتحكم لا يحدّث الباسوورد إلا إذا أُرسل وغير فارغ)
 *
 * 2) password_confirmation:
 *    - nullable دائماً، لكن confirmed rule تتطلبه إن أُرسل password
 *
 * 3) license_key:
 *    - nullable|string|max:255 — يظهر في الفورم (create + edit)
 *    - للشركة: إن لم يُرسل يُورَّث من الشركة المالكة في المتحكم
 *
 * 4) hardware_id:
 *    - nullable|string|max:255 — يظهر في الفورم (create + edit)
 *    - للشركة: إن لم يُرسل يُورَّث من الشركة المالكة في المتحكم
 *
 * 5) roles:
 *    - nullable|string — أصبح اختيارياً للشركة (موظف بدون دور مسموح)
 *
 * ملاحظة: يتم تطبيق قواعد password بشكل مشروط حسب نوع الطلب (POST vs PUT/PATCH).
 */
class UserRequest extends FormRequest
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
        // كشف نوع العملية: store (POST) vs update (PUT/PATCH)
        $isUpdate = in_array($this->method(), ['PUT', 'PATCH']);

        // ============================================================
        // قاعدة الباسوورد: إلزامية عند الإنشاء، اختيارية عند التعديل
        // ============================================================
        // عند التعديل: إن أُرسل password وغير فارغ، نطبّق قواعد Password::defaults()
        // وإن لم يُرسل، لا نتحقق منه إطلاقاً.
        if ($isUpdate) {
            $passwordRule = ['nullable', 'confirmed', Rules\Password::defaults()];
        } else {
            $passwordRule = ['required', 'confirmed', Rules\Password::defaults()];
        }

        // قاعدة الإيميل: unique تتجاهل المستخدم الحالي عند التعديل
        $emailRule = $isUpdate
            ? 'required|string|lowercase|email|max:255|unique:users,email,' . $this->route('user')
            : 'required|string|lowercase|email|max:255|unique:users,email';

        return [
            'name'                 => 'required|string|max:255',
            'email'                => $emailRule,
            'password'             => $passwordRule,
            'password_confirmation'=> 'nullable|string',
            'roles'                => 'nullable|string|max:255',
            // ============================================================
            // الحقول الجديدة v6 — تظهر في الفورم وتُحفظ في store و update
            // ============================================================
            'license_key'          => 'nullable|string|max:255',
            'hardware_id'          => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name'                  => __('Name'),
            'email'                 => __('Email'),
            'password'              => __('Password'),
            'password_confirmation' => __('Password Confirmation'),
            'roles'                 => __('Role'),
            'license_key'           => __('License Key'),
            'hardware_id'           => __('Hardware ID'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'      => __('The name field is required.'),
            'email.required'     => __('The email field is required.'),
            'email.email'        => __('Please enter a valid email address.'),
            'email.unique'       => __('This email is already registered.'),
            'password.required'  => __('The password field is required.'),
            'password.confirmed' => __('The password confirmation does not match.'),
            'license_key.max'    => __('The license key may not be greater than 255 characters.'),
            'hardware_id.max'    => __('The hardware ID may not be greater than 255 characters.'),
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * تطبيق trim على الحقول النصية وتحويل السلاسل الفارغة إلى null
     * حتى لا تُخزَّن قيم فارغة في قاعدة البيانات.
     */
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach (['license_key', 'hardware_id', 'name', 'email'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
                if ($data[$field] === '') {
                    $data[$field] = null;
                }
            }
        }

        // إن لم يُرسل password في طلب التعديل، نزيله تماماً من البيانات
        // كي لا تتفاعل معه قاعدة confirmed
        if (in_array($this->method(), ['PUT', 'PATCH'])) {
            if (!array_key_exists('password', $data) || $data['password'] === '' || $data['password'] === null) {
                unset($data['password'], $data['password_confirmation']);
            }
        }

        $this->merge($data);
    }
}
