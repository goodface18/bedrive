<?php namespace Common\Database\Seeds;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Collection;
use Common\Localizations\LocalizationsRepository;

class LocalizationsTableSeeder extends Seeder
{
    /**
     * @var LocalizationsRepository
     */
    private $repository;

    /**
     * LocalizationsTableSeeder constructor.
     *
     * @param LocalizationsRepository $repository
     */
    public function __construct(LocalizationsRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $localizations = $this->repository->all();

        if ($localizations->isNotEmpty()) {
            $this->mergeExistingTranslationLines($localizations);
        } else {
            $this->repository->create('english');
        }
    }

    /**
     * Merge existing localization translation lines with default ones.
     *
     * @param Collection $localizations
     */
    private function mergeExistingTranslationLines($localizations)
    {
        $defaultLines = $this->repository->getDefaultTranslationLines();

        $localizations->each(function ($localization) use($defaultLines) {
            $model = $localization['model'];

            $this->repository->storeLocalizationLines(
                $model,
                array_merge($defaultLines, $this->repository->getLocalizationLines($model))
            );
        });
    }
}
