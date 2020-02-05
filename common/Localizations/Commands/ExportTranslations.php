<?php namespace Common\Localizations\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ExportTranslations extends Command
{
    /**
     * @var string
     */
    protected $signature = 'translations:export';

    /**
     * @var string
     */
    protected $description = 'Export default laravel translations as flattened json file.';

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * Create a new command instance.
     * @param Filesystem $fs
     */
    public function __construct(Filesystem $fs)
    {
        parent::__construct();

        $this->fs = $fs;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $messages = array_merge(
            $this->getCustomValidationMessages(),
            $this->GetDefaultValidationMessages()
        );

        $this->fs->put(resource_path('server-translations.json'), json_encode($messages));

        $this->info('Translation lines exported as json.');
    }

    /**
     * Get custom validation messages from Laravel Request files.
     *
     * @return array
     */
    private function getCustomValidationMessages()
    {
        $files = $this->fs->files(app_path('Http/Requests'));
        $messages = [];

        foreach ($files as $file) {

            //make namespace from file path
            $namespace = str_replace([base_path() . DIRECTORY_SEPARATOR, '.php'], '', $file);
            $namespace = ucfirst(str_replace('/', '\\', $namespace));

            try {
                //need to use translation as a key (source) and value (translation)
                foreach ((new $namespace)->messages() as $message) {
                    $messages[$message] = $message;
                }
            } catch (\Exception $e) {
                //
            }
        }

        return $messages;
    }

    /**
     * Get default validation messages from laravel translation files.
     *
     * @return array
     */
    private function GetDefaultValidationMessages()
    {
        $paths = $this->fs->files(resource_path('lang/english'));

        $compiled = [];

        foreach ($paths as $path) {
            $lines = $this->fs->getRequire($path);

            foreach ($lines as $key => $line) {
                if ($key === 'custom') continue;

                //flatten multi array translations
                if (is_array($line)) {
                    foreach ($line as $subkey => $subline) {
                        $compiled[$subline] = $subline;
                    }

                    //simply copy regular translation lines
                } else {
                    $compiled[$line] = $line;
                }
            }
        }

        return $compiled;
    }
}
