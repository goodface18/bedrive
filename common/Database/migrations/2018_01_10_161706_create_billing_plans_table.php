<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBillingPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('billing_plans', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('amount');
            $table->string('currency');
            $table->string('currency_symbol')->default('$');
            $table->string('interval')->default('month');
            $table->integer('interval_count')->default(1);
            $table->integer('parent_id')->nullable();
            $table->text('permissions')->nullable();
            $table->uuid('uuid');
            $table->boolean('recommended')->default(0);
            $table->boolean('free')->default(0);
            $table->boolean('show_permissions')->default(0);
            $table->text('features')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();

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
        Schema::drop('billing_plans');
    }
}
