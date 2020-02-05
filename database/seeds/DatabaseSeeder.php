<?php

use Common\Tags\Tag;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * @var Tag
     */
    private $tag;

    public function __construct(Tag $tag)
    {
        $this->tag = $tag;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // create tag for starring file entries
        $this->tag->firstOrCreate([
            'name' => 'starred',
            'display_name' => 'Starred',
            'type' => 'label']
        );
    }
}
