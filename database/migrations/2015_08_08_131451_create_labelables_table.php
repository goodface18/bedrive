<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLabelablesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('labelables', function(Blueprint $table)
		{
			$table->increments('id');
            $table->integer('label_id');
            $table->integer('labelable_id');
            $table->string('labelable_type');

            $table->index('labelable_id');
            $table->index('label_id');
            $table->unique(array('label_id', 'labelable_id', 'labelable_type'));
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('labelables');
	}

}
