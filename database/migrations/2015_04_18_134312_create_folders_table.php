<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFoldersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('folders', function(Blueprint $table)
		{
			$table->increments('id');
            $table->string('name');
            $table->string('description', 150)->nullable();
            $table->text('path')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('folder_id')->nullable();
            $table->string('share_id', 20)->unique;
            $table->string('password')->nullable();
			$table->timestamps();
			$table->softDeletes();

            $table->index('user_id');
            $table->index('share_id');
            $table->index('folder_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('folders');
	}

}
