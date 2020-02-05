<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['prefix' => 'secure/drive', 'middleware' => 'web'], function () {

    Route::group(['middleware' => 'auth'], function () {
        //FOLDERS
        Route::get('folders/find', 'FoldersController@show');
        Route::get('folders', 'FoldersController@index');
        Route::post('folders', 'FoldersController@store');
        Route::get('users/{userId}/folders', 'UserFoldersController@index');

        //ENTRIES (COMMON FOR FILES/FOLDERS)
        Route::get('entries', 'DriveEntriesController@index');
        Route::post('entries/move', 'MoveFileEntriesController@move');
        Route::delete('entries', 'DriveEntriesController@destroy');
        Route::post('entries/restore', '\Common\Files\Controllers\RestoreDeletedEntriesController@restore');
        Route::get('entries/{id}/activity', 'ActivityController@index');
        Route::post('entries/copy', 'CopyEntriesController@copy');

        //STARS
        Route::post('entries/star', 'StarredEntriesController@add');
        Route::post('entries/unstar', 'StarredEntriesController@remove');

        //ENTRY PATHS
        Route::get('entries/{entryId}/path', 'EntryPathController@getPath');

        //sharing
        Route::post('shareable-links/{linkId}/import', 'SharesController@addCurrentUser');
        Route::post('shares/add-users', 'SharesController@addUsers');
        Route::put('shares/update-users', 'SharesController@updateUsers');
        Route::delete('shares/remove-user/{userId}', 'SharesController@removeUser');

        //SHAREABLE LINKS
        Route::get('entries/{id}/shareable-link', 'ShareableLinksController@show');
        Route::post('entries/{id}/shareable-link', 'ShareableLinksController@store');
        Route::put('shareable-links/{id}', 'ShareableLinksController@update');
        Route::delete('shareable-links/{id}', 'ShareableLinksController@destroy');

        //SPACE USAGE
        Route::get('user/space-usage', 'UserDiskSpaceController@getSpaceUsage');
    });

    //SHAREABLE LINKS PREVIEW (NO AUTH NEEDED)
    Route::get('shareable-links/{hash}', 'ShareableLinksController@show');
    Route::get('shareable-links/{linkId}/preview/{entryId}', 'ShareableLinkPreviewController@show');
    Route::post('shareable-links/{linkId}/check-password', 'ShareableLinkPasswordController@check');
});

//UPDATE
Route::group(['prefix' => 'secure', 'middleware' => 'web'], function () {
    Route::get('update', 'UpdateController@show');
    Route::post('update/run', 'UpdateController@update');
});

//FRONT-END ROUTES THAT NEED TO BE PRE-RENDERED
Route::get('/', '\Common\Core\Controllers\HomeController@show')->middleware('prerenderIfCrawler:homepage');

//CATCH ALL ROUTES AND REDIRECT TO HOME
Route::get('{all}', '\Common\Core\Controllers\HomeController@show')->where('all', '.*');
