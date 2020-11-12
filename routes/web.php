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

Route::get('/', function () {
    return view('welcome');
});
Route::get('/info', function () {
    // return view('welcome');
    echo "123";
});

Route::get('/test1','TextController@test1');

//Route::any('/Token','IndexController@wxEvent');  //接受时间推送
//Route::get('/wx/token','IndexController@getAccessToken');  //获取token
//Route::post('/test2','IndexController@test2');
//Route::get('/guzzle2','IndexController@guzzle2');
//Route::any('/createMenu','IndexController@createMenu');
//Route::any('/linShi','IndexController@linShi');


Route::prefix('/Token')->group(function(){
    Route::any('/','IndexController@index');  //微信接入
    Route::any('/wxEvent','IndexController@wxEvent');  //接受时间推送
    Route::get('/token','IndexController@getAccessToken');  //获取token
    Route::any('/createMenu','IndexController@createMenu');

});

Route::prefix('/test')->group(function(){
    Route::get('/guzzle1','TextController@guzzle1');
    Route::get('/guzzle2','TextController@guzzle2');
    Route::get('/guzzle3','TextController@guzzle3');
});
