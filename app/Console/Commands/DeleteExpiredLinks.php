<?php

namespace App\Console\Commands;

use App\ShareableLink;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeleteExpiredLinks extends Command
{
    /**
     * @var string
     */
    protected $signature = 'links:delete_expired';

    /**
     * @var string
     */
    protected $description = 'Delete expired shareable links.';

    /**
     * @var ShareableLink
     */
    private $link;

    /**
     * @param ShareableLink $link
     */
    public function __construct(ShareableLink $link)
    {
        parent::__construct();
        $this->link = $link;
    }

    public function handle()
    {
        $this->link->where('expires_at', '<', Carbon::now())->delete();
        $this->info('Deleted all expired shareable links');
    }
}
