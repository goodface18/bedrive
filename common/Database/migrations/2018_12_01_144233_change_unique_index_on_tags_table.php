<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeUniqueIndexOnTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->string('type', 30)->default('custom')->change();

            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('tags');

            if (array_key_exists('tags_name_unique', $indexesFound)) {
                $table->dropUnique('tags_name_unique');
            }

            if (array_key_exists('tags_name_type_unique', $indexesFound)) {
                $table->dropUnique('tags_name_type_unique');
            }

            $table->unique(['name', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
