<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class EmptyTempDirectory extends Command
{
    /**
     * @var string
     */
    protected $signature = 'tempDir:empty';

    /**
     * @var string
     */
    protected $description = 'Remove all files from storage "temp" directory.';

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @param Filesystem $fs
     */
    public function __construct(Filesystem $fs)
    {
        parent::__construct();
        $this->fs = $fs;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $this->fs->cleanDirectory(storage_path('app/temp/zips'));
        $this->info('Temp directory emptied.');
    }
}
