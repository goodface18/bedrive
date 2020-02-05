<?php namespace Common\Auth\Requests;

use Common\Core\BaseFormRequest;

class ModifyUsers extends BaseFormRequest
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
        $userId = $this->route('id');

        $rules = [
            'first_name'      => 'alpha|min:2|max:255|nullable',
            'last_name'       => 'alpha|min:2|max:255|nullable',
            'permissions'     => 'array',
            'roles'          => 'array',
            'password'        => 'min:3|max:255',
            'email'           => "email|min:3|max:255|unique:users,email,$userId",
            'available_space' => 'nullable|min:0'
        ];

        if ($this->method() === 'POST') {
            $rules['email']    = 'required|'.$rules['email'];
            $rules['password'] = 'required|'.$rules['password'];
        }

        return $rules;
    }
}
