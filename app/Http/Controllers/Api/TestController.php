<?php
/**
 * Created by PhpStorm.
 * User: changy
 * Date: 2017/5/17
 * Time: 13:20
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Common\Locker;
use App\Http\Controllers\Common\PayRedis;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Common\Util;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\Common\Curl;

class TestController extends Controller
{
    public function index(Request $request){
        $payWay = 100;
        $networkId = '201700007337';
        $way = PayRedis::getTerminalInfoByNetworkId($payWay);
        dd($way);
        $rule = array(
            array('id', array('required' => true, 'number' => true, 'gt' => 2, 'lt' => 5), 'testErrorId','呵呵'), //价格
            array('mobile', array('required' => true,'mobile' => true), 'testErrorMobile'), //描述
        );
        $data = $this->validator($rule, $request->all());
        if(!$data)
            return $this->response($this->getError());
        return $this->response('success',$data);
        //return $this->response('welcome to Api Test.', array('111'=>'22'));
    }
    public function test(Request $request){
        return Curl::getData('http://pay.cn/api/pay/way/get', array('networkId' => '00007337'));

        $cURL = curl_init();//启动一个CURL会话
        $url = 'http://pay.cn/api/pay/way/get?networkId=00007337';
        curl_setopt($cURL,CURLOPT_URL,$url);//设置抓取的url
        curl_setopt($cURL,CURLOPT_HEADER,0);//设置头文件的信息作为数据流输出
        curl_setopt($cURL,CURLOPT_RETURNTRANSFER,1);//设置获取的信息以文件流的形式返回，而不是直接输出。
        $return = curl_exec($cURL);//执行命令
        curl_close($cURL);//关闭URL请求
        return $return;
        //Redis::hDel('GET_ALL_TERMINAL_TIME_100', '100000030001465357');
        //return Redis::hSet('GET_ALL_TERMINAL_TIME_100', '100000030001465749', '20170527');
        //return view('pay.index',array('orderId'=>1,'price'=>2));
        Locker::lock('PAY::TEST');
        //do something...
        $this->dump('unlock');
        Locker::unLock('PAY::TEST');
        return $this->response('welcome to Api Test.', array('11'=>'22'));
    }
    public function sign(){
        $new = new Sign();
        return  $new -> uPayActivate('92392166', '797979797999','U_PAY');
    }
}