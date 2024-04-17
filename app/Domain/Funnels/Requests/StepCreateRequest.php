<?php

namespace DDD\Domain\Funnels\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StepCreateRequest extends FormRequest
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
            'order' => 'nullable|numeric',
            'name' => 'nullable|string',
            'metrics' => 'nullable|array',
            'metrics.*.metric' => 'nullable|string',
            'metrics.*.pagePath' => 'nullable|string',
            'metrics.*.pagePathPlusQueryString' => 'nullable|string',
            'metrics.*.linkUrl' => 'nullable|string',
            'metrics.*.formDestination' => 'nullable|string',
            'metrics.*.formId' => 'nullable|string',
            'metrics.*.formLength' => 'nullable|string',
            'metrics.*.formSubmitText' => 'nullable|string',
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
