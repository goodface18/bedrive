<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'first_name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
    ];
});

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\File::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->slug.'.'.$faker->fileExtension,
        'description' => 'description',
        'file_name' => 's5d8w4w5d8w4w5w5w',
        'mime' => $faker->mimeType,
        'file_size' => 159556,
        'type' => array_random(['image', 'audio', 'video', 'text', 'archive', 'generic']),
    ];
});

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Folder::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'description' => 'description',
        'type' => 'folder'
    ];
});