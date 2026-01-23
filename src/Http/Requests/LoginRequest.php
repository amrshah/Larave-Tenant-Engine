<?php

namespace Amrshah\TenantEngine\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Support JSON:API structure by unwrapping
        if ($this->has('data.attributes')) {
            $this->merge($this->input('data.attributes'));
        }
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        $hasJsonApi = $this->has('data.attributes');
        $pointerPrefix = $hasJsonApi ? '/data/attributes/' : '/';

        throw new HttpResponseException(
            response()->json([
                'errors' => collect($validator->errors()->messages())->map(function ($messages, $field) use ($pointerPrefix) {
                    return collect($messages)->map(function ($message) use ($field, $pointerPrefix) {
                        return [
                            'status' => '422',
                            'title' => 'Validation Error',
                            'detail' => $message,
                            'source' => [
                                'pointer' => "{$pointerPrefix}{$field}",
                            ],
                        ];
                    });
                })->flatten(1)->values(),
                'jsonapi' => ['version' => '1.1'],
            ], 422)
        );
    }
}
