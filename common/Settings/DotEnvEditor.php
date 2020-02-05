<?php namespace Common\Settings;

use Dotenv\Loader;

class DotEnvEditor extends Loader
{
    /**
     * EnvLoader constructor.
     *
     * @param string $fileName
     */
    public function __construct($fileName = '.env')
    {
        parent::__construct(base_path($fileName), true);
    }

    /**
     * Load values from .env file
     *
     * @param string|null $path
     * @return array
     */
    public function load($path = null)
    {
        $this->ensureFileIsReadable();

        $filePath = $path ?: $this->filePath;

        $lines = $this->readLinesFromFile($filePath);
        $env = [];

        foreach ($lines as $line) {
            if ( ! $this->isComment($line) && $this->looksLikeSetter($line)) {
                list($key, $value) = $this->normaliseEnvironmentVariable($line, null);
                $env[strtolower($key)] = $value === 'null' ? null : $value;
            }
        }

        return $env;
    }

    /**
     * Write specified settings to .env file.
     *
     * @param array $values
     *
     * @return void
     */
    public function write($values = [])
    {
        $this->ensureFileIsReadable();

        $content = file_get_contents($this->filePath);

        foreach ($values as $key => $value) {
            $value = $this->formatValue($value);

            $key = strtoupper($key);

            if (str_contains($content, $key.'=')) {
                preg_match("/($key=)(.*?)(\n|\Z)/msi", $content, $matches);
                $content = str_replace($matches[1].$matches[2], $matches[1].$value, $content);
            } else {
                $content .= "\n\n$key=$value";
            }
        }

        file_put_contents($this->filePath, $content);
    }

    /**
     * Format specified value to be compatible with .env file
     *
     * @param string|null $value
     * @return string
     */
    private function formatValue($value)
    {
        if ( ! $value) $value = 'null';

        //wrap string in quotes, if it contains whitespace
        if (preg_match('/\s/', $value)) {
            //replace double quotes with single quotes
            $value = str_replace('"', "'", $value);

            //wrap string in quotes
            $value = '"'.$value.'"';
        }

        return $value;
    }
}