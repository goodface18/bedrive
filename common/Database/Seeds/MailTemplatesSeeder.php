<?php namespace Common\Database\Seeds;

use Illuminate\Database\Seeder;
use Common\Mail\MailTemplates;

class MailTemplatesSeeder extends Seeder
{
    /**
     * @var MailTemplates
     */
    private $mailTemplates;

    /**
     * @param MailTemplates $mailTemplates
     */
    public function __construct(MailTemplates $mailTemplates)
    {
        $this->mailTemplates = $mailTemplates;
    }

    /**
     * @return void
     */
    public function run()
    {
        $this->mailTemplates->getDefault()->each(function($config) {
            //user friendly template name
            $config['display_name'] = str_replace('-', ' ', title_case($config['name']));

            //for what action template will be used
            $config['action'] = str_replace('-', '_', $config['name']);

            //set template subject
            $subjects = config('common.mail-templates.subjects');
            $config['subject'] = $subjects[$config['action']];

            //set template file name
            $config['file_name'] = $config['name'].'.blade.php';

            //mail template already exists, bail
            if ($this->mailTemplates->getByAction($config['action'])) return;

            try {
                $this->mailTemplates->create($config);
            } catch(\Exception $e) {
                //
            }
        });
    }
}
