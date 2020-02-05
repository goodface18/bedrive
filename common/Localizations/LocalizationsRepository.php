<?php namespace Common\Localizations;

use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Collection;

class LocalizationsRepository
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var Localization
     */
    private $localization;

    /**
     * Path to files with default localization language lines.
     */
    const DEFAULT_TRANS_PATHS = [
        'client-translations.json',
        'server-translations.json',
    ];

    /**
     * LocalizationsRepository constructor.
     *
     * @param Filesystem $fs
     * @param Localization $localization
     */
    public function __construct(Filesystem $fs, Localization $localization)
    {
        $this->fs = $fs;
        $this->localization = $localization;
    }

    /**
     * Get all existing localizations.
     *
     * @return Collection
     */
    public function all()
    {
        return $this->localization->all()->map(function(Localization $localization) {
            return ['model' => $localization];
        });
    }

    /**
     * Get localization by specified name.
     *
     * @param string $name
     * @return array
     */
    public function getByName($name)
    {
        $localization = $this->localization->where('name', $name)->first();
        if ( ! $localization) return null;

        return ['model' => $localization, 'lines' => $this->getLocalizationLines($localization)];
    }

    /**
     * Update specified localization.
     *
     * @param integer $id
     * @param array $data
     * @return array
     */
    public function update($id, $data)
    {
        $localization = $this->localization->findOrFail($id);

        if (isset($data['name']) && $data['name'] !== $localization->name) {
            $this->renameLocalizationLinesFile($localization, $data['name']);
            $localization->fill(['name' => $data['name']])->save();
        }

        if (isset($data['lines']) && $data['lines'] && ! empty($data['lines'])) {
            $this->storeLocalizationLines($localization, $data['lines']);
        }

        return $this->getByName($localization->name);
    }

    /**
     * Create a new localiztation.
     *
     * @param string $name
     * @return array
     */
    public function create($name)
    {
        $localization = $this->localization->create([
            'name' => $name,
        ]);

        $lines = $this->getDefaultTranslationLines();
        $this->storeLocalizationLines($localization, $lines);

        return $this->getByName($localization->name);
    }

    /**
     * Delete localization matching specified id.
     *
     * @param integer $id
     * @return bool|null
     * @throws \Exception
     */
    public function delete($id)
    {
        $localization = $this->localization->findOrFail($id);

        $this->fs->delete($this->makeLocalizationLinesPath($localization));

        return $localization->delete();
    }

    /**
     * Get default translations lines for the application.
     *
     * @return array
     */
    public function getDefaultTranslationLines()
    {
        $combined = [];

        foreach (self::DEFAULT_TRANS_PATHS as $path) {
            if ( ! $this->fs->exists(resource_path($path))) continue;
            $combined = array_merge($combined, json_decode($this->fs->get(resource_path($path)), true));
        }

        return $combined;
    }

    public function storeLocalizationLines(Localization $localization, $newLines)
    {
        $path = $this->makeLocalizationLinesPath($localization);
        $oldLines = [];

        if (file_exists($path)) {
            $oldLines = json_decode(file_get_contents($path), true);
        }

        $merged = array_merge($oldLines, $newLines);

        return file_put_contents($path, json_encode($merged));
    }

    /**
     * Get translation lines for specified localization.
     *
     * @param Localization $localization
     * @return array
     */
    public function getLocalizationLines(Localization $localization)
    {
        $path = $this->makeLocalizationLinesPath($localization);

        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        } else {
            return [];
        }
    }

    public function makeLocalizationLinesPath(Localization $localization)
    {
        $name = Str::slug($localization->name);
        return resource_path("lang/$name.json");
    }

    /**
     * Rename specified localization's lines file.
     *
     * @param Localization $localization
     * @param string $newName
     * @return bool
     */
    private function renameLocalizationLinesFile(Localization $localization, $newName)
    {
        $oldPath = $this->makeLocalizationLinesPath($localization);
        $newPath = str_replace($localization->name, $newName, $oldPath);
        return $this->fs->move($oldPath, $newPath);
    }
}