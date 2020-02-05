<?php namespace Common\Admin\Appearance\Commands;

use Illuminate\Console\Command;
use Common\Admin\Appearance\CssThemeGenerator;

class GenerateCssTheme extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'css_theme:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a css theme template for appearance editor.';

    /**
     * @var CssThemeGenerator;
     */
    protected $generator;

    /**
     * Create a new command instance.
     *
     * @param CssThemeGenerator $generator
     */
    public function __construct(CssThemeGenerator $generator)
    {
        parent::__construct();

        $this->generator = $generator;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->generator->generate();
        $this->info('Css theme generated');
    }
}
