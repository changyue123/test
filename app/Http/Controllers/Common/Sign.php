<?php
/**
 * Created by PhpStorm.
 * User: changy
 * Date: 2017/5/22
 * Time: 16:59
 */

namespace App\Http\Controllers\Common;


use Illuminate\Support\Facades\Redis;

class Sign
{

    public static $ways = array(
        '100' => 'uPay'
    );
    /**
     * 收钱吧相关参数
     */
    public static $u_pay_params = array(
        'uPayUrl' => 'https://api.shouqianba.com',
        'uPayVendorSn' => '20160317111402',
        'uPayVendorKey' => '7a87156a7c8e9ca9cecf2787fefe47d3',
        'uPayCode' => '80798456',
        'uPayAppId' => '2016081800000003',
        'payWay' => '100',
    );
    /**
     * 收钱吧接口地址
     */
    const U_PAY_URL = 'https://api.shouqianba.com';
    /**
     * 服务商序列号
     */
    const U_PAY_VENDOR_SN = '20160317111402';
    /**
     * 服务商密钥
     */
    const U_PAY_VENDOR_KEY = '7a87156a7c8e9ca9cecf2787fefe47d3';

    /**
     * 激活码
     */
    const U_PAY_CODE = '92392166';

    /**
     * app id，从服务商平台获取
     */
    const U_PAY_APP_ID = '2016081800000003';

    /**
     * 获取收钱吧头信息（内含签名信息）
     * @param array $params
     * @return string
     */
    public static function getUPayHeader(array $params){
        //todo 获取Upay签名
        $terminalSn = $params['terminal_sn'];
        //获取$terminal_key
        $terminalKey = PayRedis::getTerminalKeyById($terminalSn, self::$u_pay_params['payWay']);

        $data = json_encode($params);
        $sign = md5($data . $terminalKey);
        $Authorization = $terminalSn ." ". $sign;
        $headData = array(
            'Content-Type: application/json',
            'Authorization: '.$Authorization,
            'Content-Length: ' . strlen($data)
        );

        return $headData;
    }

    /**
     * 获取EFS签名方法
     * @param string $signNumber
     * @param string $signKey
     * @return string
     */
    public static function getEfsSign($signNumber, $signKey){
        //todo 获取EFS签名
        $sign = md5($signNumber . $signKey); //just a demo
        return $sign;
    }

    /**
     * 校区网点激活
     * @param $networkId
     * @param $code
     * @param $payWay
     */
    public static function activate($networkId, $code, $payWay){
        //var_dump("come");exit;
        if(array_key_exists($payWay, self::$ways)){
            $func = self::$ways[$payWay] . 'Activate';
            self::$func($networkId,$code);
        }
    }

    /**
     * 校区网点签到
     * @param $terminalId
     * @param $networkId
     * @param $payWay
     */
    public static function checkIn($terminalId, $networkId, $payWay){
        if(array_key_exists($payWay, self::$ways)){
            $func = self::$ways[$payWay] . 'CheckIn';
            return self::$func($networkId, $terminalId);
        }
    }

    /**
     * 通过签到获取每日最新的终端密钥
     * @param String $terminalSn 终端号
     * @param String $networkId 设备号(此处为校区ID)
     * @return bool|mixed
     */
    public static function uPayCheckIn($networkId, $terminalSn){
        //调取收钱吧签到接口，获得最新终端ID和Key
        $terminalKey = PayRedis::getTerminalKeyById($terminalSn, self::$u_pay_params['payWay']);
        $data = array(
            'terminal_sn' => $terminalSn,
            'device_id' => $networkId,
        );
        //获取有效的头信息信息
        $header = self::getUPayHeader($data);

        $response = Curl::getData((self::U_PAY_URL).'/terminal/checkin', $data, 'post',$header);
        $response = json_decode($response, true);
        Log::setLog(array('response' => $response), 'upaycheckin', 'debug');
        if ($response['result_code'] == '200'){
            $terminalSn = $response['biz_response']['terminal_sn'];
            $terminalKey = $response['biz_response']['terminal_key'];
            //更新密钥和签到信息
            PayRedis::setTerminalInfoByNetworkId($networkId, $terminalSn, $terminalKey, self::$u_pay_params['payWay']);
           //获取有效的终端号和密钥
            $terminalInfo = PayRedis::getTerminalInfoByNetworkId(self::$u_pay_params['payWay']);
            Log::setLog(array('terminalInfo' => $terminalInfo), 'getTerminal', 'debug');
            return $terminalInfo;
        }else{
            //todo：记录错误日志ERROR,存储收钱吧返回的有效错误信息
            return false;
        }
    }

