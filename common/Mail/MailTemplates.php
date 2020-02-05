<?php namespace Common\Mail;

use Common\Mail\MailTemplate;
use Common\Settings\Settings;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;

class MailTemplates
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var MailTemplate
     */
    private $template;

    /**
     * Path to mail templates views directory.
     *
     * @var string
     */
    private $templatesPath;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * SettingsTableSeeder constructor.
     *
     * @param Filesystem $fs
     * @param MailTemplate $template
     * @param Settings $settings
     */
    public function __construct(Filesystem $fs, MailTemplate $template, Settings $settings)
    {
        $this->fs = $fs;
        $this->settings = $settings;
        $this->template = $template;
        $this->templatesPath = resource_path('views/emails');
    }

    /**
     * Find mail template by specified id.
     *
     * @param integer $id
     * @return MailTemplate
     */
    public function findOrFail($id)
    {
        return $this->template->findOrFail($id);
    }

    /**
     * Get template by specified action.
     *
     * @param string $action
     * @param array $vars
     * @return array
     */
    public function getByAction($action, $vars = [])
    {
        $template = $this->template->where('action', $action)->first();

        if ( ! $template) return null;

        $subject = $template->subject;

        //replace subject placeholders with specified values
        foreach($vars as $name => $value) {
            $subject = str_replace('{{'.strtoupper($name).'}}', $value, $subject);
        }

        //make template view name
        $type = $this->settings->get('mail.use_default_templates') ? 'default' : 'custom';
        $view = str_replace([$this->templatesPath.'/'], 'emails/', $this->getFullPath($template['file_name'], $type));
        $view = str_replace('.blade.php', '', $view);

        return [
            'subject' => $subject,
            'html_view' => $view,
            'plain_view' => "{$view}-plain",
        ];
    }

    /**
     * Update specified mail template with data.
     *
     * @param integer|MailTemplate $idOrModel
     * @param array $data
     * @return array
     */
    public function update($idOrModel, $data)
    {
        $template = is_a($idOrModel, MailTemplate::class) ? $idOrModel : $this->findOrFail($idOrModel);

        $this->saveToDisk($template->file_name, $data['contents']);

        if (isset($data['subject'])) {
            $template->fill(['subject' => $data['subject']])->save();
        }

        return array_merge(['model' => $template], $this->getContents($template->file_name));
    }

    /**
     * Restore mail template contents to defaults.
     *
     * @param int $id
     * @return array
     */
    public function restoreDefault($id)
    {
        $template = $this->findOrFail($id);

        $defaults = $this->getContents($template->file_name, 'default');

        return $this->update($template, ['contents' => $defaults]);
    }

    /**
     * Get all mail templates.
     *
     * @param array $options
     * @return Collection
     */
    public function getAll($options = [])
    {
        $templates = $this->template->all();

        return $templates->map(function(MailTemplate $template) use($options) {
            return array_merge(['model' => $template], $this->getContents($template->file_name, 'custom', $options));
        });
    }

    /**
     * Create a new custom mail template.
     *
     * @param array $config
     * @return MailTemplate
     */
    public function create($config)
    {
        $this->saveToDisk($config['file_name'], $config);

        $template = $this->template->firstOrCreate(['file_name' => $config['file_name']]);

        $template->fill([
            'action' => $config['action'],
            'subject' => $config['subject'],
            'file_name' => $config['file_name'],
            'display_name' => $config['display_name'],
        ])->save();

        return $template;
    }

    /**
     * Return paths of all default templates.
     *
     * @return Collection
     */
    public function getDefault()
    {
        return collect($this->fs->directories("$this->templatesPath/default"))->map(function($path) {
            $name = basename($path);

            return [
                'name' => $name,
                'html' => $this->fs->get("$path/$name.blade.php"),
                'plain' => $this->fs->get("$path/$name-plain.blade.php")
            ];
        });
    }

    /**
     * Update or create specified filename with contents.
     *
     * @param string $fileName
     * @param array $contents
     */
    private function saveToDisk($fileName, $contents)
    {
        $path = $this->getFullPath($fileName, 'custom');

        if ( ! $this->fs->exists(dirname($path))) {
            $this->fs->makeDirectory(dirname($path));
        }

        $html = Arr::get($contents, 'html');
        $plain = Arr::get($contents, 'plain');

        $this->fs->put($path, $html);

        if ($plain) {
            $path = $this->getFullPath($this->getPlainFileName($fileName), 'custom');
            $this->fs->put($path, $plain);
        }
    }

    /**
     * Get contents of specified mail template.
     *
     * @param string $fileName
     * @param string $type
     * @param array $options
     * @return array
     */
    public function getContents($fileName, $type = 'custom', $options = [])
    {
        $forceDefault = !Arr::get($options, 'forceCustom') && $this->settings->get('mail.use_default_templates');

        $type = $forceDefault ? 'default' : $type;

        $contents = [
            'html'  => $this->fs->get($this->getFullPath($fileName, $type)),
        ];

        $plainFileName = $this->getPlainFileName($fileName);
        $plainPath = $this->getFullPath($plainFileName, $type);

        if ($this->fs->exists($plainPath)) {
            $contents['plain'] = $this->fs->get($plainPath);
        }

        return $contents;
    }

    /**
     * Make plain template file name from base template file name.
     *
     * @param string $fileName
     * @return string
     */
    private function getPlainFileName($fileName)
    {
        return str_replace('.blade.php', '-plain.blade.php', $fileName);
    }

    /**
     * Get absolute path for specified template file.
     *
     * @param string $fileName
     * @param string $type
     * @return string
     */
    private function getFullPath($fileName, $type)
    {
        $dir = str_replace(['.blade.php', '-plain'], '', $fileName);

        return "$this->templatesPath/$type/$dir/$fileName";
    }


}