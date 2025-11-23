<?php

namespace Amrshah\TenantEngine\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateTenantRequest extends FormRequest
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
        $tenantId = $this->route('tenant'); // Get tenant ID from route

        return [
            'name' => 'sometimes|string|max:255',
            'email' => "sometimes|email|unique:tenants,email,{$tenantId},id",
            'phone' => 'nullable|string|max:20',
            'plan' => 'sometimes|string|max:50',
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
