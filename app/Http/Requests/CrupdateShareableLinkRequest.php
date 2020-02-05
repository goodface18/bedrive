<?php

namespace App\Http\Requests;

use Common\Core\BaseFormRequest;

class CrupdateShareableLinkRequest extends BaseFormRequest
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

    public function messages()
    {
        return [
            'expiresAt.date' => 'This is not a valid date.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'allowDownload' => 'boolean',
            'allowEdit' => 'boolean',
            'expiresAt' => 'nullable|date',
            'password' => 'nullable|string'
        ];
    }
}
