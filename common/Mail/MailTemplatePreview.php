<?php namespace Common\Mail;

use App;
use Blade;
use Exception;
use Throwable;
use Common\Settings\Settings;
use Illuminate\Support\Arr;
use Illuminate\View\Factory;
use Illuminate\Mail\Markdown;
use Faker\Generator as Faker;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class MailTemplatePreview
{
    /**
     * @var Faker
     */
    private $faker;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var Factory
     */
    private $view;

    /**
     * MailTemplatePreview constructor.
     *
     * @param Faker $faker
     * @param Settings $settings
     */
    public function __construct(Faker $faker, Settings $settings)
    {
        $this->faker = $faker;
        $this->settings = $settings;
    }

    /**
     * Render specified string contents using
     * blade/php compilers and mock data.
     *
     * @param array $config
     * @return array
     * @throws Exception
     * @throws FatalThrowableError
     */
    public function render($config)
    {
        $contents = Arr::get($config, 'contents');
        $plain    = Arr::get($config, 'plain', false);
        $markdown = Arr::get($config, 'markdown', false);

        $data = $this->getMockData();
        $this->view = $this->makeViewFactory();

        $php = Blade::compileString($contents);

        $obLevel = ob_get_level();
        ob_start();
        extract($data, EXTR_SKIP);

        try {
            eval('?' . '>' . $php);
        } catch (Exception $e) {
            while (ob_get_level() > $obLevel) ob_end_clean();
            throw $e;
        } catch (Throwable $e) {
            while (ob_get_level() > $obLevel) ob_end_clean();
            throw new FatalThrowableError($e);
        }

        $contents = ob_get_clean();

        if ($markdown) {
            $contents = $this->handleMarkdown($contents, $plain);
        }

        return ['contents' => $contents];
    }

    /**
     * Render markdown mail template.
     *
     * @param string $contents
     * @param boolean $plain
     * @return string
     */
    private function handleMarkdown($contents, $plain)
    {
        if ($plain) {
            return preg_replace("/[\r\n]{2,}/", "\n\n", $contents);
        } else {
            return (new CssToInlineStyles)->convert(
                $contents, $this->view->make('mail::themes.default')->render()
            );
        }
    }

    /**
     * Get mock data for rendering mail template previews.
     *
     * @return array
     */
    private function getMockData()
    {
        if (class_exists(\App\Ticket::class)) {
            $ticket = new \App\Ticket();
            $ticket->id = 1;
        } else {
            $ticket = null;
        }

        $data = [
            'displayName'   => $this->faker->name,
            'emailMessage'  => $this->faker->sentence(20),
            'body'          => $this->faker->sentence(20),
            'ticket'        => $ticket,
            'reference'     => str_random(30),
            'itemName'      => $this->faker->word,
            'code'          => str_random(),
            'link'          => url(''),
            'siteName'      => $this->settings->get('branding.site_name')
        ];

        $data['__env'] = app(\Illuminate\View\Factory::class);

        return $data;
    }

    /**
     * Make and configure view factory instance.
     *
     * @return Factory
     */
    private function makeViewFactory()
    {
        $factory  = App::make(Factory::class);
        $markdown = App::make(Markdown::class);
        $markdown->loadComponentsFrom([resource_path('views/vendor/mail')]);

        $factory->replaceNamespace(
            'mail', $markdown->htmlComponentPaths()
        );

        return $factory;
    }
}