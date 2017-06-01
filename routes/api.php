<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
//测试接口
Route::group(['prefix' => 'test', 'namespace' => 'Api'], function () {
    Route::get('/pay', 'IndexController@onlyPay');
    Route::get('/', 'TestController@index');
    Route::get('/test', 'TestController@test');
    Route::get('/sign', 'TestController@sign');
});

//收钱吧支付接口
Route::group(['prefix' => 'pay', 'namespace' => 'Api'], function () {
    Route::any('/', 'IndexController@onlyPay');
    Route::any('/way/get', 'IndexController@onlyGetPayWay');
    Route::any('/way/activate', 'IndexController@onlyActivate');
});

//efs
Route::group(['namespace' => 'Api'], function () {
    //根据网点id获取最小未上报日期
    Route::get('/IncomeSaleMinDate', 'EfsController@getReportMinDate');
    //根据网点id和日期获取该天上报数据
    Route::get('/IncomeSale', 'EfsController@OnlyReport');
    //确认上报成功
    Route::get('/ConfirmIncomeSale', 'EfsController@OnlyReportNotify');
});

//统一对外（boss）支付接口 author:yinlei
Route::group(['prefix' => 'upayCenter', 'namespace' => 'Api'], function () {

    Route::any('/onlyPay', 'IndexController@onlyPay');

    Route::any('/onlyGetDeviceInfo', 'IndexController@onlyGetDeviceInfo');
    Route::any('/onlyGetTerminalInfo', 'IndexController@onlyGetTerminalInfo');
    Route::any('/onlypay', 'IndexController@onlyPay');
});

//统一对外（boss）支付接口 author:yinlei
Route::group(['prefix' => 'uPay', 'namespace' => 'Api'], function () {
    //对外的Upay支付接口
    Route::any('/onlyDefray', 'UPayController@onlyDefray');
    //对外收钱吧的回调接口
    Route::any('/onlyNotify', 'UPayController@onlyNotify');
    //对外支付中心内部页面的回调接口
    Route::any('/onlyInquire', 'UPayController@onlyInquire');


});