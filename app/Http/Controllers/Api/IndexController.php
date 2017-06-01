<?php
/**
 * Created by PhpStorm.
 * User: changy
 * Date: 2017/5/25
 * Time: 18:18
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Common\Curl;
use App\Http\Controllers\Common\Sign;
use App\Http\Controllers\Common\Utils;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Common\PayRedis;
use App\Http\Controllers\Common\Log;

class IndexController extends Controller
{
    /**
     * 订单支付状态-待支付
     */
    const ORDER_STATUS_UNPAID = 0;

    /**
     * 订单号长度
     */
    const ORDER_LEN = 32;

    private $payWays = array(
        '100' => 'uPay'
    );

    /**
     * BOSS移动支付统一入口
     * @param Request $request
     * @return string
     */
    public function onlyPay(Request $request){
        //编写支付信息验证规则
        $rule = array(
            array('subject', array('required' => true), 'required', '简介必填'), //价格
            array('totalAmount', array('required' => true, 'number' => true), 'required', '金额必填'), //价格
            array('dynamicId', array('required' => true, 'number' => true), 'required', '二维码'), //价格
            array('payType', array('required' => true, 'number' => true), 'required', '支付方式'), //价格
            array('operator', array('required' => true, 'number' => true), 'required', '操作员'), //价格
            array('networkId', array('required' => true, 'number' => true), 'required', '校区网点ID'), //价格
            array('orderId', array('required' => true, 'number' => true), 'required', '订单ID'), //价格
            array('time', array('required' => true), 'required', '创建时间'), //价格
        );
        //获取验证后的参数
        $param = $this->validator($rule, $request->all());
        if(!$param)
            return $this->response($this->getError());
        //记录Boss发送请求日志
        Log::setLog($param, 'indexOnlyPay', 'info', 'response');//记录upay返回数据日志
        //获取流水号
        $serialNumber = $this->getSerialNumber();
        //订单支付状态为待支付
        $status = self::ORDER_STATUS_UNPAID;
        //存储BOSS发送过来的数据
        $orderId = $param['orderId'];
        $param['networkId'] = Utils::networkFormat($param['networkId']);
        $flag = PayRedis::setOrderInfo($orderId, $serialNumber, $param['totalAmount'], $param['dynamicId'], $param['payType'], $status, $param['subject'], $param['operator'], $param['networkId'], date('Y-m-d H:i:s'), date('Y-m-d H:i:s'));
        if(!$flag)
            return $this->response('ServiceIsNotWork');
        //根据学校和支付方式获取终端号
        $terminalSn = PayRedis::getTerminalIdByNetworkId($param['networkId'], $param['payType']);

        //记录调用日志
        $mLogData = array(
            'terminalSn' => $terminalSn,
            'serialNumber' => $serialNumber,
            'totalAmount' => $param['totalAmount'],
            'dynamicId' => $param['dynamicId'],
            'subject' => $param['subject'],
            'operator' => $param['operator']
        );
        Log::setLog($mLogData, 'UPaydefray', 'info', 'request');
        //判断支付过程
        $mResponse = $this->pay($terminalSn, $serialNumber, $param['totalAmount'], $param['dynamicId'], $param['subject'], $param['operator']);
        //增加收钱吧返回信息日志记录info

        //返回状态至前端，这个地方支付中心进行判断，把状态写入redis中

        if ($mResponse['result_code'] == '200'){

            //修改订单状态为支付返回状态
            $mStatus = $mResponse['biz_response']['data']['order_status'];
            if ($mStatus == 'PAID') {
                //记录upay返回数据日志
                Log::setLog($mResponse, 'UPaydefray', 'info', 'response');
                //修改redis中的状态为 成功
                $status = PayRedis::updateOrderInfoById($orderId, 1);
                $mBossData = array(
                    'client_sn' => $mResponse['biz_response']['data']['client_sn'], //订单流水号
                    'payer_uid' => $mResponse['biz_response']['data']['payer_uid'],  //付款人ID
                    'channel_finish_time' => $mResponse['biz_response']['data']['channel_finish_time']
                );
                return $this->response('payIsApplied', $mBossData);
            } elseif ($mStatus == 'CREATED') {
                //记录upay返回数据日志
                Log::setLog($mResponse, 'UPaydefray', 'info', 'response');
                //修改redis中的状态为 支付中,这个地方，支付中心进行轮询，直到收钱吧返回有效信息
                $status = PayRedis::updateOrderInfoById($orderId, 0);
                //返给boss相应的流水号
                $mBossData = array(
                    'client_sn' => $mResponse['biz_response']['data']['client_sn'], //订单流水号
                );
                return $this->response('payIsAction', $mBossData);
            } elseif ($mStatus == 'PAY_CANCELED'){
                //记录upay返回数据日志
                Log::setLog($mResponse, 'UPaydefray', 'error', 'response');
                //修改redis中的状态为 支付失败
                $status = PayRedis::updateOrderInfoById($orderId, -1);
                //返给boss相应的流水号
                $mBossData = array(
                    'client_sn' => $mResponse['biz_response']['data']['client_sn'], //订单流水号
                );
                return $this->response('tradeError', $mBossData);
            }
        } else if ($mResponse['result_code'] == '400'){
            //记录upay返回数据日志
            Log::setLog($mResponse, 'UPaydefray', 'error', 'response');
            //修改redis中的状态
            $status = PayRedis::updateOrderInfoById($orderId, -1);
            return $this->response('tradeError',array('error_message'=> $mResponse['error_message']));
        } else {
            //记录upay返回数据日志，错误级别：应用组件异常
            Log::setLog($mResponse, 'UPaydefray', 'critical', 'response');
            $status = PayRedis::updateOrderInfoById($orderId, -1);
            //修改redis中的状态
            return $this->response('tradeError',array('error_message'=> '请联系工程师'));
        }
    }

    /**
     * 获取支付方式
     * @param Request $request
     * @return string
     */
    public function onlyGetPayWay(Request $request){
        Log::setLog(array('come'=>'on'), 'getway1','info','request');
        //编写支付信息验证规则
        $rule = array(
            array('networkId', array('required' => true, 'number' => true), 'testErrorId','呵呵'), //价格
        );
        //获取验证后的参数
        $param = $this->validator($rule, $request->all());
        if(!$param)
            return $this->response($this->getError());
        Log::setLog($param, 'getway','info','request');
        //返回校区对应的有效的支付方式
        $param['networkId'] = Utils::networkFormat($param['networkId']);
        $payWays = PayRedis::getPayWays();
        $data = array();
        foreach($payWays as $key => $payWay){
            $flag = $this->getValidate($param['networkId'], $key);
            if($flag)
                $data[$key] = $payWay;
        }
        Log::setLog($data, 'getway','info','response');
        return $this->response('success',array('payWays' => Utils::dataFormat($data)));
    }

    /**
     * 验证该校区是否支持该支付方式
     * @param Request $networkId 校区ID
     * @param array $payWay 支付方式
     * @return bool 是否验证通过
     */
    public function getValidate($networkId, $payWay){
        //验证该校区是否支持该支付方式
        $terminalId = PayRedis::getTerminalIdByNetworkId($networkId, $payWay);
        if(!$terminalId)
            return false;
        $terminalKey = PayRedis::getTerminalKeyById($terminalId, $payWay);
        if(!$terminalKey)
            return false;
        $checkTime = PayRedis::getTerminalTimeById($terminalId, $payWay);
        if(!$checkTime)
            return false;
        //判断该密钥是否有效
        Log::setLog(array('terminalId'=>$terminalId, 'terminalKey'=> $terminalKey, 'checkTime'=>$checkTime), 'validate', 'debug');
        if($checkTime != date('Ymd'))
            return Sign::checkIn($terminalId, $networkId, $payWay);
        return true;
    }

    /**
     * 校区接入支付方式
     * @param Request $request
     * @return string
     */
    public function onlyActivate(Request $request){
        //编写支付信息验证规则
        $rule = array(
            array('networkId', array('required' => true, 'number' => true), 'required', '非法校区'), //价格
            array('payWay', array('required' => true, 'number' => true), 'required', '非法方式'),
            array('code', array('required' => true, 'number' => true), 'required', '该激活码不可用')
        );
        //获取验证后的参数
        $param = $this->validator($rule, $request->all());
        if(!$param)
            return $this->response($this->getError());
        $param['networkId'] = Utils::networkFormat($param['networkId']);
        $flag = Sign::uPayActivate($param['networkId'], $param['code']);
        Log::setLog(array('flag'=>$flag), 'validate', 'debug');
        if($flag)
            return $this->response('success');
        return $this->response('deviceSignFailure');
    }

    /**
     * 校区接入支付方式
     * @param $networkId
     * @param $payWay
     * @param $code
     */
    public function activate($networkId,$payWay,$code){
        Sign::activate($networkId,$code,$payWay);
    }

    /**
     * 根据支付方式获取设备信息
     * @param Request $request
     * @return string
     */
    public function onlyGetDeviceInfo(Request $request){
        //编写支付信息验证规则（参考TestController@index）
        $rule = array(
            array('networkId', array('required' => true, 'number' => true), 'required', '校区网点不能为空'), //价格
            array('payWay', array('required' => true), 'required', '校区网点不能为空'), //描述
        );
        //获取验证后的参数
        $param = $this->validator($rule, $request->all());
        if(!$param)
            return $this->response($this->getError());
        $param['networkId'] = Utils::networkFormat($param['networkId']);
        //根据校区和支付方式获取设备信息(Array)
        $deviceInfo = PayRedis::getDeviceInfo($param['networkId'], $param['payWay']);
        return $this->response('success',array('deviceInfo' => Utils::dataFormat($deviceInfo)));
    }

    /**
     * 注册设备信息
     * @param Request $request
     * @return string
     */
    public function onlySetDeviceInfo(Request $request){
        //编写支付信息验证规则（参考TestController@index）
        //$rule = array(''=>'...');
        //获取验证后的参数
        //$param = $this->validator($rule, $request->all());
        $param = $request->all();
        if(!$param)
            return $this->response($this->getError());
        //根据校区和支付方式获取设备信息(Array)
        $deviceInfo = PayRedis::setDeviceInfo($param['networkId'], $param['deviceId'], $param['deviceName'], $param['payWay']);
        return $this->response('success',$deviceInfo);
    }

    /**
     * 获取有效终端信息
     * @param Request $request
     * @return string
     */
    public function onlyGetTerminalInfo(Request $request){
        //编写支付信息验证规则（参考TestController@index）
        $rule = array(''=>'...');
        //获取验证后的参数
        $param = $this->validator($rule, $request->all());
        if(!$param)
            return $this->response($this->getError());
        $networkId = $param['$networkId'];
        $payWay = $param['payWay'];
        //根据校区获取终设备号
        $deviceId = PayRedis::getDeviceInfo($networkId, $payWay);
        if(!Sign::isActivated($deviceId, $payWay))
            return $this->response('deviceIsNotActivated');
        if(!Sign::isCheckIn($deviceId, $payWay)){
            $funName = $this->payWays[$payWay].'CheckIn';
            Sign::$funName();
        }
        //返回成功信息
        return $this->response('success',array());
    }

    /**
     * 获取订单支付流水号
     * @return string
     */
    public function getSerialNumber(){
        $date = date('YmdHis');
        $len = strlen($date);
        return $date . Utils::genRandStr(self::ORDER_LEN - $len,'number');
    }

    //临时加
    public function pay($terminal_sn, $client_sn, $total_amount, $dynamic_id, $subject, $operator){

        $mData = array(
            'terminal_sn' => $terminal_sn,
            'client_sn' => $client_sn,
            'total_amount' => $total_amount,
            'dynamic_id' => $dynamic_id,
            'subject' => $subject,
            'operator' => $operator,
        );
        $mHeader  = Sign::getUPayHeader($mData);
        //增加收钱吧提交的信息 日志记录info
        Log::setLog($mData, 'UPaypay', 'info', 'request');//记录传递给upay的支付数据日志

        $mReponse = Curl::getData('https://api.shouqianba.com'.'/upay/v2/pay', $mData, 'post',$mHeader);
        return json_decode($mReponse, true);
    }

}