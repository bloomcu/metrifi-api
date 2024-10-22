<?php

namespace DDD\Http\Files\Requests;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Exception;

class StoreFileRequest extends FormRequest
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
            // 'file' => 'required|mimes:jpg,jpeg,png,gif,webp,svg,pdf,mp4,mov,webm,mpeg,html,css,js|max:30000', // Max 30mb
            'file' => 'required|mimes:jpg,jpeg,png,gif,webp|max:30000', // Max 30mb
            'folder_id' => 'nullable|integer',
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
