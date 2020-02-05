<?php

namespace Common\Core\Prerender;

use Common\Core\Contracts\AppUrlGenerator;
use Common\Settings\Settings;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

class MetaTags implements Arrayable
{
    /**
     * Tag types that can be edited by the user.
     */
    const EDITABLE_TAGS = ['og:title', 'og:description', 'keywords'];

    /**
     * Data for replacing meta tag config placeholders.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Meta tag config before generation.
     *
     * @var array
     */
    protected $tags = [];

    /**
     * Final tags for appending to site head.
     *
     * @var array
     */
    protected $generatedTags = [];

    /**
     * Namespace for current tag config. "artist.show".
     *
     * @var string
     */
    protected $namespace;

    /**
     * @var AppUrlGenerator
     */
    public $urls;

    public function __construct($tags, $data, $namespace)
    {
        $this->namespace = $namespace;
        $tags = $this->overrideTagsWithUserValues($tags);
        $this->tags = array_merge($tags, config('seo.common'));
        $this->data = $data;
        $this->urls = app(AppUrlGenerator::class);
        $this->generatedTags = $this->generateTags();
    }

    public function toArray()
    {
        return $this->getAll();
    }

    public function getTitle()
    {
        return $this->get('og:title');
    }

    public function getDescription()
    {
        return $this->get('og:description');
    }

    public function get($value, $prop = 'property')
    {
        $tag = Arr::first($this->generatedTags, function($tag) use($prop, $value) {
            return $tag[$prop] === $value;
        }, []);

        return Arr::get($tag, 'content');
    }

    public function getData($selector = null)
    {
        return Arr::get($this->data, $selector);
    }

    public function getAll()
    {
        return $this->generatedTags;
    }

    /**
     * Convert specified tag config into a string.
     *
     * @param array $tag
     * @return string
     */
    public function tagToString($tag)
    {
        $string = '';

        foreach(array_except($tag, 'nodeName') as $key => $value) {
            $string .= "$key=\"$value\" ";
        }

        return trim($string);
    }

    private function generateTags()
    {
        $tags = $this->tags;

        $tags = array_map(function($tag) {
            // if tag does not have "content" or "_text" prop, we can continue
            if (array_key_exists('content', $tag)) {
                $tag['content'] = $this->replacePlaceholders($tag['content']);
            } else if (array_key_exists('_text', $tag)) {
                $tag['_text'] = $this->replacePlaceholders($tag['_text']);
            }
            return $tag;
        }, $tags);

        $tags = $this->duplicateTags($tags);

        $tags = array_map(function($tag) {
            // set nodeName to <meta> tag, if not already specified
            if ( ! array_key_exists('nodeName', $tag)) {
                $tag['nodeName'] = 'meta';
            }
            return $tag;
        }, $tags);

        return $tags;
    }

    /**
     * Create duplicate tags from generated tags.
     * (for example: canonical link from og:url)
     *
     * @param array $tags
     * @return array
     */
    private function duplicateTags($tags)
    {
        foreach ($tags as $tag) {
            if (Arr::get($tag, 'property') === 'og:url') {
                $tags[] = [
                    'nodeName' => 'link',
                    'rel' => 'canonical',
                    'href' => $tag['content']
                ];
            }

            if (Arr::get($tag, 'property') === 'og:title') {
                $tags[] = [
                    'nodeName' => 'title',
                    '_text' => ucfirst($tag['content']),
                ];
            }

            if (Arr::get($tag, 'property') === 'og:description') {
                $tags[] = [
                    'property' => 'description',
                    'content' => $tag['content'],
                ];
            }
        }

        return $tags;
    }

    private function replacePlaceholders($text)
    {
        return preg_replace_callback('/{{([\w\.\-]+?)}}/', function($matches) {
            if ( ! isset($matches[1])) return $matches[0];

            $placeholder = strtolower($matches[1]);

            // replace site name
            if ($placeholder === 'site_name') {
                return config('app.name');
            }

            // replace base url
            if ($placeholder === 'url.base') {
                return url('');
            }

            // replace by url generator url
            if (starts_with($placeholder, 'url.')) {
                // "url.movie" => "movie"
                $resource = str_replace('url.', '', $placeholder);
                // "new_releases" => "newReleases"
                $method = camel_case($resource);
                $data = $this->getData($resource);
                return $this->urls->$method($data ?: $this->getData());
            }

            // supports dot notation: 'artist.bio.text'
            $replacement = Arr::get($this->data, $placeholder);

            // return original placeholder if it can't be replaced
            if ( ! $replacement || is_array($replacement)) {
                return $matches[0];
            }

            return str_limit($replacement, 400);
        }, $text);
    }

    /**
     * @param array $metaTags
     * @return array
     */
    private function overrideTagsWithUserValues($metaTags)
    {
        $overrides = app(Settings::class)->all();
        foreach ($metaTags as $key => $tagConfig) {
            $property = Arr::get($tagConfig, 'property');
            $settingKey = "seo.{$this->namespace}.{$property}";
            if (array_search($property, self::EDITABLE_TAGS) !== false && array_key_exists($settingKey, $overrides)) {
                $metaTags[$key]['content'] = $overrides[$settingKey];
            }
        }

        return $metaTags;
    }
}