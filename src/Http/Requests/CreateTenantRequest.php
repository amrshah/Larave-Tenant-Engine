<?php

namespace Amrshah\TenantEngine\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        if ($this->has('data.attributes')) {
            $this->merge($this->input('data.attributes'));
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:tenants,email',
            'slug' => 'required|string|min:3|max:50|unique:tenants,id|alpha_dash',
            'phone' => 'nullable|string|max:20',
            'plan' => 'nullable|string|max:50',
            'trial_days' => 'nullable|integer|min:0|max:365',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'This tenant slug is already taken',
            'slug.alpha_dash' => 'The slug may only contain letters, numbers, dashes and underscores',
            'email.unique' => 'A tenant with this email already exists',
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
