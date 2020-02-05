<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSocialProfilesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('social_profiles', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('user_id');
			$table->string('service_name', 20);
			$table->string('user_service_id');
			$table->string('username')->nullable();
			$table->timestamps();

			$table->index('user_id');
			$table->unique(['user_id', 'service_name']);
			$table->unique(['service_name', 'user_service_id']);

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
		Schema::drop('social_profiles');
	}

}
