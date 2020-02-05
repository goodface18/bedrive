<?php namespace Common\Files\Requests;

use Common\Files\Actions\GetUserSpaceUsage;
use Illuminate\Validation\Validator;
use Common\Core\BaseFormRequest;
use Common\Settings\Settings;

class UploadFile extends BaseFormRequest
{
    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var GetUserSpaceUsage
     */
    private $spaceUsage;

    /**
     * @param Settings $settings
     * @param GetUserSpaceUsage $spaceUsage
     */
    public function __construct(Settings $settings, GetUserSpaceUsage $spaceUsage)
    {
        $this->settings = $settings;
        $this->spaceUsage = $spaceUsage;
        parent::__construct();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'file' => 'required|file',
            'parentId' => 'nullable|integer|exists:file_entries,id',
            'path' => 'nullable|string|max:255',
        ];

        // validate by allowed extension setting
        if ($allowed = $this->settings->getJson('uploads.allowed_extensions')) {
            $rules['file'] .= '|mimes:' . implode(',', $allowed);
        }

        // validate by max file size setting
        if ($maxSize = (int) $this->settings->get('uploads.max_size')) {
            // size is stored in megabytes, laravel expects kilobytes
            $rules['file'] .= '|max:' . $maxSize * 1024;
        }

        return $rules;
    }

    /**
     * @param  Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {
            $file = $this->file('file');

            $blocked = $this->settings->getJson('uploads.blocked_extensions', []);
            if (in_array($file->guessExtension(), $blocked)) {
                $validator->errors()->add('file', "Files of this type can't be uploaded.");
            }

            // check if user has enough space left to upload all files.
            if ($this->spaceUsage->userIsOutOfSpace($this->file('file'))) {
                $validator->errors()->add('file', "You are out of space. Try to delete some files.");
            }
        });
    }
}
