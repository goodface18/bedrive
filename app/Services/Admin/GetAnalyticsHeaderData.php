<?php

namespace App\Services\Admin;

use App\File;
use App\Folder;
use App\User;
use Common\Admin\Analytics\Actions\GetAnalyticsHeaderDataAction;

class GetAnalyticsHeaderData implements GetAnalyticsHeaderDataAction
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var File
     */
    private $file;

    /**
     * @var Folder
     */
    private $folder;

    /**
     * GetAnalyticsHeaderData constructor.
     *
     * @param File $file
     * @param User $user
     * @param Folder $folder
     */
    public function __construct(File $file, User $user, Folder $folder)
    {
        $this->user = $user;
        $this->file = $file;
        $this->folder = $folder;
    }

    public function execute()
    {
        return [
            [
                'icon' => 'content-copy',
                'name' => 'Total Files',
                'type' => 'number',
                'value' => $this->file->count(),
            ],
            [
                'icon' => 'folder',
                'name' => 'Total Folders',
                'type' => 'number',
                'value' => $this->folder->count(),
            ],
            [
                'icon' => 'people',
                'name' => 'Total Users',
                'type' => 'number',
                'value' => $this->user->count(),
            ],
            [
                'icon' => 'sd-storage',
                'name' => 'Total Space Used',
                'type' => 'fileSize',
                'value' => (int) $this->file->sum('file_size'),
            ]
        ];
    }
}