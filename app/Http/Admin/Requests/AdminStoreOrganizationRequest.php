<?php

namespace DDD\Http\Admin\Requests;

use Illuminate\Validation\Rules\Password;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Exception;

class AdminStoreOrganizationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'regex:/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(\/.*)?$/', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'domain.regex' => 'The domain must be a valid URL or domain name.',
        ];
    }

    /**
     * Return exception as json
     */
    protected function failedValidation(Validator $validator): Exception
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
