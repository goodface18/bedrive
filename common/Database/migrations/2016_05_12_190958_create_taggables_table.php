<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaggablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taggables', function (Blueprint $table) {
            $table->integer('tag_id')->unsigned()->index();
            $table->integer('taggable_id')->unsigned()->index();
            $table->string('taggable_type', 80)->index();
            $table->integer('user_id')->unsigned()->index()->nullable();

            $table->unique(['tag_id', 'taggable_id', 'user_id', 'taggable_type']);

            $table->collation = config('database.connections.mysql.collation');
            $table->charset = config('database.connections.mysql.charset');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('taggables');
    }
}
