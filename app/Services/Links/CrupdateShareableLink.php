<?php

namespace App\Services\Links;

use App\ShareableLink;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class CrupdateShareableLink
{
    /**
     * @var ShareableLink
     */
    private $link;

    /**
     * @param ShareableLink $link
     */
    public function __construct(ShareableLink $link)
    {
        $this->link = $link;
    }

    /**
     * Create a new link or update existing one.
     *
     * @param array $params
     * @param ShareableLink $link
     * @return ShareableLink|\Illuminate\Database\Eloquent\Model
     */
    public function execute($params, ShareableLink $link = null) {
        if ($link) {
            $link->fill($this->transformParams($params))->save();
        } else {
            $link = $this->link->create($this->transformParams($params));
        }

        return $link;
    }

    private function transformParams($params)
    {
        $transformed = [
            'user_id' => $params['userId'],
            'password' => Arr::get($params, 'password'),
            'allow_download' => $params['allowDownload'],
            'allow_edit' => $params['allowEdit'],
        ];

        // creating a new link
        if (isset($params['entryId'])) {
            $transformed['entry_id'] = $params['entryId'];
            $transformed['hash'] = str_random(30);
        }

        if (isset($params['expiresAt'])) {
            $transformed['expires_at'] = Carbon::parse($params['expiresAt']);
        }

        return $transformed;
    }
}