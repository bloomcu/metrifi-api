<?php

namespace DDD\Domain\Funnels\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use DDD\Domain\Funnels\Enums\MatchType;

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
        $allowedMatchTypes = implode(',', MatchType::common());

        return [
            'order' => 'nullable|numeric',
            'name' => 'nullable|string',
            'metrics' => 'nullable|array',
            'metrics.*.metric' => 'nullable|string',
            'metrics.*.matchType' => "nullable|string|in:{$allowedMatchTypes}",
            'metrics.*.pagePath' => 'nullable|string',
            'metrics.*.pagePathMatchType' => "nullable|string|in:{$allowedMatchTypes}",
            'metrics.*.pagePathPlusQueryString' => 'nullable|string',
            'metrics.*.pageTitle' => 'nullable|string',
            'metrics.*.linkUrl' => 'nullable|string',
            'metrics.*.linkUrlMatchType' => "nullable|string|in:{$allowedMatchTypes}",
            'metrics.*.formDestination' => 'nullable|string',
            'metrics.*.formId' => 'nullable|string',
            'metrics.*.formLength' => 'nullable|string',
            'metrics.*.formSubmitText' => 'nullable|string',
            'metrics.*.hostname' => 'nullable|string',
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
