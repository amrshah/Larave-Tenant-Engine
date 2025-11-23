<?php

namespace Amrshah\TenantEngine\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateSuperAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:super_admins,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'A super admin with this email already exists',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'errors' => collect($validator->errors()->messages())->map(function ($messages, $field) {
                    return collect($messages)->map(function ($message) use ($field) {
                        return [
                            'status' => '422',
                            'title' => 'Validation Error',
                            'detail' => $message,
                            'source' => [
                                'pointer' => "/data/attributes/{$field}",
                            ],
                        ];
                    });
                })->flatten(1)->values(),
                'jsonapi' => ['version' => '1.1'],
            ], 422)
        );
    }
}