    /**
     * 注册设备相关信息
     * @param String $netWordId 网点ID
     * @param String $deviceId 设备ID
     * @param String $deviceName 设备名称
     * @param String $terminalId 终端号
     * @param String $terminalKey 终端Key
     * @return bool
     */
    public function uPaySetDeviceInfo($netWordId, $deviceId, $deviceName, $terminalId, $terminalKey){
        //存储设备信息
        $deviceStatus = PayRedis::setDeviceInfo($netWordId, $deviceId, $deviceName, self::$u_pay_params['payWay']);
        //存储终端信息
        $terminalStatus = PayRedis::setTerminalInfo($deviceId, $terminalId, $terminalKey, self::$u_pay_params['payWay']);
        if(!$deviceStatus)
            PayRedis::delDeviceInfo($netWordId, $deviceId, self::$u_pay_params['payWay']);
        if(!$terminalStatus)
            PayRedis::delTerminalInfo($deviceId, self::$u_pay_params['payWay']);
        return $deviceStatus && $terminalStatus;

    }

    /**
     * 设备激活，获取终端号和终端密钥
     * @param String $deviceId 设备ID
     * @param String $code 激活码
     * @return mixed
     */
    public static function uPayActivate($deviceId, $code){
        //注册设备信息
        //todo:调取收钱吧激活接口，获得最新终端ID和Key
        $vendorSn = self::$u_pay_params['uPayVendorSn'];
        $vendorKey = self::$u_pay_params['uPayVendorKey'];
        $arr = array();
        $arr['app_id'] = self::$u_pay_params['uPayAppId'];
        $arr['code'] = $code;
        $arr['device_id'] = $deviceId;
        $arr['vendor_sn'] = $vendorSn;
        $arr['vendor_key'] = $vendorKey;
        $data= json_encode($arr);

        $sign=md5($data.$vendorKey);
        $Authorization=$vendorSn." ".$sign;
        $header =  array(
            'Content-Type: application/json',
            'Authorization: '.$Authorization,
            'Content-Length: ' . strlen($data)
        );
        $response = Curl::getData((self::$u_pay_params['uPayUrl']).'/terminal/activate', $data, 'post',$header);
        $response = json_decode($response, true);

        if ($response['result_code'] == 200){
            $terminalId = $response['biz_response']['terminal_sn'];
            $terminalKey = $response['biz_response']['terminal_key'];
            //存储设备相关信息
            PayRedis::setTerminalInfoByNetworkId($deviceId, $terminalId, $terminalKey, self::$u_pay_params['payWay']);
            return true;
        }else{
            return false;
        }
        //$terminalSn = '20160317111402';
        //$terminalKey = '7a87156a7c8e9ca9cecf2787fefe47d3';
        //更新密钥和签到信息
        //PayRedis::setTerminalInfoByNetworkId($deviceId, $terminalSn, $terminalKey, self::$u_pay_params['payWay']);
        //return $response[];
    }

    /**
     * 判断是否已签到
     * @param String $terminalId 终端号
     * @param String $payWay 支付方式
     * @return bool
     */
    public static function uPayIsCheckIn($terminalId, $payWay){
        //获取当前日期
        $currentDate = date('Ymd');
        //获取终端签到日期
        $checkInDate = PayRedis::getTerminalTimeById($terminalId, $payWay);
        return $currentDate == $checkInDate ? true : false;
    }

    /**
     * 判断设备是否已激活
     * @param String $deviceId 设备ID
     * @param String $payWay 支付方式
     * @return bool
     */
    public static function isActivated($deviceId, $payWay){
        //判断设备是否被激活
        $terminalInfo = PayRedis::getTerminalInfo($deviceId, $payWay);
        if(!$terminalInfo)
            return false;
        return true;
    }

}