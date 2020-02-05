<?php

namespace App\Console\Commands;

use App\User;
use Common\Auth\Events\UserCreated;
use Hash;
use Illuminate\Console\Command;

class CreateDemoAccounts extends Command
{
    private $numOfAccounts = 100;

    /**
     * @var string
     */
    protected $signature = 'demo:create_accounts';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return mixed
     */
    public function handle()
    {
        $bar = $this->output->createProgressBar($this->numOfAccounts);

        for ($i = 0; $i <= $this->numOfAccounts; $i++) {
            $number = str_pad($i, 3, '0', STR_PAD_LEFT);
            $user = new User([
                'username' => "admin",
                'email' => "admin@demo{$number}.com",
                'first_name' => 'Demo',
                'last_name' => 'Admin',
                'password' => Hash::make('admin'),
                'permissions' => ['admin' => 1, 'superAdmin' => 1]
            ]);

            $user->save();

            event(new UserCreated($user));
            $bar->advance();
        }

        $bar->finish();
    }
}
