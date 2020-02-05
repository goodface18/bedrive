<?php namespace Common\Core;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Validator as ConcreteValidator;

class BaseFormRequest extends FormRequest
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
     * {@inheritdoc}
     */
    protected function formatErrors(Validator $validator)
    {
        return $this->formatValidationErrors($validator);
    }

    /**
     * Format the validation errors to be returned.
     *
     * @param ConcreteValidator $validator
     * @return array
     */
    public static function formatValidationErrors(ConcreteValidator $validator)
    {
        $formatted = $validator->errors()->getMessages();

        return ['status' => 'error', 'messages' => $formatted];
    }
}
