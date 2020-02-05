<?php namespace Common\Billing;

use Illuminate\Console\Command;
use Common\Billing\Plans\BillingPlansController;

class SyncBillingPlansCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plans:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync billing plans in database with enabled gateways.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        \App::make(BillingPlansController::class)->sync();

        $this->info('Synced plans successfully.');
    }
}
