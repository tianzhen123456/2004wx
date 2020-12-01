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

//微信
Route::prefix('/Token')->group(function(){
    Route::any('/','IndexController@index');  //执行微信
    Route::any('/wxEvent','IndexController@wxEvent');  //接受时间推送
    Route::get('/token','IndexController@getAccessToken');  //获取token
    Route::any('/createMenu','IndexController@createMenu');
    Route::any('/callBack','IndexController@callBack');
    Route::any('/getweather','IndexController@getweather');
    Route::any('/xcxLogin','XcxController@xcxLogin');  //小程序登录
    Route::any('/test','XcxController@test');




});

Route::prefix('/test')->group(function(){
    Route::get('/guzzle1','TextController@guzzle1');
    Route::get('/guzzle2','TextController@guzzle2');
    Route::get('/guzzle3','TextController@guzzle3');
});


//小程序接口
Route::prefix('/api')->group(function(){
    Route::get('/userinfo','ApiController@userInfo');
    Route::get('/test','ApiController@test');
    Route::any('/xcxLogin','ApiController@xcxLogin');  //首页登录
    Route::any('/userLogin','ApiController@userLogin');  //个人中心登录
    Route::get('/goodsList','ApiController@goodsList');  //商品列表
    Route::get('/goods','ApiController@goods');            //商品详情
    Route::get('/add-fav','ApiController@addFav');           //收藏
    Route::post('/add-cart','ApiController@addCart')->middleware('check.token');           //加入购物车
    Route::get('/cart-list','ApiController@cartList');           //购物车列表
//    Route::post('/cart-add','ApiController@cartAdd')->middleware('check.token');
    Route::any('/del-cart','ApiController@delCart')->middleware('check.token');

});
