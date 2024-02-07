<?php

namespace DDD\Domain\Funnels\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StepUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'metric' => 'nullable|string',
            'order' => 'nullable|numeric',
            'name' => 'nullable|string',
            'description' => 'nullable|string',
            'measurables' => 'nullable|array',
        ];
    }

    /**
     * Return exception as json
     *
     * @return Exception
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors()
        ], 422));
    }
}
